<?php

namespace Drupal\eic_group_membership\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permissions_manager
   *   The group permissions manager.
   */
  public function __construct(GroupPermissionsManagerInterface $group_permissions_manager) {
    $this->groupPermissionsManager = $group_permissions_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_permission.group_permissions_manager')
    );
  }

  /**
   * Implements hook_group_insert().
   */
  public function groupInsert(EntityInterface $entity) {
    $this->updateGroupLeavePermissions($entity);
  }

  /**
   * Implements hook_group_update().
   */
  public function groupUpdate(GroupInterface $entity) {
    $this->updateGroupLeavePermissions($entity);
  }

  /**
   * Adds/deletes group "delete memberships" permission from GO/GA.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   */
  private function updateGroupLeavePermissions(GroupInterface $entity) {
    /** @var \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permissions */
    $group_permissions = $this->groupPermissionsManager->loadByGroup($entity);
    $permissions = $group_permissions->getPermissions();
    $roles = [
      EICGroupsHelper::getGroupTypeRole(
        $entity->bundle(),
        EICGroupsHelper::GROUP_TYPE_OWNER_ROLE
      ),
      EICGroupsHelper::getGroupTypeRole(
        $entity->bundle(),
        EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE
      ),
      EICGroupsHelper::getGroupTypeRole(
        $entity->bundle(),
        EICGroupsHelper::GROUP_TYPE_MEMBER_ROLE
      ),
    ];

    if (
      !EICGroupsHelper::isSmedGroup($entity->original) &&
      EICGroupsHelper::isSmedGroup($entity)
    ) {
      foreach ($roles as $role) {
        if ($key = array_search('leave group', $permissions[$role])) {
          unset($permissions[$role][$key]);
        }
      }
      $group_permissions->setPermissions($permissions);
    }
    elseif (
      EICGroupsHelper::isSmedGroup($entity->original) &&
      !EICGroupsHelper::isSmedGroup($entity)
    ) {
      foreach ($roles as $role) {
        if (!array_search('leave group', $permissions[$role])) {
          $permissions[$role][] = 'leave group';
        }
      }
      $group_permissions->setPermissions($permissions);
    }
    else {
      return;
    }

    $violations = $group_permissions->validate();
    if (count($violations) > 0) {
      $message = '';
      foreach ($violations as $violation) {
        $message .= "\n" . $violation->getMessage();
      }
      throw new EntityStorageException('Group permissions are not saved correctly, because:' . $message);
    }
    $group_permissions->setNewRevision();
    $group_permissions->setRevisionUserId(\Drupal::currentUser()->id());
    $group_permissions->setRevisionCreationTime(\Drupal::service('datetime.time')
      ->getRequestTime());
    $group_permissions->setRevisionLogMessage('Group features enabled/disabled.');
    $group_permissions->save();
  }

}
