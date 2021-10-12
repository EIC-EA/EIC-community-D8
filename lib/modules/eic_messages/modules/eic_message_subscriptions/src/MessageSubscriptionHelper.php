<?php

namespace Drupal\eic_message_subscriptions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Provides helper methods for message subscriptions.
 *
 * @package Drupal\eic_message_subscriptions
 */
class MessageSubscriptionHelper {

  /**
   * State cache ID that represents a new group content creation.
   */
  const GROUP_CONTENT_CREATED_STATE_KEY = 'eic_message_subscriptions:group_content_created';

  /**
   * Check if an entity can trigger message subscriptions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if the entity can trigger message subscriptions.
   */
  public function isMessageSubscriptionApplicable(EntityInterface $entity) {
    $is_applicable = TRUE;
    $in_group_context = FALSE;

    switch ($entity->getEntityTypeId()) {
      case 'comment':
        // Get commented entity.
        $commented_entity = $entity->getCommentedEntity();

        // If commented entity is not published, then subscription is not
        // applicable.
        if (!$commented_entity->isPublished()) {
          break;
        }

        // Loads group contents for the commented entity.
        $group_contents = GroupContent::loadByEntity($commented_entity);

        if (empty($group_contents)) {
          break;
        }

        $group_content = reset($group_contents);
        $in_group_context = TRUE;
        break;

      case 'group_content':
        $group_content = $entity;
        $in_group_context = TRUE;
        break;
    }

    // If the entity is in the context of a group we need to make sure the
    // group is not in pending or draft state.
    if ($in_group_context && isset($group_content)) {
      $group_content_plugin_id = $group_content->getContentPlugin()->getPluginId();

      // Group content plugins other than group_node cannot trigger
      // notifications.
      if (strpos($group_content_plugin_id, 'group_node:') === FALSE) {
        return FALSE;
      }

      // Group book pages cannot trigger notifications.
      if (strpos($group_content_plugin_id, 'group_node:book') !== FALSE) {
        return FALSE;
      }

      // If entity is not publish, it cannot trigger notifications.
      if (!$group_content->getEntity()->isPublished()) {
        return FALSE;
      }

      $group = $group_content->getGroup();

      $is_applicable = $group->isPublished();
    }

    return $is_applicable;
  }

}
