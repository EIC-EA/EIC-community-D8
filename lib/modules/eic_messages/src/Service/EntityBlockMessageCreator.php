<?php

namespace Drupal\eic_messages\Service;

use Drupal\eic_flags\BlockFlagTypes;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_messages\Util\NotificationMessageTemplates;
use Drupal\flag\FlaggingInterface;

/**
 * Class for creating messages notifications when an entity is blocked.
 */
class EntityBlockMessageCreator extends MessageCreatorBase {

  /**
   * Creates a notification message to be sent after blocking an entity.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flag associated with the block operation.
   */
  public function createBlockEntityNotification(
    FlaggingInterface $flagging
  ) {
    $supported_entity_types = BlockFlagTypes::getSupportedEntityTypes();

    if (!in_array($flagging->getFlagId(), array_values($supported_entity_types))) {
      return;
    }

    $entity = $flagging->getFlaggable();
    $to = [];

    switch ($entity->getEntityTypeId()) {
      case 'group':
        $owners = $entity->getMembers(EICGroupsHelper::GROUP_OWNER_ROLE);

        // If group has no owner, we don't send out any notification.
        if (empty($owners)) {
          return;
        }

        // We need to map the membership into an array of user entities.
        $to = array_map(
          function ($owner) {
            return $owner->getUser();
          },
          $owners
        );

        break;

      default:
        $to[] = $entity->getOwner();
        break;
    }

    $messages = [];
    foreach ($to as $user) {
      $message = $this->entityTypeManager->getStorage('message')->create(
        [
          'template' => NotificationMessageTemplates::ENTITY_BLOCKED,
          'field_referenced_flag' => $flagging,
          'uid' => $user->id(),
        ]
      );

      // Adds the reference to the user who blocked the entity.
      if ($message->hasField('field_event_executing_user')) {
        $message->set('field_event_executing_user', $flagging->getOwnerId());
      }

      $messages[] = $message;
    }

    $this->processMessages($messages);
  }

}
