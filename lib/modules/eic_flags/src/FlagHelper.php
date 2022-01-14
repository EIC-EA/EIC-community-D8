<?php

namespace Drupal\eic_flags;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_groups\EICGroupsHelper;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;


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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The entity type manager.
   */
  public function __construct(
    FlagServiceInterface $flag_service,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $currentUser
  ) {
    $this->flagService = $flag_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
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
   * Sets the EIC Groups helper service.
   *
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function setGroupsHelper(EICGroupsHelper $eic_groups_helper) {
    $this->groupsHelper = $eic_groups_helper;
  }

}
