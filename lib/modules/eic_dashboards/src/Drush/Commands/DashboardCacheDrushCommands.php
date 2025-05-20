<?php

namespace Drupal\eic_dashboards\Drush\Commands;

use Drupal\eic_dashboards\Services\DashboardCacheManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class DashboardCacheDrushCommands extends DrushCommands {

  /**
   * Constructs a DashboardCacheDrushCommands object.
   */
  public function __construct(
    private readonly DashboardCacheManager $dashboardCacheManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_dashboards.cache_manager'),
    );
  }

  /**
   * Populate the data of the previous month into the database.
   */
  #[CLI\Command(name: 'eic_dashboards:invalidate-cache', aliases: ['dashboard-invalidate-cache'])]
  public function generateAllStatsPreviousMonth() {
    $this->dashboardCacheManager->invalidateAllCaches();
  }

}
