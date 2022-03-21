<?php

namespace Drupal\eic_group_membership\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\eic_flags\FlagHelper;
use Drupal\eic_flags\FlagType;
use Drupal\flag\FlagService;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface;
use Drupal\user\UserInterface;
use Drush\Commands\DrushCommands;

/**
 * Class that provides drush commands related to group memberships.
 */
class GroupMembershipCommands extends DrushCommands {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The Flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * The EIC Flag helper service.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $eicFlagHelper;

  /**
   * The OEC Group visibility storage.
   *
   * @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface
   */
  protected $groupVisibilityStorage;

  /**
   * Constructs a new GroupMembershipCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \Drupal\flag\FlagService $flag_service
   *   The Flag service.
   * @param \Drupal\eic_flags\FlagHelper $eic_flag_helper
   *   The EIC Flag helper service.
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $group_visibility_storage
   *   The OEC Group visibility storage.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QueueFactory $queue_factory,
    FlagService $flag_service,
    FlagHelper $eic_flag_helper,
    GroupVisibilityDatabaseStorageInterface $group_visibility_storage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->flagService = $flag_service;
    $this->eicFlagHelper = $eic_flag_helper;
    $this->groupVisibilityStorage = $group_visibility_storage;
  }

  /**
   * Execute queue items to unfollow group content nodes from non members.
   *
   * @usage eic_group_membership-unfollowGroupContentFromNonMembers
   *   Run the command without any arguments to unfollow group content nodes
   *   from non members
   *
   * @command eic_group_membership:unfollowGroupContentFromNonMembers
   * @aliases unfollow_group_content_from_non_members
   */
  public function unfollowGroupContentFromNonMembers() {
    $queue = $this->queueFactory->get('eic_group_membership_unfollow_content');

    $exclude_group_visibilities = [
      'public',
      'restricted_community_members',
    ];

    while ($item = $queue->claimItem()) {
      try {
        if (
          !empty($item->data['gid']) &&
          !empty($item->data['uid'])
        ) {

          $group = $this->entityTypeManager->getStorage('group')->load($item->data['gid']);
          if (!$group instanceof GroupInterface) {
            $queue->deleteItem($item);
            continue;
          }

          $group_visibility = $this->groupVisibilityStorage->load($group->id());

          // Group visibility is one of the excluded ones, so we can skip this
          // item.
          if (in_array($group_visibility->getType(), $exclude_group_visibilities)) {
            $queue->deleteItem($item);
            continue;
          }

          $user = $this->entityTypeManager->getStorage('user')->load($item->data['uid']);
          if (!$user instanceof UserInterface) {
            $queue->deleteItem($item);
            continue;
          }

          // Get user follow content flags of all group content nodes of the
          // group.
          $user_content_flaggings = $this->eicFlagHelper->getGroupContentFollowFlaggingsFromUser($group, $user);

          // Unfollows every group content node if the user cannot view it.
          if (!empty($user_content_flaggings)) {
            foreach ($user_content_flaggings as $flagging) {
              if ($flagging->getFlaggable()->access('view', $user)) {
                continue;
              }
              $this->flagService->unflag($flagging->getFlag(), $flagging->getFlaggable(), $user);
            }
          }
        }

        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
    }
  }

  /**
   * Execute queue items to unfollow group content nodes on visibility change.
   *
   * @usage eic_group_membership-unfollowGroupContentOnVisibilityChange
   *   Run the command without any arguments to unfollow group content nodes
   *   from non members after changing group visibility.
   *
   * @command eic_group_membership:commandName
   * @aliases unfollow_group_content_on_visibility_change
   */
  public function unfollowGroupContentOnVisibilityChange() {
    $queue = $this->queueFactory->get('eic_group_membership_visibility_change_unfollow_content');

    $exclude_group_visibilities = [
      'public',
      'restricted_community_members',
    ];

    while ($item = $queue->claimItem()) {
      try {
        if (!empty($item->data['gid'])) {

          $group = $this->entityTypeManager->getStorage('group')->load($item->data['gid']);
          if (!$group instanceof GroupInterface) {
            $queue->deleteItem($item);
            continue;
          }

          $group_visibility = $this->groupVisibilityStorage->load($group->id());

          // Group visibility is one of the excluded ones, so we can skip this
          // item.
          if (in_array($group_visibility->getType(), $exclude_group_visibilities)) {
            $queue->deleteItem($item);
            continue;
          }

          $flag_types = [
            FlagType::FOLLOW_GROUP,
            FlagType::FOLLOW_CONTENT,
          ];
          // Unfollows every group and group content node if the user cannot
          // view it.
          foreach ($flag_types as $flag_type) {
            $flaggings = $this->eicFlagHelper->getGroupFollowFlaggingsByNonMembers($group, $flag_type);
            foreach ($flaggings as $flagging) {
              if ($flagging->getFlaggable()->access('view', $flagging->getOwner())) {
                continue;
              }
              $this->flagService->unflag($flagging->getFlag(), $flagging->getFlaggable(), $flagging->getOwner());
            }
          }
        }

        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      }
    }
  }

}
