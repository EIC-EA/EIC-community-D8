<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\flag\FlaggingInterface;

/**
 * Class ArchiveRequestHandler
 *
 * @package Drupal\eic_flags\Service
 */
class ArchiveRequestHandler extends AbstractRequestHandler {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return RequestTypes::ARCHIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    return [
      RequestStatus::OPEN => 'notify_new_archive_request',
      RequestStatus::DENIED => 'notify_delete_archive_denied',
      RequestStatus::ACCEPTED => 'notify_delete_archive_accepted',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    //TODO implement the accept method
  }

  /**
   * @return string[]
   */
  public function getSupportedEntityTypes() {
    return [
      'node' => 'request_archive_content',
      'group' => 'request_archive_group',
      'comment' => 'request_archive_comment',
    ];
  }

}
