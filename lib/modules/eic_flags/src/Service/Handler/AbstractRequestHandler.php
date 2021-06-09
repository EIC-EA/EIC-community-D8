<?php

namespace Drupal\eic_flags\Service\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\flag\FlaggingInterface;

/**
 * Class AbstractRequestHandler
 *
 * @package Drupal\eic_flags\Service\Handler
 */
abstract class AbstractRequestHandler implements HandlerInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * AbstractRequestHandler constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function deny(FlaggingInterface $flagging, ContentEntityInterface $content_entity, string $reason) {
    $this->moduleHandler->invokeAll('request_close', [$flagging, $content_entity, RequestStatus::DENIED, $reason]);
    $flagging->delete();

    return TRUE;
  }

}
