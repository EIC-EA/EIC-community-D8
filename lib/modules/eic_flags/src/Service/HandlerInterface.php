<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\flag\FlaggingInterface;

/**
 * Interface HandlerInterface
 *
 * @package Drupal\eic_flags\Service\Handler
 */
interface HandlerInterface {

  /**
   * Returns the type of request the handler is for.
   *
   * @return string
   */
  public function getType();

  /**
   * Starts the 'deny' workflow for a request.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   * @param string $reason
   *
   * @return bool
   */
  public function deny(FlaggingInterface $flagging, ContentEntityInterface $content_entity, string $reason);

  /**
   * Starts the 'accept' workflow for a request.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   * @param string $reason
   *
   * @return bool
   */
  public function accept(FlaggingInterface $flagging, ContentEntityInterface $content_entity, string $reason);

  /**
   * Applies the given the corresponding flag to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $reason
   *
   * @return bool|NULL
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason);

  /**
   * Returns an array of supported entity types.
   *
   * @return array
   */
  public function getSupportedEntityTypes();

}
