<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a message creator class for group content.
 */
class GroupContentMessageCreator extends MessageCreatorBase {

  /**
   * Implements hook_group_content_insert().
   *
   * Sends out message notifications upon group content creation.
   */
  public function groupContentInsert(EntityInterface $entity) {
    $messages = [];

    /** @var \Drupal\group\Entity\GroupContent $entity */
    $group_content_type = $entity->getGroupContentType();

    // New member joined notification.
    if ($group_content_type->get('content_plugin') === 'group_membership') {
      $relatedUser = $entity->getEntity();
      $relatedGroup = $entity->getGroup();

      // Prepare the message to the group owner.
      $message = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'notify_new_member_joined',
        'uid' => $relatedGroup->getOwnerId(),
        'field_group_ref' => ['target_id' => $relatedGroup->id()],
        'field_group_membership' => ['target_id' => $entity->id()],
        'field_related_user' => ['target_id' => $relatedUser->id()],
      ]);
      $messages[] = $message;
    }

    // User requested membership notification.
    if ($group_content_type->get('content_plugin') === 'group_membership_request') {
      $relatedUser = $entity->getEntity();
      $relatedGroup = $entity->getGroup();

      // Prepare the message to the group owner.
      $message = \Drupal::entityTypeManager()->getStorage('message')->create([
        'template' => 'notify_new_membership_request',
        'uid' => $relatedGroup->getOwnerId(),
        'field_group_ref' => ['target_id' => $relatedGroup->id()],
        'field_group_membership' => ['target_id' => $entity->id()],
        'field_related_user' => ['target_id' => $relatedUser->id()],
      ]);
      $messages[] = $message;
    }

    $this->processMessages($messages);
  }

  /**
   * Creates an activity stream message for an entity inside a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group having this content.
   * @param string $operation
   *   The type of the operation. See ActivityStreamOperationTypes.
   */
  public function createGroupContentActivity(
    EntityInterface $entity,
    GroupInterface $group,
    string $operation
  ) {
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $message = \Drupal::entityTypeManager()->getStorage('message')->create([
          'template' => $this->getActivityItemTemplate($entity),
          'field_referenced_node' => $entity,
          'field_operation_type' => $operation,
          'field_group_ref' => $group,
        ]);
        break;
    }

    try {
      $message->save();
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('eic_messages');
      $logger->error($e->getMessage());
    }
  }

  /**
   * Checks if an entity has activity message template.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if the entity has activity message template.
   */
  public function hasActivityMessageTemplate(EntityInterface $entity): bool {
    try {
      $this->getActivityItemTemplate($entity);
    }
    catch (\InvalidArgumentException $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets activity message template for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The message template name.
   */
  private function getActivityItemTemplate(EntityInterface $entity): string {
    static $templates = [
      'node' => [
        'discussion' => 'stream_discussion_insert_update',
        'wiki_page' => 'stream_wiki_page_insert_update',
        'document' => 'stream_document_insert_update',
      ],
    ];

    if (!isset($templates[$entity->getEntityTypeId()][$entity->bundle()])) {
      throw new \InvalidArgumentException('Invalid entity / bundle provided');
    }

    return $templates[$entity->getEntityTypeId()][$entity->bundle()];
  }

}
