<?php

namespace Drupal\eic_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * UserHelper service that provides helper functions for users.
 */
class UserHelper {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * Drupal administrator role.
   *
   * @var string
   */
  const ROLE_DRUPAL_ADMINISTRATOR = 'administrator';

  /**
   * Site administrator role.
   *
   * @var string
   */
  const ROLE_SITE_ADMINISTRATOR = 'site_admin';

  /**
   * Content administrator role.
   *
   * @var string
   */
  const ROLE_CONTENT_ADMINISTRATOR = 'content_administrator';

  /**
   * Trusted user role.
   *
   * @var string
   */
  const ROLE_TRUSTED_USER = 'trusted_user';

  /**
   * The user storage interface.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new UserHelper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $account) {
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->currentUser = $account;
  }

  /**
   * Provides a link to the given account with display name.
   *
   * If the current user doesn't have 'access user profiles' permission, the it
   * will return the name only.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account for which we return the link.
   *
   * @return \Drupal\Component\Render\MarkupInterface|\Drupal\Core\Link|string
   *   The username, linked if user can access user profiles.
   */
  public function getUserLink(UserInterface $account) {
    if ($this->currentUser->hasPermission('access user profiles')) {
      $url = Url::fromRoute('entity.user.canonical', ['user' => $account->id()]);
      return Link::fromTextAndUrl($account->getDisplayName(), $url);
    }
    return Markup::create($account->getDisplayName());
  }

  /**
   * Returns an array of uids for all the "power users".
   *
   * @param bool $active_only
   *   Whether to return only active users or all.
   *
   * @return array
   *   Array of uids.
   */
  public function getSitePowerUsers(bool $active_only = TRUE) {
    $query = $this->userStorage->getQuery()
      ->condition('status', (int) $active_only)
      ->condition('roles', [
        static::ROLE_SITE_ADMINISTRATOR,
        static::ROLE_CONTENT_ADMINISTRATOR,
      ], 'IN');

    if ($active_only) {
      $query->condition('status', 1);
    }

    return $query->execute();
  }

  /**
   * Checks if a user is a "power user".
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check if is a power user.
   *
   * @return bool
   *   TRUE if user is a power user.
   */
  public static function isPowerUser(AccountInterface $account) {
    // User 1 is always considered power user.
    if ((int) $account->id() === 1) {
      return TRUE;
    }

    foreach ($account->getRoles(TRUE) as $role) {
      switch ($role) {
        case static::ROLE_DRUPAL_ADMINISTRATOR:
        case static::ROLE_SITE_ADMINISTRATOR:
        case static::ROLE_CONTENT_ADMINISTRATOR:
          // User is power user if has one of the administation roles.
          return TRUE;

      }
    }

    return FALSE;
  }

  /**
   * @param \Drupal\user\UserInterface|NULL $user
   *
   * @return string
   */
  public function getFullName(UserInterface $user = NULL): string {
    if (!$user instanceof UserInterface) {
      $user_id = $this->currentUser->id();
      $user = User::load($user_id);

      if (!$user instanceof UserInterface) {
        return $this->t('User not found', [], ['context' => 'eic_user']);
      }
    }

    return realname_load($user) ?: '';
  }

}
