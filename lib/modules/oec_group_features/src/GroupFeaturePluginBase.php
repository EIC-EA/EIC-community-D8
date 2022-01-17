<?php

namespace Drupal\oec_group_features;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\GroupPermissionsManager;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for group_feature plugins.
 */
abstract class GroupFeaturePluginBase extends PluginBase implements GroupFeatureInterface, ContainerFactoryPluginInterface {

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
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManager
   */
  protected $groupPermissionsManager;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group\GroupRoleSynchronizer
   */
  protected $groupRoleSynchronizer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Creates a new GroupFeaturePluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepository $entity_repository
   *   The entity repository.
   * @param \Drupal\group_permissions\GroupPermissionsManager $group_permissions_manager
   *   The group permissions manager.
   * @param \Drupal\group\GroupRoleSynchronizer $group_role_synchronizer
   *   The group permissions manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entity_type_manager,
    EntityRepository $entity_repository,
    GroupPermissionsManager $group_permissions_manager,
    GroupRoleSynchronizer $group_role_synchronizer,
    ConfigFactory $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->entityRepository = $entity_repository;
    $this->groupPermissionsManager = $group_permissions_manager;
    $this->groupRoleSynchronizer = $group_role_synchronizer;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('group_permission.group_permissions_manager'),
      $container->get('group_role.synchronizer'),
      $container->get('config.factory')
    );
  }

  /**
   * Enables the a menu item (and creates it if necessary).
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_item
   *   The menu item to create.
   *
   * @return bool
   *   TRUE if the menu item could be enabled or created, FALSE otherwise.
   */
  protected function enableMenuItem(MenuLinkContentInterface $menu_item) {
    // First check if an item with the same uri already exists in the target
    // menu.
    if ($existing_menu_item = $this->getExistingMenuItem($menu_item)) {
      // Make sure it is enabled.
      $existing_menu_item->set('enabled', TRUE);
      if ($this->saveMenuItem($existing_menu_item)) {
        return TRUE;
      }
    }
    else {
      // Make sure it is enabled.
      $menu_item->set('enabled', TRUE);
      if ($this->saveMenuItem($menu_item)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Disables a menu item.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_item
   *   The menu item to disable.
   *
   * @return bool
   *   Returns TRUE is menu item was disabled or non-existing, FALSE in case of
   *   error.
   */
  protected function disableMenuItem(MenuLinkContentInterface $menu_item) {
    if ($existing_menu_item = $this->getExistingMenuItem($menu_item)) {
      $existing_menu_item->set('enabled', FALSE);
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
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface|null
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
    $group_permissions->setRevisionCreationTime(\Drupal::service('datetime.time')
      ->getRequestTime());
    $group_permissions->setRevisionLogMessage('Group features enabled/disabled.');
    $group_permissions->save();
  }

  /**
   * Add role permissions to the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $groupPermission
   *   The group permission object to add the permissions to.
   * @param string $role
   *   The role to add the permissions to.
   * @param array $rolePermissions
   *   The permissions to add to the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface
   *   The unsaved group permission object with the updated permissions.
   */
  protected function addRolePermissionsToGroup(
    GroupPermissionInterface $groupPermission,
    string $role,
    array $rolePermissions
  ): GroupPermissionInterface {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (!array_key_exists($role, $permissions) || !in_array($permission, $permissions[$role],
          TRUE)) {
        $permissions[$role][] = $permission;
      }
    }
    $groupPermission->setPermissions($permissions);

    return $groupPermission;
  }

  /**
   * Remove role permissions from the group.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $groupPermission
   *   The group permission object to set the permissions to.
   * @param string $role
   *   The role to remove the permissions from.
   * @param array $rolePermissions
   *   The permissions to remove from the role.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface
   *   The unsaved group permission object with the updated permissions.
   */
  protected function removeRolePermissionsFromGroup(
    GroupPermissionInterface $groupPermission,
    string $role,
    array $rolePermissions
  ): GroupPermissionInterface {
    $permissions = $groupPermission->getPermissions();
    foreach ($rolePermissions as $permission) {
      if (array_key_exists($role, $permissions) || in_array($permission, $permissions[$role],
          TRUE)) {
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
  protected function getOutsiderRolesFromInternalRoles(
    GroupTypeInterface $groupType,
    array $internal_rids
  ): array {
    $roles = [];
    $group_roles = $groupType->getRoles();

    if (empty($group_roles)) {
      return $roles;
    }

    foreach ($group_roles as $role) {
      foreach ($internal_rids as $key => $internal_rid) {
        if ($role->isInternal() && in_array("user.role.{$internal_rid}",
            $role->getDependencies()['config'])) {
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
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_item
   *   The (unsaved) menu item to work with.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface|false
   *   The existing menu item of FALSE if it doesn't exist yet.
   */
  protected function getExistingMenuItem(MenuLinkContentInterface $menu_item) {
    $url = $menu_item->getUrlObject();
    $uri = empty(static::ANCHOR_ID) ?
      'internal:/' . $url->getInternalPath() :
      $url->toUriString();

    /** @var Drupal\menu_link_content\MenuLinkContentInterface[] $items */
    $items = $this->menuLinkContentStorage->loadByProperties([
      'menu_name' => $menu_item->getMenuName(),
      'link__uri' => $uri,
    ]);

    if (!empty($items)) {
      return reset($items);
    }

    return FALSE;
  }

  /**
   * Saves a menu item into a GroupContentMenu based on provided arguments.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $menu_item
   *   The menu item to save.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|false
   *   The saved menu item.
   */
  private function saveMenuItem(ContentEntityInterface $menu_item) {
    try {
      $menu_item->save();
      return $menu_item;
    }
    catch (EntityStorageException $e) {
      $logger = $this->getLogger('oec_group_features');
      $logger->error($e->getMessage());
      $this->messenger()
        ->addError('An error has occurred. Please contact the site administrators.');
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
