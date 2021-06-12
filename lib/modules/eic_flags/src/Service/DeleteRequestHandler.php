<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_flags\RequestTypes;
use Drupal\flag\FlaggingInterface;

/**
 * Class DeleteRequestHandler
 *
 * @package Drupal\eic_flags\Service
 */
class DeleteRequestHandler extends AbstractRequestHandler {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return RequestTypes::DELETE;
  }

  /**
   * {@inheritdoc}
   */
  public function accept(FlaggingInterface $flagging, ContentEntityInterface $content_entity, string $reason) {
    // TODO: Implement accept() method.
  }

  /**
   * @return string[]
   */
  public function getSupportedEntityTypes() {
    return [
      'node' => 'request_delete_content',
      'group' => 'request_delete_group',
      'comment' => 'request_delete_comment',
    ];
  }

}
