<?php

namespace Drupal\eic_dashboards\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Router;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_dashboards\Constants\DashboardsViews;

/**
 * Service to log an export of a dashboard list.
 */
class DashboardExportLog {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected \Drupal\Core\Routing\Router $router,
  ) {}


  /**
   * Log the export of a dashboard view list as a message.
   *
   * @param $results
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function log($results) {
    if ($view_id = $this->getViewIdByUrl($results['redirect_url'])) {
      $entity = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'log_dashboard_listing_export',
        'field_event_executing_user' => $this->currentUser->id(),
        'field_view_id' => $view_id,
      ]);
      $entity->save();
    }

  }

  /**
   * Get the dashboard view ID from the URL or FALSE if other view.
   *
   * @param $url
   *
   * @return false|string
   */
  private function getViewIdByUrl($url): bool|string {
    $match = $this->router->match($url);
    // Check the view is about dashboards, otherwise skip log.
    if (in_array($match['view_id'], DashboardsViews::DASHBOARDS_LIST_VIEWS, TRUE)) {
      return $match['view_id'];
    }
    return FALSE;
  }

}
