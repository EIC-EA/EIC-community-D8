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
    return $query->accessCheck(FALSE)
      ->count()
      ->execute();
  }
}
