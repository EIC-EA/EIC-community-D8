<?php

namespace Drupal\eic_groups;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\node\NodeInterface;

/**
 * EICGroupsHelper service that provides helper functions for groups.
 */
class EICGroupsHelper implements EICGroupsHelperInterface {

  use StringTranslationTrait;

  const GROUP_OWNER_ROLE = 'group-owner';

  const GROUP_ADMINISTRATOR_ROLE = 'group-admin';

  const GROUP_MEMBER_ROLE = 'group-member';

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new EventsHelperService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    Connection $database,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user,
    TimeInterface $time
  ) {
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupFromRoute() {
    $entity = FALSE;
    $parameters = $this->routeMatch->getParameters()->all();
    if (!empty($parameters['group']) && is_numeric($parameters['group'])) {
      $group = Group::load($parameters['group']);
      return $group;
    }
    if (!empty($parameters)) {
      foreach ($parameters as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $entity = $parameter;
          break;
        }
      }
    }
    if ($entity) {
      return $this->getGroupByEntity($entity);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupByEntity(EntityInterface $entity) {
    $group = FALSE;
    if ($entity instanceof GroupInterface) {
      return $entity;
    }
    elseif ($entity instanceof NodeInterface) {
      // Load all the group content for this entity.
      $group_content = GroupContent::loadByEntity($entity);
      // Assuming that the content can be related only to 1 group.
      $group_content = reset($group_content);
      if (!empty($group_content)) {
        $group = $group_content->getGroup();
      }
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContentOperationLinks(
    GroupInterface $group,
    $limit_entities = [],
    CacheableMetadata $cacheable_metadata = NULL
  ) {
    $operation_links = [];

    if (!is_null($cacheable_metadata)) {
      // Retrieve the operations from the installed content plugins and merges
      // cacheable metadata.
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        if (!empty($limit_entities) && !in_array($plugin->getEntityTypeId(), $limit_entities)) {
          continue;
        }

        $operation_links += $plugin->getGroupOperations($group);
        $cacheable_metadata = $cacheable_metadata->merge($plugin->getGroupOperationsCacheableMetadata());
      }
    }
    else {
      // Retrieve the operations from the installed content plugins without
      // merging cacheable metadata.
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        if (!empty($limit_entities) && !in_array($plugin->getEntityTypeId(), $limit_entities)) {
          continue;
        }

        $operation_links += $plugin->getGroupOperations($group);
      }
    }

    if ($operation_links) {
      // Allow modules to alter the collection of gathered links.
      $this->moduleHandler->alter('group_operations', $operation_links, $group);

      // Sort the operations by weight.
      uasort($operation_links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    }

    return $operation_links;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupBookPage(GroupInterface $group) {
    $query = $this->database->select('group_content_field_data', 'gp');
    $query->condition('gp.type', 'group-group_node-book');
    $query->condition('gp.gid', $group->id());
    $query->join('book', 'b', 'gp.entity_id = b.nid');
    $query->fields('b', ['bid', 'nid']);
    $query->condition('b.pid', 0);
    $query->orderBy('b.weight');
    $results = $query->execute()->fetchAll(\PDO::FETCH_OBJ);
    if (!empty($results)) {
      return $results[0]->nid;
    }
    return NULL;
  }

  /**
   * Returns a custom description for the given group_flex plugin.
   *
   * @param string $plugin_type
   *   The plugin type can be one of the following type:
   *   - visibility: the GroupVisibility plugin type.
   *   - joining_method: the GroupJoiningMethod plugin type.
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The description for the given plugin.
   */
  public function getGroupFlexPluginDescription(string $plugin_type, string $plugin_id) {
    $key = "$plugin_type-$plugin_id";

    switch ($key) {
      case 'visibility-public':
        return $this->t("This group is visible to everyone visiting the group. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");

      case 'visibility-restricted_community_members':
        return $this->t("This group is visible to every person that is a member of the EIC Community and has joined this platform. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");

      case 'visibility-custom_restricted':
        return $this->t('This group is visible to every person that has joined the EIC community that also complies with the following restrictions. You can see this group because the organisation you work for is allowed to see this content or the group owners and administrators have chosen to specifically grant you access to this group. If you want to participate, please become a group member.');

      case 'visibility-private':
        return $this->t('A private group is only visible to people who received an invitation via email and accepted it. No one else can see this group.');

      case 'joining_method-tu_open_method':
        return $this->t('This means that EIC Community members can join this group immediately by clicking "join group".');

      case 'joining_method-tu_group_membership_request':
        return $this->t('This means that EIC Community members can request to join this group. This request needs to be validated by the group owner or administrator.');

      default:
        return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addRolePermissionsToGroup(
    GroupPermissionInterface $group_permissions,
    string $role,
    array $role_permissions
  ) {
    $permissions = $group_permissions->getPermissions();
    foreach ($role_permissions as $permission) {
      if (!array_key_exists($role, $permissions) || !in_array($permission, $permissions[$role],
          TRUE)) {
        $permissions[$role][] = $permission;
      }
    }
    $group_permissions->setPermissions($permissions);
    return $group_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRolePermissionsFromGroup(
    GroupPermissionInterface $group_permissions,
    string $role,
    array $role_permissions
  ) {
    $permissions = $group_permissions->getPermissions();
    foreach ($role_permissions as $permission) {
      if (array_key_exists($role, $permissions) || in_array($permission, $permissions[$role],
          TRUE)) {
        $permissions[$role] = array_diff($permissions[$role], [$permission]);
      }
    }
    $group_permissions->setPermissions($permissions);
    return $group_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function saveGroupPermissions(GroupPermissionInterface $group_permissions) {
    $violations = $group_permissions->validate();

    if (count($violations) > 0) {
      $message = '';
      foreach ($violations as $violation) {
        $message .= "\n" . $violation->getMessage();
      }
      throw new EntityStorageException('Group permissions were not saved correctly, because:' . $message);
    }

    // Saves the GroupPermission object with a new revision.
    $group_permissions->setNewRevision();
    $group_permissions->setRevisionUserId($this->currentUser->id());
    $group_permissions->setRevisionCreationTime($this->time->getRequestTime());
    $group_permissions->setRevisionLogMessage('Group permissions updated successfully.');
    $group_permissions->save();
  }

  /**
   * Determines if a group has content.
   * Considered as content:
   *  - Discussions
   *  - Documents
   *  - Wiki pages
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *    The group for which the check is.
   */
  public function hasContent(GroupInterface $group) {
    $query = $this->database->select('group_content_field_data', 'gp');
    $query->condition('gp.type', [
      'group-group_node-discussion',
      'group-group_node-document',
      'group-group_node-wiki_page',
    ], 'IN');
    $query->condition('gp.gid', $group->id());
    $query->fields('gp', ['id']);

    return !empty($query->execute()->fetchAll(\PDO::FETCH_OBJ));
  }

}
