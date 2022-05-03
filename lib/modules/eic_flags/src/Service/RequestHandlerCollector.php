<?php

namespace Drupal\eic_flags\Service;

/**
 * Class RequestHandlerCollector.
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
   *   The handler for the request type.
   */
  public function addHandler(HandlerInterface $handler) {
    $this->requestHandlers[$handler->getType()] = $handler;
  }

  /**
   * Returns the handled matching to the given id.
   *
   * @param string $id
   *   The id of the handler.
   *
   * @return HandlerInterface|null
   *   Matching handler or null.
   */
  public function getHandlerByType(string $id) {
    return $this->requestHandlers[$id] ?? NULL;
  }

  /**
   * Returns all registered handlers.
   *
   * @return \Drupal\eic_flags\Service\HandlerInterface[]
   *   Array of request handlers.
   */
  public function getHandlers() {
    return $this->requestHandlers;
  }

}
