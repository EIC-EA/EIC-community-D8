<?php

namespace Drupal\eic_flags\Service;

/**
 * Class RequestHandlerCollector
 *
 * @package Drupal\eic_flags\Service
 */
class RequestHandlerCollector {

  /**
   * List of handlers.
   *
   * @var array
   */
  private $requestHandlers = [];

  /**
   * Adds the given handler to the list of handlers.
   *
   * @param \Drupal\eic_flags\Service\HandlerInterface $handler
   */
  public function addHandler(HandlerInterface $handler) {
    $this->requestHandlers[$handler->getType()] = $handler;
  }

  /**
   * @param string $id
   *
   * @return HandlerInterface|null
   */
  public function getHandlerByType(string $id) {
    return $this->requestHandlers[$id] ?? NULL;
  }

  /**
   * @return array
   */
  public function getHandlers() {
    return $this->requestHandlers;
  }

}
