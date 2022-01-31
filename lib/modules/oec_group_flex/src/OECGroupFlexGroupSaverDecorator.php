<?php

namespace Drupal\oec_group_flex;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_search\Service\SolrDocumentProcessor;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\GroupFlexGroup;
use Drupal\group_flex\GroupFlexGroupSaver;
use Drupal\group_flex\Plugin\GroupJoiningMethodManager;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\GroupPermissionsManager;

/**
 * Saving of a Group to implement the correct group type permissions.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 */
class OECGroupFlexGroupSaverDecorator extends GroupFlexGroupSaver {

  /**
   * The group flex saver service.
   *
   * @var \Drupal\group_flex\GroupFlexGroupSaver
   */
  protected $groupFlexSaver;

  /**
   * The group visibility storage service.
   *
   * @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface
   */
  protected $groupVisibilityStorage;

  /**
   * The EIC Search Solr Document Processor.
   *
   * @var \Drupal\eic_search\Service\SolrDocumentProcessor
   */
  private $solrDocumentProcessor;

  /**
   * Constructs a new GroupFlexGroupSaver object.
   *
   * @param \Drupal\group_flex\GroupFlexGroupSaver $groupFlexSaver
   *   The group flex saver inner service.
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage
   *   The group visibility storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManager $groupPermManager
   *   The group permissions manager.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $visibilityManager
   *   The group visibility manager.
   * @param \Drupal\group_flex\Plugin\GroupJoiningMethodManager $joiningMethodManager
   *   The group joining method manager.
   * @param \Drupal\group_flex\GroupFlexGroup $groupFlex
   *   The group flex.
   */
  public function __construct(
    GroupFlexGroupSaver $groupFlexSaver,
    GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage,
    EntityTypeManagerInterface $entityTypeManager,
    GroupPermissionsManager $groupPermManager,
    GroupVisibilityManager $visibilityManager,
    GroupJoiningMethodManager $joiningMethodManager,
    GroupFlexGroup $groupFlex
  ) {
    parent::__construct($entityTypeManager, $groupPermManager,
      $visibilityManager, $joiningMethodManager, $groupFlex);
    $this->groupFlexSaver = $groupFlexSaver;
    $this->groupVisibilityStorage = $groupVisibilityStorage;
  }

  /**
   * @param \Drupal\eic_search\Service\SolrDocumentProcessor|null $document_processor
   */
  public function setDocumentProcessor(?SolrDocumentProcessor $document_processor) {
    $this->solrDocumentProcessor = $document_processor;
  }

  /**
   * Save the group visibility.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to save.
   * @param string $groupVisibility
   *   The desired visibility of the group.
   * @param array $groupVisibilityOptions
   *   The group visibility options array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveGroupVisibility(
    GroupInterface $group,
    string $groupVisibility,
    array $groupVisibilityOptions = []
  ) {
    $groupPermission = $this->getGroupPermissionObject($group);

    if (!$groupPermission) {
      return;
    }

    $visibilityPlugins = $this->getAllGroupVisibility();

    // Clears group role permissions.
    foreach ($visibilityPlugins as $id => $pluginInstance) {
      /** @var \Drupal\group_flex\Plugin\GroupVisibilityBase $pluginInstance */
      if ($groupVisibility !== $id) {
        foreach ($pluginInstance->getDisallowedGroupPermissions($group) as $role => $rolePermissions) {
          $groupPermission = $this->removeRolePermissionsFromGroup(
            $groupPermission,
            $role, $rolePermissions
          );
        }
      }
    }

    // Adds role permissions to the group based on the selected visibility
    // plugin.
    if (isset($visibilityPlugins[$groupVisibility])) {
      foreach ($visibilityPlugins[$groupVisibility]->getGroupPermissions($group) as $role => $rolePermissions) {
        $groupPermission = $this->addRolePermissionsToGroup(
          $groupPermission,
          $role,
          $rolePermissions
        );
      }
    }

    $violations = $groupPermission->validate();
    if (count($violations) > 0) {
      $message = '';
      foreach ($violations as $violation) {
        $message .= "\n" . $violation->getMessage();
      }
      throw new EntityStorageException('Group permissions are not saved correctly, because:' . $message);
    }
    $groupPermission->save();

    if (!($item = $this->groupVisibilityStorage->load($group->id()))) {
      $item = $this->groupVisibilityStorage->create([
        'id' => 0,
        'gid' => (int) $group->id(),
        'type' => $groupVisibility,
      ]);
    }

    $oldVisibilityOptions = $item->getOptions();
    $item->setType($groupVisibility);
    $item->setOptions($groupVisibilityOptions);

    $this->groupVisibilityStorage->save($item);

    // Invalidates group cache tags.
    Cache::invalidateTags($group->getCacheTagsToInvalidate());

    // If group visibility changed we need to reupdate all group contents.
    // These re-index logic is on 2 different places because of visibility
    // field not instance of FieldItemInterface so we need to compare it here.
    if (
      Json::encode($groupVisibilityOptions) !==
      Json::encode($oldVisibilityOptions)
    ) {
      $this->solrDocumentProcessor->reIndexEntitiesFromGroup($group);
    }

    return $groupPermission;
  }

  /**
   * Get the groupPermission object, will create a new one if needed.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to get the group permission object for.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission|null
   *   The (new) group permission object, returns NULL if something went wrong.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function getGroupPermissionObject(GroupInterface $group): ?GroupPermission {
    /** @var \Drupal\group_permissions\Entity\GroupPermission $groupPermission */
    $groupPermission = $this->groupPermManager->loadByGroup($group);

    if (!$groupPermission instanceof GroupPermissionInterface) {
      // Create the entity.
      $groupPermission = GroupPermission::create([
        'gid' => $group->id(),
        'permissions' => $this->getDefaultGroupTypePermissions($group->getGroupType()),
        'status' => 1,
      ]);
    }
    else {
      $groupPermission->setNewRevision(TRUE);
    }

    return $groupPermission;
  }

  /**
   * Remove role permissions from the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermission $groupPermission
   *   The group permission object to set the permissions to.
   * @param string $role
   *   The role to remove the permissions from.
   * @param array $rolePermissions
   *   The permissions to remove from the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission
   *   The group permission object with the updated permissions.
   */
  protected function removeRolePermissionsFromGroup(GroupPermission $groupPermission, string $role, array $rolePermissions): GroupPermission {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (array_key_exists($role, $permissions) || in_array($permission, $permissions[$role], TRUE)) {
        $permissions[$role] = array_diff($permissions[$role], [$permission]);
      }
    }
    $groupPermission->setPermissions($permissions);
    return $groupPermission;
  }

  /**
   * Add role permissions to the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermission $groupPermission
   *   The group permission object to add the permissions to.
   * @param string $role
   *   The role to add the permissions to.
   * @param array $rolePermissions
   *   The permissions to add to the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission
   *   The group permission object with the updated permissions.
   */
  protected function addRolePermissionsToGroup(GroupPermission $groupPermission, string $role, array $rolePermissions): GroupPermission {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (!array_key_exists($role, $permissions) || !in_array($permission, $permissions[$role], TRUE)) {
        $permissions[$role][] = $permission;
      }
    }
    $groupPermission->setPermissions($permissions);
    return $groupPermission;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->groupFlexSaver, $method], $args);
  }

  /**
   * Save the group joining methods.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to save.
   * @param array $joiningMethods
   *   The desired joining methods of the group.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function saveGroupJoiningMethods(
    GroupInterface $group,
    array $joiningMethods
  ) {
    $groupPermission = $this->getGroupPermissionObject($group);

    if (!$groupPermission) {
      return;
    }

    /** @var \Drupal\group_flex\Plugin\GroupJoiningMethodBase $pluginInstance */
    foreach ($this->getAllJoiningMethods() as $id => $pluginInstance) {
      // Checks if the method is enabled.
      $isEnabled = in_array($id, $joiningMethods, TRUE) && $joiningMethods[$id] === $id;
      // Checks if the method is allowed for the group's visibility.
      $allowedVisibilities = $pluginInstance->getVisibilityOptions();
      $isAllowed = in_array($this->groupFlex->getGroupVisibility($group), $allowedVisibilities, TRUE);
      if ($isEnabled && $isAllowed) {
        foreach ($pluginInstance->getGroupPermissions($group) as $role => $rolePermissions) {
          $groupPermission = $this->addRolePermissionsToGroup($groupPermission, $role, $rolePermissions);
        }
        continue;
      }

      if (empty($pluginInstance->getDisallowedGroupPermissions($group))) {
        continue;
      }
      foreach ($pluginInstance->getDisallowedGroupPermissions($group) as $role => $rolePermissions) {
        $groupPermission = $this->removeRolePermissionsFromGroup($groupPermission, $role, $rolePermissions);
      }
    }

    $violations = $groupPermission->validate();
    if (count($violations) > 0) {
      $message = '';
      foreach ($violations as $violation) {
        $message .= "\n" . $violation->getMessage();
      }
      throw new EntityStorageException('Group permissions are not saved correctly, because:' . $message);
    }
    $groupPermission->save();

    // Invalidates group cache tags.
    Cache::invalidateTags($group->getCacheTagsToInvalidate());
  }

}
