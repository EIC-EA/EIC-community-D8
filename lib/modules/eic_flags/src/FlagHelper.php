<?php

namespace Drupal\eic_flags;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\flag\FlagCountManager;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides helper methods for flags.
 */
class FlagHelper {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The flag count manager service.
   *
   * @var \Drupal\flag\FlagCountManager
   */
  protected $flagCountManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * Constructs a FlagHelper object.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\flag\FlagCountManager $flag_count_manager
   *   The flag count manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(
    FlagServiceInterface $flag_service,
    FlagCountManager $flag_count_manager,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $currentUser,
    Connection $database,
    EICGroupsHelper $eic_groups_helper
  ) {
    $this->flagService = $flag_service;
    $this->flagCountManager = $flag_count_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
    $this->database = $database;
    $this->groupsHelper = $eic_groups_helper;
  }

  /**
   * Get a list of users that have flagged an entity with a given flag ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flagged entity.
   * @param array $flag_ids
   *   Array of flag machine names.
   * @param bool $include_anonymous
   *   Whether to include anonymous flaggings. Defaults to FALSE.
   *
   * @return array
   *   An array of users who have flagged the entity.
   */
  public function getFlaggingUsersByFlagIds(EntityInterface $entity, array $flag_ids = [], $include_anonymous = FALSE) {
    if (empty($flag_ids)) {
      return $this->flagService->getFlaggingUsers($entity);
    }
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();
    $query->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());
    if (!empty($flag_ids)) {
      $query->condition('flag_id', $flag_ids, 'IN');
    }

    if (!$include_anonymous) {
      $query->condition('uid', 0, '<>');
    }

    $ids = $query->execute();

    // Load the flaggings.
    $flaggings = $this->entityTypeManager->getStorage('flagging')->loadMultiple($ids);

    $users = [];
    foreach ($flaggings as $flagging) {
      $user_id = $flagging->get('uid')->first()->getValue()['target_id'];
      $users[$user_id] = $this->entityTypeManager->getStorage('user')->load($user_id);
    }

    return $users;
  }

  /**
   * Checks if the user can use the highlight flag.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account. If none provided, defaults to current user.
   * @param Drupal\group\Entity\GroupInterface $group
   *   The entity.
   *
   * @return bool
   *   TRUE if user can use the highlight flag.
   */
  public function canUserHighlight(AccountInterface $account = NULL, GroupInterface $group = NULL) {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    if (empty($group)) {
      $group = $this->groupsHelper->isGroupPage();
    }

    // Content can only be highlighted in a group context, and only group admins
    // can do so.
    if ($group) {
      if ($this->groupsHelper::userIsGroupAdmin($group, $account)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the number of flaggings for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   * @param bool $include_group_content
   *   Whether to search also into group content.
   * @param array $node_filters
   *   Filters when querying nodes. See GroupInterface::getContent().
   *
   * @return array
   *   An array with the following structure:
   *   - group: array of flag_id => value. Only flags that have at least 1 value
   *   will be present in the result.
   *   - content: array of flag_id => value. Only flags that have at least 1
   *   value will be present in the result.
   */
  public function getFlaggingsCountPerGroup(GroupInterface $group, $include_group_content = FALSE, array $node_filters = []) {
    $count = [
      'group' => [],
      'node' => [],
    ];

    // Count the group flags.
    $count['group'] = $this->flagCountManager->getEntityFlagCounts($group);

    // Count group content flags if enabled.
    if ($include_group_content) {
      foreach ($this->groupsHelper->getGroupNodes($group, $node_filters) as $node) {
        foreach ($this->flagCountManager->getEntityFlagCounts($node) as $flag_id => $flag_count) {
          !isset($count['node'][$flag_id]) ?
            $count['node'][$flag_id] = $flag_count : $count['node'][$flag_id] += $flag_count;
        }
      }

    }
    return $count;
  }

  /**
   * Sets the EIC Groups helper service.
   *
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function setGroupsHelper(EICGroupsHelper $eic_groups_helper) {
    $this->groupsHelper = $eic_groups_helper;
  }

  /**
   * Get a list of content follow flags (related to group content) from a user.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\flag\Entity\Flagging[]
   *   An array user flaggings.
   */
  public function getGroupContentFollowFlaggingsFromUser(GroupInterface $group, AccountInterface $account) {
    // Grab user follow content flag IDs for a given group.
    $query = $this->database->select('flagging', 'f');
    $query->addField('f', 'id');
    $query->condition('f.flag_id', FlagType::FOLLOW_CONTENT);
    $query->condition('f.uid', $account->id());
    $query->join('group_content_field_data', 'gc', 'gc.entity_id = f.entity_id');
    $query->condition('gc.gid', $group->id());
    $query->condition('gc.type', $group->bundle() . '-group_node-%', 'LIKE');
    $results = $query->execute()->fetchAllAssoc('id');
    $flaggings = [];

    if (!empty($results)) {
      // Load the flaggings.
      $flaggings = $this->entityTypeManager->getStorage('flagging')->loadMultiple(array_keys($results));
    }

    return $flaggings;
  }

  /**
   * Get a list of group content flags from users (non-members).
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param string $flag_type
   *   The follow flag type.
   *
   * @return \Drupal\flag\Entity\Flagging[]
   *   An array of flaggings.
   */
  public function getGroupFollowFlaggingsByNonMembers(GroupInterface $group, string $flag_type = FlagType::FOLLOW_CONTENT) {
    $group_contents = $this->entityTypeManager->getStorage('group_content')
      ->loadByGroup($group, 'group_membership');

    // Grab user follow content flag IDs for a given group.
    $query = $this->database->select('flagging', 'f');
    $query->addField('f', 'id');
    $query->condition('f.flag_id', $flag_type);
    if (!empty($group_contents)) {
      $member_uids = [];
      foreach ($group_contents as $group_content) {
        $member_uids[] = $group_content->getEntity()->id();
      }
      $query->condition('f.uid', $member_uids, 'NOT IN');
    }
    if ($flag_type === FlagType::FOLLOW_CONTENT) {
      $query->join('group_content_field_data', 'gc', 'gc.entity_id = f.entity_id');
      $query->condition('gc.gid', $group->id());
      $query->condition('gc.type', $group->bundle() . '-group_node-%', 'LIKE');
    }
    else {
      $query->condition('f.entity_id', $group->id());
    }
    $results = $query->execute()->fetchAllAssoc('id');
    $flaggings = [];

    if (!empty($results)) {
      // Load the flaggings.
      $flaggings = $this->entityTypeManager->getStorage('flagging')->loadMultiple(array_keys($results));
    }

    return $flaggings;
  }

}
