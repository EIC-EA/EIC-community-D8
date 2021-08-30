<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\Constants\NodeProperty;
use Drupal\eic_user\UserHelper;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Plugin\GroupContentAccessControlHandler;

/**
 * Provides access control for GroupContent entities and grouped entities.
 */
class GroupContentNodeAccessControlHandler extends GroupContentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    $access = parent::entityAccess($entity, $operation, $account, $return_as_object);

    switch ($operation) {
      case 'update':
        /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage('group_content');
        $group_contents = $storage->loadByEntity($entity);

        // We check if the user is a member of a group where this node is
        // referenced and if so, we allow access to edit the node if the owner
        // allowed members to do so via "member_content_edit_access" property.
        foreach ($group_contents as $group_content) {
          $group = $group_content->getGroup();

          $editable_by_members = $entity->get(NodeProperty::MEMBER_CONTENT_EDIT_ACCESS)->value;

          if ($editable_by_members) {
            if (UserHelper::isPowerUser($account)) {
              $access = GroupAccessResult::allowed()
                ->addCacheableDependency($account)
                ->addCacheableDependency($entity);
              break;
            }

            $membership = $group->getMember($account);

            $access = AccessResult::allowedIf($membership)
              ->addCacheableDependency($account)
              ->addCacheableDependency($membership)
              ->addCacheableDependency($entity);
            break;
          }
        }
        break;

    }

    return $access;
  }

}
