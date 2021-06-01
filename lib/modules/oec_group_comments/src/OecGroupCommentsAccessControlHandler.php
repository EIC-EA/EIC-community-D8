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
    $group_contents = GroupContent::loadByEntity($commented_entity);

    // Check for 'delete all comments' permission in case content is not from
    // group.
    if (empty($group_contents) && $account->hasPermission('delete all comments')) {
      $administer_access = AccessResult::allowed();
    }
    else {
      $administer_access = $this->getPermissionInGroups('administer comments', $account, $group_contents);
    }

    if ($administer_access->isAllowed()) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($entity->getCommentedEntity()
        ->access($operation, $account, TRUE));
    }

    // If there are replies to the comment, disable 'can_edit'.
    $reply_count = intval($this->comment_reply_count($entity->id(), $commented_entity->id(), $commented_entity->getEntityTypeId()));

    // Fallback.
    $access = AccessResult::neutral();

    // Only react if it is actually posted inside a group.
    if (!empty($group_contents)) {
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
          // For now only 'direct deleting' is implemented.
          // This can be turned of by simple uncommenting the following line:
          //            $access = AccessResult::forbidden('Deleting is not allowed for users who don\'t have administer_comments'); break;

          if ($entity->getOwnerId() == $account->id() && $reply_count == 0) {
            $access = $this->getPermissionInGroups('request delete own comments', $account, $group_contents);
          }
          if ($this->getPermissionInGroups('request delete all comments', $account, $group_contents)
            ->isAllowed()) {
            $access = AccessResult::allowed();
          }
          break;

        default:
          // No opinion.
          $access = AccessResult::neutral();
      }
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
  protected function comment_reply_count($cid, $nid, $entity_type) {
    $result = \Drupal::entityQuery('comment')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $nid)
      ->condition('pid', $cid)
      ->count()
      ->execute();
    return $result;
  }

}
