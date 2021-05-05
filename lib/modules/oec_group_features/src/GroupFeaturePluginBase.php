<?php

namespace Drupal\oec_group_features;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Base class for group_feature plugins.
 */
abstract class GroupFeaturePluginBase extends PluginBase implements GroupFeatureInterface {

  use LoggerChannelTrait;
  use MessengerTrait;

  /**
   * The menu link storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkContentStorage;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * Creates a new GroupFeaturesHelper object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->entityRepository = \Drupal::service('entity.repository');
    $this->groupPermissionsManager = \Drupal::service('group_permission.group_permissions_manager');
  }

  /**
   * Enables the a menu item (and creates it if necessary).
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_item
   *   The menu item to create.
   *
   * @return bool
   *   TRUE if the menu item could be enabled or created, FALSE otherwise.
   */
  protected function enableMenuItem(MenuLinkContent $menu_item) {
    // First check if an item with the same uri already exists in the target
    // menu.
    if ($existing_menu_item = $this->getExistingMenuItem($menu_item)) {
      // Make sure it is enabled.
      $existing_menu_item->enabled->value = 1;
      if ($this->saveMenuItem($existing_menu_item)) {
        return TRUE;
      }
    }
    else {
      // Make sure it is enabled.
      $menu_item->enabled->value = 1;
      if ($this->saveMenuItem($menu_item)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Disables a menu item.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_item
   *   The menu item to disable.
   *
   * @return bool
   *   Returns TRUE is menu item was disabled or non-existing, FALSE in case of
   *   error.
   */
  protected function disableMenuItem(MenuLinkContent $menu_item) {
    if ($existing_menu_item = $this->getExistingMenuItem($menu_item)) {
      $existing_menu_item->enabled->value = 0;
      if (!$this->saveMenuItem($existing_menu_item)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Returns the GroupPermission object for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which the GroupPermission object should be returned.
   *
   * @return \Drupal\group\Entity\GroupPermissiontInterface|null
   *   The GroupPermission object.
   */
  protected function getGroupPermissions(GroupInterface $group) {
    return $this->groupPermissionsManager->loadByGroup($group);
  }

  /**
   * Saves the GroupPermission object.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermission $group_permissions
   *   The GroupPermission object to be saved.
   */
  protected function saveGroupPermissions(GroupPermission $group_permissions) {
    $violations = $group_permissions->validate();

    if (count($violations) > 0) {
      $message = '';
      foreach ($violations as $violation) {
        $message .= "\n" . $violation->getMessage();
      }
      throw new EntityStorageException('Group permissions are not saved correctly, because:' . $message);
    }

    // Saves the GroupPermission object with a new revision.
    $group_permissions->setNewRevision();
    $group_permissions->setRevisionUserId(\Drupal::currentUser()->id());
    $group_permissions->setRevisionCreationTime(\Drupal::service('datetime.time')->getRequestTime());
    $group_permissions->setRevisionLogMessage('Group features enabled/disabled.');
    $group_permissions->save();
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
   *   The unsaved group permission object with the updated permissions.
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
   *   The unsaved group permission object with the updated permissions.
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
   * Get group outsider drupal roles.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type entity.
   * @param array $internal_rids
   *   The outsider role id.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The outsider roles of the group.
   */
  protected function getOutsiderRolesFromInternalRoles(GroupTypeInterface $groupType, array $internal_rids): array {
    $roles = [];
    $group_roles = $groupType->getRoles();

    if (empty($group_roles)) {
      return $roles;
    }

    foreach ($group_roles as $role) {
      foreach ($internal_rids as $key => $internal_rid) {
        if ($role->isInternal() && in_array("user.role.{$internal_rid}", $role->getDependencies()['config'])) {
          $roles[] = $role;
          // We unset the role from $internal_rids array to avoid redundant
          // checks.
          unset($internal_rids[$key]);
          break;
        }
      }
    }
    return $roles;
  }

  /**
   * Returns the existing menu item.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_item
   *   The (unsaved) menu item to work with.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|false
   *   The existing menu item of FALSE if it doesn't exist yet.
   */
  private function getExistingMenuItem(MenuLinkContent $menu_item) {
    $items = $this->menuLinkContentStorage->loadByProperties([
      'menu_name' => $menu_item->getMenuName(),
      'link__uri' => 'route:' . $menu_item->getUrlObject()->getRouteName(),
    ]);
    if (!empty($items)) {
      return reset($items);
    }
    return FALSE;
  }

  /**
   * Saves a menu item into a GroupContentMenu based on provided arguments.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_item
   *   The menu item to save.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|false
   *   The saved menu item.
   */
  private function saveMenuItem(MenuLinkContent $menu_item) {
    try {
      $menu_item->save();
      return $menu_item;
    }
    catch (EntityStorageException $e) {
      $logger = $this->getLogger('oec_group_features');
      $logger->error($e->getMessage());
      $this->messenger()->addError('An error has occurred. Please contact the site administrators.');
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
