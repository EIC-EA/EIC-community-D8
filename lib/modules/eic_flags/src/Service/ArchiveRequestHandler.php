<?php

namespace Drupal\eic_flags\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\flag\FlaggingInterface;

/**
 * Class ArchiveRequestHandler.
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
      RequestStatus::OPEN => 'notify_new_archival_request',
      RequestStatus::DENIED => 'notify_archival_request_denied',
      RequestStatus::ACCEPTED => 'notify_archival_request_accepted',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    if ($this->moderationInformation->isModeratedEntity($content_entity)) {
      $content_entity->set('moderation_state', 'archived');
    }
    else {
      if ($content_entity instanceof CommentInterface) {
        $now = DrupalDateTime::createFromTimestamp(time());

        $content_entity->set('comment_body', [
          'value' => $this->t(
            'This comment has been archived by a content administrator.',
            [],
            ['context' => 'eic_flags']
          ),
          'format' => 'plain_text',
        ]);
        $content_entity->save();
      }
      else {
        $content_entity->set('status', FALSE);
      }
    }

    $content_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    return [
      'node' => 'request_archive_content',
      'group' => 'request_archive_group',
      'comment' => 'request_archive_comment',
    ];
  }

}
