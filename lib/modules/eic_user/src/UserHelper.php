<?php

namespace Drupal\eic_user;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\eic_flags\FlagType;
use Drupal\eic_topics\Constants\Topics;
use Drupal\file\Entity\File;
use Drupal\flag\FlagCountManagerInterface;
use Drupal\taxonomy\TermInterface;
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
  const ROLE_SECURITY_ADMINISTRATOR = 'security_admin';

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The flag count service.
   *
   * @var \Drupal\flag\FlagCountManagerInterface
   */
  protected $flagCount;

  /**
   * Constructs a new UserHelper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current user.
   * @param \Drupal\flag\FlagCountManagerInterface $flag_count
   *   The flag count service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $account,
    Connection $connection,
    FlagCountManagerInterface $flag_count
  ) {
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $account;
    $this->connection = $connection;
    $this->flagCount = $flag_count;
  }

  /**
   * Returns the current user account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account for the current user.
   */
  public function getCurrentUser() {
    return $this->currentUser;
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
          // User is power user if has one of the administration roles.
          return TRUE;

      }
    }

    return FALSE;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   *
   * @return string
   */
  public static function getUserAvatar(UserInterface $user): string {
    $media_picture = $user->get('field_media')->referencedEntities();
    /** @var File|NULL $file */
    $file = $media_picture ? File::load($media_picture[0]->get('oe_media_image')->target_id) : '';

    return $file ? \Drupal::service('file_url_generator')
      ->transformRelative(file_create_url($file->get('uri')->value)) : '';
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

  /**
   * Returns the active member profile for the given user account.
   *
   * @param \Drupal\user\UserInterface $user
   *  The user entity.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *  The profile. NULL if no matching entity was found.
   */
  public function getUserMemberProfile(UserInterface $user) {
    return $this->entityTypeManager->getStorage('profile')->loadByUser($user, ProfileConst::MEMBER_PROFILE_TYPE_NAME);
  }

  /**
   * This will make sure the user has a member profile entity created.
   *
   * @param \Drupal\user\UserInterface $user
   *  The user entity.
   *
   * @return bool
   *   TRUE if a member profile exists for the given user.
   */
  public function ensureUserMemberProfile(UserInterface $user) {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    if (!$this->getUserMemberProfile($user)) {
      try {
        $profile = $this->entityTypeManager->getStorage('profile')->create([
          'type' => ProfileConst::MEMBER_PROFILE_TYPE_NAME,
          'uid' => $user->id(),
        ]);
        $profile->setDefault(TRUE);
        $profile->setPublished();
        $profile->save();
        return TRUE;
      }
      catch (\Exception $error) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks if a user has completed their profile.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity.
   *
   * @return bool
   *   TRUE if profile is completed, FALSE otherwise.
   */
  public function isUserProfileCompleted(UserInterface $account) {
    // Make sure the user has a member profile entity.
    $this->ensureUserMemberProfile($account);

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    if (!$profile = $this->getUserMemberProfile($account)) {
      return FALSE;
    }

    $violations = $profile->validate();
    if ($violations->count() > 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns the number of users by topic of expertise.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The topic of expertise to look for.
   * @param bool $active_only
   *   Whether to return active users only.
   *
   * @return int[]|false
   *   An array of user IDs.
   */
  public function getUsersByExpertise(TermInterface $term, bool $active_only = TRUE) {
    // If term is not a topic term, return FALSE.
    if ($term->bundle() != Topics::TERM_VOCABULARY_TOPICS_ID) {
      return FALSE;
    }

    // Get matching profiles.
    // @todo Combine this query with the user query when reverse conditions
    //   will be available.
    // @see https://www.drupal.org/project/drupal/issues/2975750
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->entityTypeManager->getStorage('profile')->getQuery()
      ->condition('type', ProfileConst::MEMBER_PROFILE_TYPE_NAME)
      ->condition('status', 1)
      ->condition('field_vocab_topic_expertise', [$term->id()], 'IN');
    $profile_ids = $query->execute();

    if (empty($profile_ids)) {
      return [];
    }

    // Get the owner users of the profiles.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->connection->select('users', 'u');
    $query->innerJoin('profile', 'p', 'u.uid = p.uid');
    $query->condition('p.profile_id', $profile_ids, 'IN');
    $query->fields('u', ['uid']);

    if ($active_only) {
      $query->innerJoin('users_field_data', 'ufd', 'u.uid = ufd.uid');
      $query->condition('ufd.status', 1);
    }

    return $query->execute()->fetchAllKeyed(0, 0);
  }

  /**
   * Returns the number of followers for a given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return int
   *   The number of followers.
   */
  public function getUserFollowers(UserInterface $user): int {
    $user_flag_counters = $this->flagCount->getEntityFlagCounts($user);

    // No one is following the user, therefore we return 0.
    if (!isset($user_flag_counters[FlagType::FOLLOW_USER])) {
      return 0;
    }

    return (int) $user_flag_counters[FlagType::FOLLOW_USER];
  }

  /**
   * Checks if a user is a SMED user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check if is SMED user.
   *
   * @return bool
   *   TRUE if user is a SMED user.
   */
  public static function isSmedUser(AccountInterface $account) {
    return $account->hasField('field_smed_id') && !$account->get('field_smed_id')->isEmpty();
  }

}
