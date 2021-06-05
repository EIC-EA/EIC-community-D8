<?php

namespace Drupal\eic_flags\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_flags\DeleteRequestListBuilder;

/**
 * Class DeleteRequestController
 *
 * @package Drupal\eic_flags\Controller
 */
class DeleteRequestController extends ControllerBase {

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function listing() {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    return $this->entityTypeManager()->createHandlerInstance(DeleteRequestListBuilder::class, $definition)->render();
  }

}
