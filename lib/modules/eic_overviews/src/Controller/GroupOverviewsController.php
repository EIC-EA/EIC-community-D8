<?php

namespace Drupal\eic_overviews\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides route response for group overview pages.
 */
class GroupOverviewsController extends ControllerBase {

  /**
   * Returns the content for a generic group overview page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function buildGenericPage(GroupInterface $group) {
    return ['#markup' => ''];
  }

}
