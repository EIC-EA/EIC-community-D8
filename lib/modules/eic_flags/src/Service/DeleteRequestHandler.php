<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;

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
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    switch ($content_entity->getEntityTypeId()) {
      case 'group':
        /** @var GroupInterface $content_entity */
        $this->deleteGroup($content_entity);
        break;
      case 'node':
      case 'comment':
        $content_entity->delete();
        break;
    }
  }

  /**
   * Denies the request but un-publish the entity instead
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function archive(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    if ($this->moderationInformation->isModeratedEntity($content_entity)) {
      // TODO think about looping through every workflow to find an 'unpublished' state
      $state = $content_entity->getEntityTypeId() === 'group' ? 'pending' : 'unpublished';
      $content_entity->set('moderation_state', $state);
    }
    else {
      $content_entity->set('status', FALSE);
    }

    $content_entity->save();
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

  /**
   * @param \Drupal\group\Entity\GroupInterface $group
   */
  private function deleteGroup(GroupInterface $group) {
    // Retrieve group content entities linked to the group
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
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   * @param \Drupal\group\Entity\GroupInterface $group
   * @param $context
   */
  public static function deleteGroupContent(
    GroupContentInterface $group_content,
    GroupInterface $group,
    &$context
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
    } catch (\Exception $exception) {
      $context['results']['errors'][] = t(
        'Something went wrong during content removal @error',
        ['@error' => $exception->getMessage()]
      );
    }
  }

  /**
   * @param bool $success
   * @param array $results
   * @param array $operations
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

    if (isset($results['group'])
      && $results['group'] instanceof GroupInterface) {
      $results['group']->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getActions(ContentEntityInterface $entity) {
    return parent::getActions($entity) + [
        'archive_request' => [
          'title' => t('Archive'),
          'url' => $entity->toUrl('close-request')
            ->setRouteParameter('request_type', $this->getType())
            ->setRouteParameter('response', RequestStatus::ARCHIVED)
            ->setRouteParameter(
              'destination',
              \Drupal::request()
                ->getRequestUri()
            ),
        ],
      ];
  }

}
