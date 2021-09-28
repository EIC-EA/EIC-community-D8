<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\group\Entity\GroupContent;
use Drupal\message\Entity\Message;

/**
 * Provides a message creator class for group content.
 *
 * @package Drupal\eic_messages
 */
class NodeMessageCreator extends MessageCreatorBase {

  /**
   * Creates a subscription message for a node with terms of interest.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param string $operation
   *   The type of the operations. See SubscriptionOperationTypes.
   */
  public function createTermsOfInterestNodeSubscription(
    ContentEntityInterface $entity,
    string $operation
  ) {
    $message = NULL;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        $message_type = $operation === SubscriptionOperationTypes::NEW_ENTITY
          ? MessageSubscriptionTypes::NODE_PUBLISHED
          : MessageSubscriptionTypes::NODE_UPDATED;

        // We only create subscription message if the operation is 'created'.
        if (!$message_type) {
          break;
        }

        $message = Message::create([
          'template' => $message_type,
          'field_node_ref' => $entity,
        ]);

        $group_contents = GroupContent::loadByEntity($entity);

        if (!empty($group_contents)) {
          $group_content = reset($group_contents);
          $group = $group_content->getGroup();
          // Adds reference to group.
          $message->set('field_group_ref', $group);
        }

        // Adds the reference to the user who created/updated the entity.
        if ($message->hasField('field_event_executing_user')) {
          $executing_user_id = $entity->getOwnerId();

          $vid = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
            ->getLatestRevisionId($entity->id());

          if ($vid) {
            $latest_revision = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
              ->loadRevision($vid);
            $executing_user_id = $latest_revision->getOwnerId();
          }

          $message->set('field_event_executing_user', $executing_user_id);
        }
        break;

    }

    return $message;
  }

}
