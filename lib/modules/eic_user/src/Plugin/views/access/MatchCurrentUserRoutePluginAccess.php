<?php

namespace Drupal\eic_user\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Access plugin to check if current user match with the route user.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "current_user_match_access",
 *   title = @Translation("Matching current user with route user parameter"),
 * )
 */
class MatchCurrentUserRoutePluginAccess extends AccessPluginBase {

  /**
   * @inheritDoc
   */
  public function access(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_route_current_user_access', 'TRUE');
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Match current user from route user parameter');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
  }
}
