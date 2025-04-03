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
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
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
    $query->orderBy('taxonomy_term_id', 'ASC');
    $results = $query->execute()->fetchAll();

    $data = [];

    foreach ($results as $row) {
      $data[$row->taxonomy_term_id][$argumentId] = $row->taxonomy_term_id;
      $data[$row->taxonomy_term_id]['label'] = $this->entityTypeManager->getStorage('taxonomy_term')->load($row->taxonomy_term_id)?->name->value ?? 'NA';
      $data[$row->taxonomy_term_id]['count'] = (int) $row->count_members;
    }

    return $data;
  }
}
