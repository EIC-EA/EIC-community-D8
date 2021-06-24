<?php

namespace Drupal\eic_flags\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eic_flags\FlaggedEntitiesListBuilder;
use Drupal\eic_flags\FlaggingListBuilder;
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
  public function listing() {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    return $this->entityTypeManager()
      ->createHandlerInstance(FlaggedEntitiesListBuilder::class, $definition)
      ->render();
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function detail() {
    $definition = $this->entityTypeManager()->getDefinition('flagging');

    return $this->entityTypeManager()
      ->createHandlerInstance(FlaggingListBuilder::class, $definition)
      ->render();
  }

}
