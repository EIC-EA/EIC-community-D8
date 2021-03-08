<?php

namespace Drupal\eic_homepage\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for EIC Homepage routes.
 */
class HomepageController extends ControllerBase {

  /**
   * Builds the homepage title.
   */
  public function title() {
    return $this->t('Welcome to the EIC Community');
  }

  /**
   * Builds the empty homepage.
   */
  public function build() {
    $output = [];
    return $output;
  }

}
