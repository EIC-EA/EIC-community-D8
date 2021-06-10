<?php

namespace Drupal\eic_flags\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_flags\DeleteRequestListBuilder;
use Drupal\eic_flags\RequestTypes;

/**
 * Class FlagRequestController
 *
 * @package Drupal\eic_flags\Controller
 */
class FlagRequestController extends ControllerBase {

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function listing($flag_type) {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    $list_builder = NULL;
    switch ($flag_type) {
      case RequestTypes::DELETE:
        $list_builder = DeleteRequestListBuilder::class;
        break;
    }

    return $this->entityTypeManager()->createHandlerInstance($list_builder, $definition)->render();
  }

  public function close() {
    // TODO
  }

}
