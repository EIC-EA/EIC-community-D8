<?php

namespace Drupal\eic_groups;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_groups\Constants\GroupJoiningMethodType;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityInterface;
use Drupal\oec_group_flex\Plugin\GroupVisibility\CustomRestrictedVisibility;

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
   * The OEC Group flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * The group visibility manager service.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  protected $groupVibilityManager;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

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
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The OEC Group flex helper service.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $group_vibility_manager
   *   The group visibility manager service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path service.
   */
  public function __construct(
    Connection $database,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user,
    TimeInterface $time,
    OECGroupFlexHelper $oec_group_flex_helper,
    GroupVisibilityManager $group_vibility_manager,
    CurrentPathStack $current_path
  ) {
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->groupVibilityManager = $group_vibility_manager;
    $this->currentPath = $current_path;
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

    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      if (!empty($limit_entities) && !in_array($plugin->getEntityTypeId(), $limit_entities)) {
        continue;
      }

      $plugin_operation_links = $plugin->getGroupOperations($group);

      // Remove operation plugins if the user doesn't have access.
      foreach ($plugin_operation_links as $key => $plugin_operation_link) {
        if ($plugin_operation_link['url']->access()) {
          continue;
        }

        unset($plugin_operation_link[$key]);
      }

      $operation_links += $plugin_operation_links;

      // Retrieve the operations from the installed content plugins and merges
      // cacheable metadata.
      if (!is_null($cacheable_metadata)) {
        $cacheable_metadata = $cacheable_metadata->merge($plugin->getGroupOperationsCacheableMetadata());
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
      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC:
        return $this->t("This group is visible to everyone visiting the group. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY:
        return $this->t("This group is visible to every person that is a member of the EIC Community and has joined this platform. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED:
        return $this->t('This group is visible to every person that has joined the EIC community that also complies with the following restrictions. You can see this group because the organisation you work for is allowed to see this content or the group owners and administrators have chosen to specifically grant you access to this group. If you want to participate, please become a group member.');

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PRIVATE:
        return $this->t('A private group is only visible to people who received an invitation via email and accepted it. No one else can see this group.');

      case 'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN:
        return $this->t('This means that EIC Community members can join this group immediately by clicking "join group".');

      case 'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST:
        return $this->t('This means that EIC Community members can request to join this group. This request needs to be validated by the group owner or administrator.');

      default:
        return '';
    }
  }

  /**
   * Returns a custom title for the given group_flex plugin.
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
  public function getGroupFlexPluginTitle(string $plugin_type, string $plugin_id) {
    $key = "$plugin_type-$plugin_id";

    switch ($key) {
      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC:
        return $this->t('Public group');

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY:
        return $this->t('Community members only');

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED:
        return $this->t('Restricted group');

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PRIVATE:
        return $this->t('Private group');

      case 'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN:
        return $this->t('Open');

      case 'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST:
        return $this->t('Moderated');

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
      if (!array_key_exists($role, $permissions) || !in_array($permission, $permissions[$role], TRUE)) {
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
      if (array_key_exists($role, $permissions) || in_array($permission, $permissions[$role], TRUE)) {
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
   *
   * Considered as content:
   *  - Discussions
   *  - Documents
   *  - Wiki pages.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which the check is.
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

  /**
   * Check if a group can be flagged depending on the moderation state.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return bool
   *   TRUE if the group is not in Pending or Draft state.
   */
  public static function groupIsFlaggable(GroupInterface $group) {
    $moderation_state = $group->get('moderation_state')->value;
    return !in_array(
      $moderation_state,
      [
        GroupsModerationHelper::GROUP_PENDING_STATE,
        GroupsModerationHelper::GROUP_DRAFT_STATE,
      ]
    );
  }

  /**
   * Checks if a user is a group admin of a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object.
   * @param \Drupal\group\GroupMembership $membership
   *   The group membership (optional).
   *
   * @return bool
   *   TRUE if user is a group admin.
   */
  public static function userIsGroupAdmin(GroupInterface $group, AccountInterface $account, GroupMembership $membership = NULL) {
    $membership = $membership ?: $group->getMember($account);

    // User is not a member of the group. We return FALSE.
    if (!$membership) {
      return FALSE;
    }

    $membership_roles = $membership->getRoles();
    $is_admin = FALSE;

    foreach ($membership_roles as $role) {
      $is_admin = in_array(
        $role->id(),
        [
          self::GROUP_ADMINISTRATOR_ROLE,
          self::GROUP_OWNER_ROLE,
        ]
      );

      if (!$is_admin) {
        continue;
      }

      break;
    }

    return $is_admin;
  }

  /**
   * Checks if the current page is a group under review page.
   *
   * @return bool
   *   TRUE if group is blocked and user can view the group.
   */
  public function isGroupUnderReviewPage() {
    $is_group_page = FALSE;
    $route_name = $this->routeMatch->getRouteName();

    if (!$route_name === 'system.403') {
      return $is_group_page;
    }

    $current_path = $this->currentPath->getPath();
    $current_url = Url::fromUri("internal:" . $current_path);
    $route_name = $current_url->getRouteName();
    $route_parameters = $current_url->getRouteParameters();

    switch ($route_name) {
      case 'entity.group.canonical':
      case 'eic_groups.about_page':
      case GroupOverviewPages::DISCUSSIONS:
      case GroupOverviewPages::FILES:
      case GroupOverviewPages::MEMBERS:
      case GroupOverviewPages::SEARCH:
        if (is_numeric($route_parameters['group'])) {
          $group = Group::load($route_parameters['group']);
        }
        elseif ($route_parameters['group'] instanceof GroupInterface) {
          $group = $route_parameters['group'];
        }
        else {
          break;
        }
        $is_group_page = TRUE;
        break;

      case 'entity.node.canonical':
        if (empty($route_parameters['node'])) {
          break;
        }

        if (is_numeric($route_parameters['node'])) {
          $node = Node::load($route_parameters['node']);
        }

        if (!isset($node) && !$route_parameters['node'] instanceof NodeInterface) {
          break;
        }

        $group = $this->getGroupByEntity($node);

        if (!$group) {
          break;
        }

        $is_group_page = TRUE;
        break;
    }

    if ($is_group_page) {
      $moderation_state = $group->get('moderation_state')->value;

      // If group is not blocked, we return FALSE.
      if ($moderation_state !== GroupsModerationHelper::GROUP_BLOCKED_STATE) {
        return FALSE;
      }

      // If user doesn't have permission to view the group, we return FALSE.
      if (!$group->hasPermission('view group', $this->currentUser->getAccount())) {
        return FALSE;
      }

      $group_visibility_settings = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);

      // If group visibility is not custom restricted, it means the user can
      // access the group but the group is under review.
      if ($group_visibility_settings['plugin_id'] !== GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED) {
        return $is_group_page;
      }

      $group_visibility_plugin = $this->groupVibilityManager->createInstance($group_visibility_settings['plugin_id']);

      if ($group_visibility_plugin instanceof CustomRestrictedVisibility) {
        $is_group_page = FALSE;

        // Loop through all of the options, they are keyed by pluginId.
        // If we have a match and the plugin returns not neutral we return the
        // it means the user has access to the group but the group is under
        // review.
        foreach (array_keys($group_visibility_settings['settings']->getOptions()) as $pluginId) {
          $group_custom_restricted_visibility_plugins = $group_visibility_plugin->getCustomRestrictedPlugins();
          $plugin = isset($group_custom_restricted_visibility_plugins[$pluginId]) ? $group_custom_restricted_visibility_plugins[$pluginId] : NULL;

          if ($plugin instanceof CustomRestrictedVisibilityInterface) {
            $pluginAccess = $plugin->hasViewAccess($group, $this->currentUser->getAccount(), $group_visibility_settings['settings']);
            if (!$pluginAccess->isNeutral()) {
              $is_group_page = TRUE;
              break;
            }
          }
        }
      }
    }

    return $is_group_page;
  }

}
