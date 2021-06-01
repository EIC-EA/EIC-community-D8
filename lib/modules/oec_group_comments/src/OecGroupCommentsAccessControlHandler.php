<?php

namespace Drupal\oec_group_comments;

use Drupal\Core\Access\AccessResult;
use Drupal\comment\CommentAccessControlHandler;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\comment\Entity\Comment
 *
 * @todo Implement setting to make it possible overridden on per-group basis.
 */
class OecGroupCommentsAccessControlHandler extends CommentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\comment\CommentInterface|\Drupal\user\EntityOwnerInterface $entity */

    $commented_entity = $entity->getCommentedEntity();
    if (!($commented_entity instanceof ContentEntityInterface)) {
      return AccessResult::neutral();
    }

    // If user has 'administer comments', the user
    // is always allowed to do everything with a comment.
    if ($account->hasPermission('administer comments')) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($entity->getCommentedEntity()
        ->access($operation, $account, TRUE));
    }

    $group_contents = GroupContent::loadByEntity($commented_entity);
    $parent_access = parent::checkAccess($entity, $operation, $account);

    // If not posted in a group, don't bother doing the logic for it.
    if (empty($group_contents)) {
      return $parent_access;
    }

    // If there are replies to the comment, disable 'can_edit'.
    $reply_count = intval($this->commentReplyCount($entity->id(), $commented_entity->id(), $commented_entity->getEntityTypeId()));

    // Fallback.
    $access = AccessResult::neutral();

    switch ($operation) {
      case 'view':
        $access = $this->getPermissionInGroups('view comments', $account, $group_contents);
        break;

      case 'update':
        if ($entity->getOwnerId() == $account->id() && $reply_count == 0) {
          $access = $this->getPermissionInGroups('edit own comments', $account, $group_contents);
        }
        if ($this->getPermissionInGroups('edit all comments', $account, $group_contents)
          ->isAllowed()) {
          $access = AccessResult::allowed();
        }
        break;

      case 'delete':
        // The 'Request Deletion' workflow will be implemented with EICNET-745.
        // For now deleting a comment is completely disabled for other permissions than administer_comments.
        $access = AccessResult::forbidden('Deleting is not allowed for users who don\'t have administer_comments');
        break;

      default:
        // No opinion.
        $access = AccessResult::neutral();
    }

    return $access;
  }

  /**
   * Checks if account was granted permission in group.
   */
  protected function getPermissionInGroups($perm, AccountInterface $account, $group_contents) {

    // Only when you have permission to view the comments.
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContent $group_content */
      $group = $group_content->getGroup();
      /** @var \Drupal\group\Entity\Group $group */
      if ($group->hasPermission($perm, $account)) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    // Fallback.
    return AccessResult::forbidden()->cachePerUser();
  }

  /**
   * Helper function to fetch Comment reply count.
   *
   * Ripped from the module comment_reply_count.
   */
  protected function commentReplyCount($cid, $nid, $entity_type) {
    $result = \Drupal::entityQuery('comment')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $nid)
      ->condition('pid', $cid)
      ->count()
      ->execute();
    return $result;
  }

}
