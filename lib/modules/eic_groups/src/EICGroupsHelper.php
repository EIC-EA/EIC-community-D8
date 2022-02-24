<?php

namespace Drupal\eic_groups;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\address\FieldHelper;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\message\MessageInterface;
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
use Drupal\eic_events\Constants\Event;
use Drupal\eic_groups\Constants\GroupJoiningMethodType;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_organisations\Constants\Organisations;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupMembership;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityInterface;
use Drupal\oec_group_flex\Plugin\GroupVisibility\CustomRestrictedVisibility;
use Drupal\taxonomy\TermInterface;

/**
 * EICGroupsHelper service that provides helper functions for groups.
 */
class EICGroupsHelper implements EICGroupsHelperInterface {

  use StringTranslationTrait;

  const GROUP_OWNER_ROLE = 'group-owner';

  const GROUP_ADMINISTRATOR_ROLE = 'group-admin';

  const GROUP_MEMBER_ROLE = 'group-member';

  const GROUP_TYPE_OWNER_ROLE = 'owner';

  const GROUP_TYPE_ADMINISTRATOR_ROLE = 'admin';

  const GROUP_TYPE_MEMBER_ROLE = 'member';

  const INVITEE_INVITATION_EMAIL_LIMIT = 2;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The oec_group_flex.helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * The group visibility manager service.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  protected $groupVisibilityManager;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The group content enabler manager service.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentPluginManager;

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
   *   The oec_group_flex.helper service.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $group_visibility_manager
   *   The group visibility manager service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $group_content_enabler_manager
   *   The group content enabler manager service.
   */
  public function __construct(
    Connection $database,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user,
    TimeInterface $time,
    OECGroupFlexHelper $oec_group_flex_helper,
    GroupVisibilityManager $group_visibility_manager,
    CurrentPathStack $current_path,
    EntityTypeManagerInterface $entity_type_manager,
    GroupContentEnablerManagerInterface $group_content_enabler_manager
  ) {
    $this->database = $database;
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
    $this->groupVisibilityManager = $group_visibility_manager;
    $this->currentPath = $current_path;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupContentPluginManager = $group_content_enabler_manager;
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
   * @param \Drupal\group\GroupMembership|null $membership
   *   The group membership (optional).
   *
   * @return bool
   *   TRUE if user is a group admin.
   */
  public static function userIsGroupAdmin(GroupInterface $group, AccountInterface $account, GroupMembership $membership = NULL) {
    // If user is power user, return TRUE.
    if (UserHelper::isPowerUser($account)) {
      return TRUE;
    }

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
   * Get the current group owner of a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param bool $return_as_membership_object
   *   (optional) Defaults to FALSE.
   *
   * @return \Drupal\user\UserInterface|\Drupal\group\GroupMembership|null
   *   Returns a user entity if $return_as_membership_object is FALSE (this is
   *   the default) and otherwise an GroupMembership object.
   */
  public static function getGroupOwner(GroupInterface $group, $return_as_membership_object = FALSE) {
    $owners = $group->getMembers($group->bundle() . '-' . self::GROUP_TYPE_OWNER_ROLE);

    if (empty($owners)) {
      return NULL;
    }

    /** @var \Drupal\group\GroupMembership $owner_membership */
    $owner_membership = reset($owners);

    if ($return_as_membership_object) {
      return $owner_membership;
    }

    return $owner_membership->getUser();
  }

  /**
   * Provides the list of roles for the given group types.
   *
   * @param string[]|null $group_types
   *   An array of group types to filter on. Default to all.
   * @param bool $include_internal_roles
   *   Whether to include group internal roles. Default to FALSE.
   *
   * @return array
   *   An array keyed as follows:
   *   - group_type: the group type machine name
   *     - label: the group type's label.
   *     - roles: array of roles.
   *       - role_id: the role machine name.
   *         => label of the role.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupRoles($group_types = NULL, $include_internal_roles = FALSE) {
    $roles = [];

    /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple($group_types);
    foreach ($group_types as $group_type) {
      $roles[$group_type->id()]['label'] = $group_type->label();
      $group_roles = $group_type->getRoles($include_internal_roles);
      foreach ($group_roles as $group_role) {
        $roles[$group_type->id()]['roles'][$group_role->id()] = $group_role->label();
      }
    }

    return $roles;
  }

  /**
   * Returns the correct role machine name for the given group type and role.
   *
   * @param string $group_type
   *   The group type ID.
   * @param string $role
   *   The role to check. Can be either "admin", "owner" or "member".
   *
   * @return string|null
   *   The group role machine name or NULL if not found.
   */
  public static function getGroupTypeRole(string $group_type, string $role) {
    $roles = [
      'group' => [
        self::GROUP_TYPE_ADMINISTRATOR_ROLE => self::GROUP_ADMINISTRATOR_ROLE,
        self::GROUP_TYPE_OWNER_ROLE => self::GROUP_OWNER_ROLE,
        self::GROUP_TYPE_MEMBER_ROLE => self::GROUP_MEMBER_ROLE,
      ],
      'event' => [
        self::GROUP_TYPE_ADMINISTRATOR_ROLE => Event::GROUP_ADMINISTRATOR_ROLE,
        self::GROUP_TYPE_OWNER_ROLE => Event::GROUP_OWNER_ROLE,
        self::GROUP_TYPE_MEMBER_ROLE => Event::GROUP_MEMBER_ROLE,
      ],
      'organisation' => [
        self::GROUP_TYPE_ADMINISTRATOR_ROLE => Organisations::GROUP_ADMINISTRATOR_ROLE,
        self::GROUP_TYPE_OWNER_ROLE => Organisations::GROUP_OWNER_ROLE,
        self::GROUP_TYPE_MEMBER_ROLE => Organisations::GROUP_MEMBER_ROLE,
      ],
    ];

    return $roles[$group_type][$role] ?? NULL;
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

    if ($entity instanceof MessageInterface) {
      $group_ref_id = NULL;
      if ($entity->hasField('field_group_ref') && $entity->get('field_group_ref')->entity instanceof GroupInterface) {
        $group_ref_id = $entity->get('field_group_ref')->entity->id();
      }
      $group = $group_ref_id ? Group::load($group_ref_id) : NULL;

      return $group;
    }

    if ($entity instanceof NodeInterface) {
      // Load all the group content for this entity.
      $group_contents = GroupContent::loadByEntity($entity);

      // We look for the first group_node group_content entity.
      foreach ($group_contents as $group_content_item) {
        if (strpos($group_content_item->getGroupContentType()->getContentPluginId(), 'group_node:') === 0) {
          $group = $group_content_item->getGroup();
          break;
        }
      }

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
   * @param string $group_type
   *   (optional) The group type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The description for the given plugin.
   */
  public function getGroupFlexPluginDescription(string $plugin_type, string $plugin_id, string $group_type = 'group') {
    $key = "$plugin_type-$plugin_id";

    switch ($key) {
      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC:
        return $this->t("This @group-type is visible to everyone visiting the @group-type. You're welcome to scroll through the @group-type's content. If you want to participate, please become a group member.", ['@group-type' => $group_type]);

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY:
        return $this->t("This @group-type is visible to every person that is a member of the EIC Community and has joined this platform. You're welcome to scroll through the @group-type's content. If you want to participate, please become a group member.", ['@group-type' => $group_type]);

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED:
        return $this->t('This @group-type is visible to every person that has joined the EIC community that also complies with the following restrictions. You can see this @group-type because the organisation you work for is allowed to see this content or the @group-type owners and administrators have chosen to specifically grant you access to this @group-type. If you want to participate, please become a group member.', ['@group-type' => $group_type]);

      case 'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PRIVATE:
        return $this->t('A private @group-type is only visible to people who received an invitation via email and accepted it. No one else can see this @group-type.', ['@group-type' => $group_type]);

      case 'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN:
        return $this->t('This means that EIC Community members can join this @group-type immediately by clicking "join group".', ['@group-type' => $group_type]);

      case 'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST:
        return $this->t('This means that EIC Community members can request to join this @group-type. This request needs to be validated by the @group-type owner or administrator.', ['@group-type' => $group_type]);

      default:
        return '';
    }
  }

  /**
   * Returns a list of groups based on the given visibility type.
   *
   * @param string $visibility
   *   The visibility type.
   *
   * @return array
   *   An array of Group entities.
   */
  public function getGroupsByVisibility(string $visibility) {
    $gids = $this->database->select('oec_group_visibility')
      ->fields('oec_group_visibility', ['gid'])
      ->condition('type', $visibility)
      ->execute()->fetchAllAssoc('gid', \PDO::FETCH_ASSOC);

    if (empty($gids)) {
      return [];
    }

    return Group::loadMultiple(array_keys($gids));
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
   * @param string $format
   *   The format of the label. Can be 'default' or 'short'. Defaults to
   *   'default'.
   * @param string $group_type
   *   The group type. Defaults to 'group'.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The description for the given plugin.
   */
  public function getGroupFlexPluginTitle(
    string $plugin_type,
    string $plugin_id,
    string $format = 'default',
    string $group_type = 'group'
  ) {
    $key = "$plugin_type-$plugin_id";

    $labels = [
      'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PUBLIC => [
        'default' => $this->t('Public @group-type', ['@group-type' => $group_type]),
        'short' => $this->t('Public'),
      ],
      'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_COMMUNITY => [
        'default' => $this->t('Community members only'),
        'short' => $this->t('Community members'),
      ],
      'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_CUSTOM_RESTRICTED => [
        'default' => $this->t('Restricted @group-type', ['@group-type' => $group_type]),
        'short' => $this->t('Restricted'),
      ],
      'visibility-' . GroupVisibilityType::GROUP_VISIBILITY_PRIVATE => [
        'default' => $this->t('Private @group-type', ['@group-type' => $group_type]),
        'short' => $this->t('Private'),
      ],
      'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_OPEN => [
        'default' => $this->t('Open'),
        'short' => $this->t('Open'),
      ],
      'joining_method-' . GroupJoiningMethodType::GROUP_JOINING_METHOD_TU_MEMBERSHIP_REQUEST => [
        'default' => $this->t('Moderated'),
        'short' => $this->t('Moderated'),
      ],
    ];

    if (!empty($labels[$key][$format])) {
      return $labels[$key][$format];
    }

    return '';
  }

  /**
   * Returns the visibility label for the given group entity.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to check.
   * @param string $format
   *   The format of the label. Can be 'default' or 'short'. Defaults to
   *   'default'.
   *
   * @return string
   *   The visibility label.
   */
  public function getGroupVisibilityLabel(GroupInterface $group, string $format = 'default') {
    $group_visibility = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);
    return $this->getGroupFlexPluginTitle('visibility', $group_visibility['plugin_id'], $format);
  }

  /**
   * Returns the list of installed plugins for a group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface|null $group_type
   *   The group type object. If none provided, will return for all group types.
   *
   * @return string[]
   *   An array of plugin IDs.
   */
  public function getGroupTypeEnabledPlugins(GroupTypeInterface $group_type = NULL) {
    return $this->groupContentPluginManager->getInstalledIds($group_type);
  }

  /**
   * Returns the list of enabled group_node plugins for the given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type object.
   *
   * @return array
   *   An array of plugin type IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupTypeEnabledContentPlugins(GroupTypeInterface $group_type) {
    $plugins = [];
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $node_type) {
      if ($this->isGroupTypePluginEnabled($group_type, 'group_node', $node_type->id())) {
        // @todo Get real content type config id?
        // @see \Drupal\group\Plugin\GroupContentEnablerBase::getContentTypeConfigId().
        $plugins[] = $group_type->id() . '-group_node-' . $node_type->id();
      }
    }
    return $plugins;
  }

  /**
   * Checks if a plugin is enabled for the given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type object.
   * @param string $plugin_type
   *   The plugin type.
   * @param string $bundle
   *   The bundle to append to the plugin type.
   *   E.g. 'group_node:news'.
   *
   * @return bool
   *   TRUE if plugin is enabled, FALSE otherwise.
   */
  public function isGroupTypePluginEnabled(GroupTypeInterface $group_type, string $plugin_type, string $bundle = ''):bool {
    $enabled_plugins = $this->getGroupTypeEnabledPlugins($group_type);
    $plugin_id = $plugin_type;
    if (!empty($bundle)) {
      $plugin_id .= ":$bundle";
    }
    return in_array($plugin_id, $enabled_plugins);
  }

  /**
   * Returns nodes that belong to a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param array $filters
   *   Filters to apply to the query. See GroupInterface::getContent().
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array of node entities.
   */
  public function getGroupNodes(GroupInterface $group, array $filters = []) {
    $content = [];
    foreach ($this->getGroupTypeEnabledPlugins($group->getGroupType()) as $plugin_id) {
      if (strpos($plugin_id, 'group_node') !== 0) {
        continue;
      }
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      foreach ($group->getContent($plugin_id, $filters) as $group_content) {
        $content[] = $group_content->getEntity();
      }
    }
    return $content;
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
   * Checks if the current page is a group under review page.
   *
   * @return \Drupal\group\Entity\Group|bool
   *   Returns the group if is blocked and the user can view it.
   */
  public function isGroupUnderReviewPage(?GroupInterface $group) {
    $route_name = $this->routeMatch->getRouteName();

    if (!$route_name === 'system.403') {
      return FALSE;
    }

    if (!$group) {
      if (!($group = $this->isGroupPage())) {
        return $group;
      }
    }

    $is_group_page = $group;

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

    $group_visibility_plugin = $this->groupVisibilityManager->createInstance($group_visibility_settings['plugin_id']);

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
            $is_group_page = $group;
            break;
          }
        }
      }
    }

    return $is_group_page;
  }

  /**
   * Checks if the current page is a group page.
   *
   * @return \Drupal\group\Entity\Group|bool
   *   Returns the group entity object if the current page is a group page.
   */
  public function isGroupPage() {
    $is_group_page = FALSE;
    $current_path = $this->currentPath->getPath();
    $current_url = Url::fromUri("internal:" . $current_path);

    if (!$current_url->isRouted()) {
      return $is_group_page;
    }

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
        $is_group_page = $group;
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

        $is_group_page = $group;
        break;
    }

    return $is_group_page;
  }

  /**
   * Returns a list of groups based on their topics.
   *
   * This function assumes that all groups have a field_vocab_topics.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term to look for.
   * @param string[] $group_types
   *   An array of group type machine names. If empty, the function will return
   *   results for all types.
   * @param bool $published_only
   *   Whether to return published groups only.
   *
   * @return array|int
   *   And array of group IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupsByTopic(TermInterface $term, array $group_types = [], bool $published_only = TRUE) {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->entityTypeManager->getStorage('group')->getQuery();

    if (!empty($group_types)) {
      $query->condition('type', $group_types, 'IN');
    }

    if ($published_only) {
      $query->condition('status', 1);
    }

    $query->condition('field_vocab_topics', [$term->id()], 'IN');
    return $query->execute();
  }

  /**
   * Get group administrators.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which we want to grab the list of administrators.
   *
   * @return \Drupal\group\GroupMembership[]
   *   Array of group admin memberships.
   */
  public static function getGroupAdmins(GroupInterface $group) {
    return $group->getMembers([
      $group->bundle() . '-' . self::GROUP_TYPE_ADMINISTRATOR_ROLE,
    ]);
  }

  /**
   * Format array of a field address to string.
   *
   * @param mixed $address
   *   The address field array.
   *
   * @return string
   *   The formatted address.
   */
  public static function formatAddress($address) {
    $countries_map = CountryManager::getStandardList();
    $location_formatted = $address[FieldHelper::getPropertyName(AddressField::ADDRESS_LINE1)]
      . ' ' .
      $address[$address[FieldHelper::getPropertyName(AddressField::ADDRESS_LINE2)]] . '<br />';
    $location_formatted .= $address[FieldHelper::getPropertyName(AddressField::POSTAL_CODE)]
      . ' ' .
      $address[FieldHelper::getPropertyName(AddressField::LOCALITY)] . '<br />';
    $location_formatted .= array_key_exists(
      $address['country_code'],
      $countries_map
    ) ?
      $countries_map[$address['country_code']] :
      '';

    return $location_formatted;
  }

}
