<?php

namespace Drupal\eic_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * UserHelper service that provides helper functions for users.
 */
class UserHelper {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * Site administrator role.
   *
   * @var string
   */
  const ROLE_SITE_ADMINISTRATOR = 'administrator';

  /**
   * Content administrator role.
   *
   * @var string
   */
  const ROLE_CONTENT_ADMINISTRATOR = 'content_administrator';

  /**
   * The user storage interface.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new UserHelper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->userStorage = $entity_type_manager->getStorage('user');
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

}
