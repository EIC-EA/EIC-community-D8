<?php

namespace Drupal\eic_flags\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DeleteRequestController
 *
 * @package Drupal\eic_flags\Controller
 */
class DeleteRequestController extends ControllerBase {


  public function listing() {
    return $this->entityTypeManager()->getListBuilder('node')->render();
  }

}
