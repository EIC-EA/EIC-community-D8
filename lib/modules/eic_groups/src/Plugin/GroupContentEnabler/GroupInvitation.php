<?php

namespace Drupal\eic_groups\Plugin\GroupContentEnabler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation as GroupInvitationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends content enabler class for group invitations.
 */
class GroupInvitation extends GroupInvitationBase implements ContainerFactoryPluginInterface {

  /**
   * The user helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * Build a new link type instance and sets the configuration.
   *
   * @param array $configuration
   *   The configuration array with which to initialize this plugin.
   * @param string $plugin_id
   *   The ID with which to initialize this plugin.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The user helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    UserHelper $user_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userHelper = $user_helper;
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
      $container->get('eic_user.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = parent::getGroupOperations($group);
    $account = \Drupal::currentUser();

    // We keep only operations the user has access to.
    foreach ($operations as $key => $operation) {
      if (!$operation['url']->access($account)) {
        unset($operations[$key]);
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    $access = parent::createAccess($group, $account);

    // If access is not allowed, we do nothing.
    if (!$access->isAllowed()) {
      return $access;
    }

    switch ($group->get('moderation_state')->value) {
      case GroupsModerationHelper::GROUP_PENDING_STATE:
      case GroupsModerationHelper::GROUP_DRAFT_STATE:
        // Deny access to the group invitation form if the group is NOT yet
        // published and the user is not a "site_admin" or a
        // "content_administrator".
        if (!$this->userHelper->isPowerUser($account)) {
          $access = GroupAccessResult::forbidden()
            ->addCacheableDependency($account)
            ->addCacheableDependency($group);
        }
        break;

    }

    return $access;
  }

}
