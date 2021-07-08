<?php

namespace Drupal\oec_group_comments\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\Controller\CommentController;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends base controller for the comment entity.
 */
class GroupCommentController extends CommentController {

  /**
   * The group permission checker.
   *
   * @var \Drupal\oec_group_comments\GroupPermissionChecker
   */
  protected $groupPermissionChecker;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = parent::create($container);
    $controller->groupPermissionChecker = $container->get('oec_group_comments.group_permission_checker');
    return $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function replyFormAccess(EntityInterface $entity, $field_name, $pid = NULL) {
    $access = parent::replyFormAccess($entity, $field_name, $pid);

    if (!($entity instanceof ContentEntityInterface)) {
      return $access;
    }

    $group_contents = GroupContent::loadByEntity($entity);

    // If entity is not a group content, we don't need extra logic.
    if (empty($group_contents)) {
      return $access;
    }

    $account = $this->currentUser();

    // If user doesn't have permissions to post comments in a group we deny
    // access to the reply form.
    if ($this->groupPermissionChecker->getPermissionInGroups('post comments', $account, $group_contents)->isForbidden()) {
      $group_content = reset($group_contents);
      $access = AccessResult::forbidden()
        ->cachePerPermissions()
        ->addCacheableDependency($entity)
        ->addCacheableDependency($group_content->getGroup());
    }

    return $access;
  }

}
