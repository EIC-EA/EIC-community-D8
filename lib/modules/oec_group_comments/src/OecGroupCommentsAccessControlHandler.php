<?php

namespace Drupal\oec_group_comments;

use Drupal\Core\Access\AccessResult;
use Drupal\comment\CommentAccessControlHandler;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the comment entity type.
 */
class OecGroupCommentsAccessControlHandler extends CommentAccessControlHandler implements EntityHandlerInterface {

  /**
   * The group permission checker.
   *
   * @var \Drupal\oec_group_comments\GroupPermissionChecker
   */
  protected $groupPermissionChecker;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('oec_group_comments.group_permission_checker')
    );
  }

  /**
   * Constructs a new CommentAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\oec_group_comments\GroupPermissionChecker $groupPermissionChecker
   *   The group permission checker.
   */
  public function __construct(EntityTypeInterface $entity_type, GroupPermissionChecker $groupPermissionChecker) {
    parent::__construct($entity_type);
    $this->groupPermissionChecker = $groupPermissionChecker;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\comment\CommentInterface $entity */
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
    $reply_count = $this->commentReplyCount($entity->id(), $commented_entity->id(), (int) $commented_entity->getEntityTypeId());

    // Fallback.
    $access = AccessResult::neutral();

    switch ($operation) {
      case 'view':
        $access = $this->groupPermissionChecker->getPermissionInGroups('view comments', $account, $group_contents);
        break;

      case 'update':
        if ($entity->getOwnerId() == $account->id() && $reply_count == 0) {
          $access = $this->groupPermissionChecker->getPermissionInGroups('edit own comments', $account, $group_contents);
        }
        if ($this->groupPermissionChecker->getPermissionInGroups('edit all comments', $account, $group_contents)
          ->isAllowed()) {
          $access = AccessResult::allowed();
        }
        break;

      case 'delete':
        // The 'Request Deletion' workflow will be implemented with EICNET-745.
        // For now deleting a comment is completely disabled for other
        // permissions than administer_comments.
        $access = AccessResult::forbidden('Deleting is not allowed for users who don\'t have administer_comments');
        break;

      default:
        // No opinion.
        $access = AccessResult::neutral();
    }

    return $access;
  }

  /**
   * Helper function to fetch comment reply count.
   *
   * @param int $cid
   *   Entity ID.
   * @param int $nid
   *   Commented Entity ID.
   * @param int $entity_type
   *   Commented Entity Type ID.
   *
   * @return int
   *   Number of replies for comment.
   */
  protected function commentReplyCount($cid, $nid, $entity_type): int {
    $result = \Drupal::entityQuery('comment')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $nid)
      ->condition('pid', $cid)
      ->count()
      ->execute();

    return (int) $result;
  }

}
