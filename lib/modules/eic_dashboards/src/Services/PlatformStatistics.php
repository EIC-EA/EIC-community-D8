<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Platform statistics class.
 */
class PlatformStatistics implements PlatformStatisticsInterface {
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
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
    CountryServiceInterface $countryService,
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->countryService = $countryService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('eic_dashboards.country_service'),
    );
  }


  /**
   * Returns the total number of platform members.
   */
  public function getTotalPlatformMembers(): array|int {
    $query = $this->entityTypeManager->getStorage('user')->getQuery();
    return $query->condition('status', '1')
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
      $query = $this->connection->select('taxonomy_term_field_data', 'ttfd');
      $query->fields('ttfd', ['name']);
      $query->condition('ttfd.tid', $row->taxonomy_term_id);
      $label = $query->execute()->fetchField();

      $data[$row->taxonomy_term_id][$argumentId] = $row->taxonomy_term_id;
      $data[$row->taxonomy_term_id]['label'] = !empty($label) ? $label : 'NA';
      $data[$row->taxonomy_term_id]['count'] = (int) $row->count_members;
    }

    return $data;
  }
}
