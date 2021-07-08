<?php

namespace Drupal\oec_group_comments\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Group Comments route subscriber.
 */
class GroupCommentsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('comment.reply')) {
      $route->setRequirement('_custom_access', '\Drupal\oec_group_comments\Controller\GroupCommentController::replyFormAccess');
    }
  }

}
