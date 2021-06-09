<?php

namespace Drupal\eic_flags\Service\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\flag\FlaggingInterface;

/**
 * Interface HandlerInterface
 *
 * @package Drupal\eic_flags\Service\Handler
 */
interface HandlerInterface {

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

}
