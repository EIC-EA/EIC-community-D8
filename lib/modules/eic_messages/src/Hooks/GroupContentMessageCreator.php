<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class GroupContentMessageCreator.
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
   */
  public function createGroupContentActivity(EntityInterface $entity) {
    $messages = [];

    switch ($entity->getEntityTypeId()) {
      case 'node':
        switch ($entity->bundle()) {
          case 'discussion':
            // @todo Handle all activity messages with correct values.
            $messages[] = \Drupal::entityTypeManager()->getStorage('message')->create([
              'template' => 'stream_discussion_insert_update',
            ]);
            break;
        }
        break;
    }

    // Save all messages.
    foreach ($messages as $message) {
      try {
        $message->save();
      }
      catch (\Exception $e) {
        $logger = $this->getLogger('eic_messages');
        $logger->error($e->getMessage());
      }
    }

  }

}
