<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_groups\GroupsModerationHelper;

/**
 * Class GroupMessageCreator.
 */
class GroupMessageCreator extends MessageCreatorBase {

  /**
   * Implements hook_group_insert().
   *
   * Sends out message notifications upon group creation.
   */
  public function groupInsert(ContentEntityInterface $entity) {
    $messages = [];
    $author_id = $entity->get('uid')->getValue()[0]['target_id'];

    // Prepare the message to the requester.
    $message = $this->entityTypeManager->getStorage('message')->create([
      'template' => 'notify_group_requested',
      'uid' => $author_id,
      'field_group_ref' => ['target_id' => $entity->id()],
    ]);
    $messages[] = $message;

    // Prepare messages to SA/CA.
    foreach ($this->eicUserHelper->getSitePowerUsers() as $uid) {
      $message = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'notify_group_request_submitted',
        'uid' => $uid,
        'field_group_ref' => ['target_id' => $entity->id()],
        'field_event_executing_user' => ['target_id' => $author_id],
      ]);
      $messages[] = $message;
    }

    $this->processMessages($messages);
  }

  /**
   * Implements hook_group_update().
   *
   * Sends out message notifications upon group state changes.
   */
  public function groupUpdate(ContentEntityInterface $entity) {
    // Check if state has changed.
    if ($entity->get('moderation_state')->getValue() == $entity->original->get('moderation_state')->getValue()) {
      return;
    }

    $messages = [];
    $author_id = $entity->get('uid')->getValue()[0]['target_id'];

    // Get the current and original Moderation states.
    $current_state = $entity->get('moderation_state')->getValue()[0]['value'];
    $original_state = $entity->original->get('moderation_state')->getValue()[0]['value'];

    // Get the transition.
    $delimiter = '-->';
    $workflow_transition = $original_state . $delimiter . $current_state;

    switch ($workflow_transition) {
      // Group has been approved.
      case GroupsModerationHelper::GROUP_PENDING_STATE . $delimiter . GroupsModerationHelper::GROUP_DRAFT_STATE:
        $message = $this->entityTypeManager->getStorage('message')->create([
          'template' => 'notify_group_request_approved',
          'uid' => $author_id,
          'field_group_ref' => ['target_id' => $entity->id()],
        ]);
        $messages[] = $message;
        break;

    }
    $this->processMessages($messages);
  }

}
