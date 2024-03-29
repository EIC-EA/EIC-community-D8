<?php

namespace Drupal\eic_flags\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\flag\FlaggingInterface;

/**
 * Service that provides logic to request entity archival.
 *
 * @package Drupal\eic_flags\Service
 */
class ArchiveRequestHandler extends AbstractRequestHandler {

  /**
   * The Solr document processor service.
   *
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor
   */
  private $solrDocumentProcessor;

  /**
   * Injects SOLR document processor service.
   *
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor|null $solr_document_processor
   *   The EIC Search Solr Document Processor.
   */
  public function setDocumentProcessor(?SolrDocumentProcessor $solr_document_processor) {
    $this->solrDocumentProcessor = $solr_document_processor;
  }

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
  public function canRequest(AccountInterface $account, ContentEntityInterface $entity) {
    // Deny access if entity is not published.
    if (!$this->moderationHelper->isPublished($entity)) {
      return AccessResult::forbidden();
    }

    return parent::canRequest($account, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    if ($this->moderationInformation->isModeratedEntity($content_entity)) {
      $workflow = $this->moderationInformation->getWorkflowForEntity($content_entity);

      switch ($workflow->id()) {
        case EICContentModeration::MACHINE_NAME:
          $content_entity->set('moderation_state', EICContentModeration::STATE_UNPUBLISHED);
          break;

        case GroupsModerationHelper::WORKFLOW_MACHINE_NAME:
          $content_entity->set('moderation_state', GroupsModerationHelper::GROUP_ARCHIVED_STATE);
          break;

        default:
          $content_entity->set('moderation_state', DefaultContentModerationStates::ARCHIVED_STATE);
          break;
      }

    }
    else {
      if ($content_entity instanceof CommentInterface) {
        $now = DrupalDateTime::createFromTimestamp(time());

        $content_entity->set('comment_body', [
          'value' => $this->t(
            'This comment has been archived by a content administrator at @time.',
            [
              '@time' => $now->format('d/m/Y - H:i'),
            ],
            ['context' => 'eic_flags']
          ),
          'format' => 'plain_text',
        ]);
        $content_entity->set('field_comment_is_archived', TRUE);
        $content_entity->save();

        // Reindex user entity to update data like most_active_score.
        $this->solrDocumentProcessor->lateReIndexEntities([$content_entity->getOwner()]);

        // Reindex commented entity to update overview teaser and
        // most_active_score.
        $this->solrDocumentProcessor->reIndexEntities([$content_entity->getCommentedEntity()]);
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
