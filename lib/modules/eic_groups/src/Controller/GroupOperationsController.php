<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides group operation route controllers.
 */
class GroupOperationsController extends ControllerBase {

  /**
   * Builds the publish group page title.
   */
  public function publishTitle(GroupInterface $group) {
    return $this->t('Publish - @group_name', ['@group_name' => $group->label()]);
  }

  /**
   * Publishes a given group and redirects back to the group homepage.
   */
  public function publish(GroupInterface $group) {
    $group->setPublished();
    $group->set('moderation_state', 'published');
    $group->save(TRUE);
    $response = new RedirectResponse($group->toUrl()->toString());
    return $response->send();
  }

}
