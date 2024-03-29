<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;

/**
 * Provides a delete request handler.
 *
 * @package Drupal\eic_flags\Service
 */
class DeleteRequestHandler extends AbstractRequestHandler {

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
    return RequestTypes::DELETE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    return [
      RequestStatus::OPEN => 'notify_new_deletion_request',
      RequestStatus::DENIED => 'notify_delete_request_denied',
      RequestStatus::ACCEPTED => 'notify_delete_request_accepted',
      RequestStatus::ARCHIVED => 'notify_delete_request_archived',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function canRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  ) {
    // If user can delete the entity, there is no need to allow deletion request.
    if ($entity->access('delete', $account)) {
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
    switch ($content_entity->getEntityTypeId()) {
      case 'group':
        /** @var \Drupal\group\Entity\GroupInterface $content_entity */
        $this->deleteGroup($content_entity);
        break;

      case 'node':
        $content_entity->delete();
        break;

      case 'comment':
        $now = DrupalDateTime::createFromTimestamp(time());
        $content_entity->set('comment_body', [
          'value' => $this->t(
            'This comment has been removed by a content administrator at @time.',
            ['@time' => $now->format('d/m/Y - H:i')],
            ['context' => 'eic_flags']
          ),
          'format' => 'plain_text',
        ]);
        $content_entity->set('field_comment_is_soft_deleted', TRUE);
        $content_entity->save();

        // Reindex user entity to update data like most_active_score.
        $this->solrDocumentProcessor->lateReIndexEntities([$content_entity->getOwner()]);

        // Reindex commented entity to update overview teaser and
        // most_active_score.
        $this->solrDocumentProcessor->reIndexEntities([$content_entity->getCommentedEntity()]);
        break;

    }
  }

  /**
   * Denies the request but un-publish the entity instead.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flag object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   */
  public function archive(
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
      $content_entity->set('status', FALSE);
    }

    $content_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    return [
      'node' => 'request_delete_content',
      'group' => 'request_delete_group',
      'comment' => 'request_delete_comment',
    ];
  }

  /**
   * Deletes the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The concerned group.
   */
  private function deleteGroup(GroupInterface $group) {
    // Retrieve group content entities linked to the group.
    $group_contents = $group->getContent();
    $batch_builder = (new BatchBuilder())
      ->setFinishCallback(
        [
          DeleteRequestHandler::class,
          'deleteGroupContentFinished',
        ]
      )
      ->setTitle(
        $this->t('Deleting group @group', ['@group' => $group->label()])
      );

    foreach ($group_contents as $group_content) {
      $batch_builder->addOperation(
        [
          DeleteRequestHandler::class,
          'deleteGroupContent',
        ],
        [$group_content, $group]
      );
    }

    batch_set($batch_builder->toArray());
  }

  /**
   * Deletes the given group content.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The concerned group content.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group having this content.
   * @param array $context
   *   The context of the batch.
   */
  public static function deleteGroupContent(
    GroupContentInterface $group_content,
    GroupInterface $group,
    array &$context
  ) {
    if (!isset($context['results']['errors'])) {
      $context['results']['errors'] = [];
    }

    if (!isset($context['results']['group'])) {
      $context['results']['group'] = $group;
    }

    try {
      // Clean up everything!
      $target_entity = $group_content->getEntity();
      $group_content->delete();

      if ($target_entity instanceof UserInterface) {
        $group->removeMember($target_entity);
        // Job done. We don't delete target if it's a user.
        return;
      }

      if ($target_entity instanceof ContentEntityInterface) {
        $target_entity->delete();
      }
    }
    catch (\Exception $exception) {
      $context['results']['errors'][] = new TranslatableMarkup(
        'Something went wrong during content removal @error',
        ['@error' => $exception->getMessage()]
      );
    }
  }

  /**
   * Handles the batch finish.
   *
   * @param bool $success
   *   Result of the operation.
   * @param array $results
   *   Deleted entities.
   * @param array $operations
   *   The array of operations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function deleteGroupContentFinished(
    bool $success,
    array $results,
    array $operations
  ) {
    $messenger = \Drupal::messenger();
    if (!$success) {
      $error_operation = reset($operations);
      $message = t(
        'An error occurred while processing %error_operation with arguments: @arguments',
        [
          '%error_operation' => $error_operation[0],
          '@arguments' => print_r(
            $error_operation[1],
            TRUE
          ),
        ]
      );

      $messenger->addError($message);

      return;
    }

    if (!empty($results['results']['errors'])) {
      foreach ($results['results']['errors'] as $error) {
        $messenger->addError($error);
      }

      return;
    }

    if (isset($results['group']) && $results['group'] instanceof GroupInterface) {
      $results['group']->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getActions(ContentEntityInterface $entity) {
    return parent::getActions($entity) + [
      'archive_request' => [
        'title' => $this->t('Archive'),
        'url' => $entity->toUrl('close-request')
          ->setRouteParameter('request_type', $this->getType())
          ->setRouteParameter('response', RequestStatus::ARCHIVED)
          ->setRouteParameter('destination', $this->currentRequest->getRequestUri()),
      ],
    ];
  }

}
