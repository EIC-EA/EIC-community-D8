<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Members statistics class.
 */
class MembersStatistics implements MembersStatisticsInterface {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The country service.
   *
   * @var \Drupal\eic_dashboards\Services\CountryServiceInterface
   */
  protected CountryServiceInterface $countryService;

  /**
   * The dashboard helper service.
   *
   * @var \Drupal\eic_dashboards\Services\DashboardHelperInterface
   */
  protected DashboardHelperInterface $dashboardHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
    CountryServiceInterface $countryService,
    DashboardHelperInterface $dashboardHelper,
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->countryService = $countryService;
    $this->dashboardHelper = $dashboardHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('eic_dashboards.country_service'),
      $container->get('eic_dashboards.helper')
    );
  }


  /**
   * Returns the total number of platform members.
   */
  public function getTotalMembers(): array|int {
    $query = $this->entityTypeManager->getStorage('user')->getQuery();
    return $query->condition('status', '1')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getMembersRegisteredPastDays(int $days = 30): int {
    $timestamp = strtotime("-$days days");

    $query = $this->entityTypeManager->getStorage('user')->getQuery();
    return $query->condition('status', 1)
      ->condition('created', $timestamp, '>=')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastRegisteredMembersList(int $maxResults = 10): array {
    $query = $this->connection->select('users_field_data', 'u');
    $query->fields('u', ['uid', 'created']);
    $query->condition('u.status', 1);
    $query->orderBy('u.uid', 'DESC');
    $query->range(0, $maxResults);
    $query->leftJoin('profile', 'p', 'u.uid = p.uid AND p.type = :profile_type', ['profile_type' => 'member']);
    $query->leftJoin('user__field_first_name', 'fn', 'u.uid = fn.entity_id');
    $query->leftJoin('user__field_last_name', 'ln', 'u.uid = ln.entity_id');
    $query->leftJoin('profile__field_location_address', 'pla', 'p.profile_id = pla.entity_id AND pla.deleted = 0');
    $query->addField('fn', 'field_first_name_value', 'first_name');
    $query->addField('ln', 'field_last_name_value', 'last_name');
    $query->addField('pla', 'field_location_address_country_code', 'country');

    $result = $query->execute()->fetchAllAssoc('uid');

    $members = [];
    foreach ($result as $uid => $user) {
      $profile_url = Url::fromRoute('entity.user.canonical', ['user' => $uid])->toString();

      // Build the user's full name.
      $first_name = $user->first_name ?? '';
      $last_name = $user->last_name ?? '';
      $title = trim("$first_name $last_name");

      // Append country name if available.
      if (!empty($user->country)) {
        $country_name = $this->countryService->getCountryName($user->country) ?? $user->country;
        $title .= " ($country_name)";
      }

      $members[] = [
        'prefix' => !empty($user->created) ? date('d M Y', $user->created) : '',
        'title' => $title,
        'url' => $profile_url,
      ];
    }

    return $members;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformMembersLoggedPastDays(int $days): int {
    $timestamp = strtotime("-$days days");

    $query = $this->entityTypeManager->getStorage('user')->getQuery();

    return $query->condition('status', 1)
      ->condition('login', $timestamp, '>=')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * Returns members grouped by country.
   */
  public function getMembersGroupedByCountry($argumentId): array {
    // TODO: Statically cache the result.
    $query = $this->connection->select('profile', 'pfl');
    $query->join('user__roles', 'ur', 'pfl.uid = ur.entity_id');
    $query->join('users_field_data', 'ufd', 'pfl.uid = ufd.uid');
    $query->join('profile__field_location_address', 'pfl_foa', 'pfl.profile_id = pfl_foa.entity_id');
    $query->addExpression('COUNT(pfl_foa.field_location_address_country_code)', 'count_members');
    $query->addExpression('pfl_foa.field_location_address_country_code', 'country_code');
    $query->condition('ufd.status', 1);
    $query->groupBy('country_code');
    $query->orderBy('country_code', 'ASC');
    $results = $query->execute()->fetchAll();

    $data = [];

    $countries = $this->countryService->getAllCountries();
    foreach ($results as $row) {
      $data[$row->country_code][$argumentId] = $row->country_code;
      $data[$row->country_code]['label'] = $countries[mb_strtoupper($row->country_code)] ?? '';
      $data[$row->country_code]['count'] = $row->count_members;
    }

    return $data;
  }

  /**
   * Returns members grouped by vocabulary.
   */
  public function getMembersPerTaxonomyTerm($taxonomyField, $argumentId): array {
    $query = $this->connection->select('profile', 'pfl');
    $query->join('user__roles', 'ur', 'pfl.uid = ur.entity_id');
    $query->join('users_field_data', 'ufd', 'pfl.uid = ufd.uid');
    $query->join('profile__' . $taxonomyField, 'tf', 'pfl.profile_id = tf.entity_id');
    $query->addExpression('COUNT(tf.' . $taxonomyField . '_target_id)', 'count_members');
    $query->addExpression('tf.' . $taxonomyField . '_target_id', 'taxonomy_term_id');
    $query->condition('ufd.status', 1);
    $query->groupBy('taxonomy_term_id');
    $query->orderBy('count_members', 'DESC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $row) {
      $data[$row->taxonomy_term_id][$argumentId] = $row->taxonomy_term_id;
      $data[$row->taxonomy_term_id]['label'] = $this->dashboardHelper->getTaxonomyTermLabel($row->taxonomy_term_id);
      $data[$row->taxonomy_term_id]['count'] = (int) $row->count_members;
    }

    return $data;
  }

  /**
   * {*inheritdoc*}
   */
  public function getMembersLinkedByType(string $membershipType): int {
    $query = $this->connection->select('group_content_field_data', 'gcfd');
    $query->join('users_field_data', 'ufd', 'gcfd.entity_id = ufd.uid');
    $query->addExpression('COUNT(DISTINCT gcfd.entity_id)');
    $query->condition('gcfd.type', $membershipType);
    $query->condition('ufd.status', 1);

    $result = $query->execute()->fetchField();

    return (int) $result;
  }

  /**
   * {*inheritdoc}
   */
  public function getCompletedMembersProfiles(): int {
    $query = $this->connection->select('profile', 'p');
    $query->leftJoin('profile__field_vocab_topic_expertise', 'pvte', 'p.profile_id = pvte.entity_id');
    $query->leftJoin('profile__field_vocab_topic_interest', 'pvti', 'p.profile_id = pvti.entity_id');
    $query->leftJoin('profile__field_location_address', 'pla', 'p.profile_id = pla.entity_id');

    // Join users table to link profiles with their owners.
    $query->innerJoin('users_field_data', 'u', 'p.uid = u.uid');

    // Filters: active users and member profiles.
    $query->condition('u.status', 1);
    $query->condition('p.type', 'member');

    // Select fields and aggregate.
    $query->addExpression('COUNT(DISTINCT pvte.field_vocab_topic_expertise_target_id)', 'expertise_count');
    $query->addExpression('COUNT(DISTINCT pvti.field_vocab_topic_interest_target_id)', 'interest_count');
    $query->addExpression('MAX(pla.field_location_address_country_code)', 'country_code');

    // Group by UID.
    $query->groupBy('p.uid');

    // Having conditions to filter completed profiles.
    $query->having('expertise_count > 0');
    $query->having('interest_count > 0');
    $query->having('country_code IS NOT NULL');

    // Now, count how many rows we get = how many users completed.
    $completedProfiles = $query->countQuery()->execute()->fetchField();

    return (int) $completedProfiles;
  }

}
