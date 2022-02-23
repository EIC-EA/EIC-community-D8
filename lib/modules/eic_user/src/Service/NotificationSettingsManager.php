<?php

namespace Drupal\eic_user\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_user\ProfileConst;
use Drupal\eic_user\UserHelper;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use http\Exception\InvalidArgumentException;

/**
 * Class NotificationSettingsManager
 *
 * @package Drupal\eic_user\Service
 */
class NotificationSettingsManager {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\eic_user\UserHelper
   */
  private $userHelper;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   * @param \Drupal\eic_user\UserHelper $user_helper
   */
  public function __construct(AccountProxyInterface $account_proxy, UserHelper $user_helper) {
    $this->currentUser = $account_proxy;
    $this->userHelper = $user_helper;
  }

  /**
   * @param string $notification_type
   *
   * @return bool|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function toggleSetting(string $notification_type): ?bool {
    $user = User::load($this->currentUser->id());
    if (!$user instanceof UserInterface) {
      throw new InvalidArgumentException('Current user doesn\'t exist');
    }

    if (!in_array($notification_type, ProfileConst::ALLOWED_NOTIFICATION_TYPES)) {
      throw new InvalidArgumentException('Given type isn\'t allowed');
    }

    $profile = $this->userHelper->getUserMemberProfile($user);
    if (!$profile instanceof ProfileInterface) {
      return FALSE;
    }

    //TODO adapt this when group & event settings are being implemented
    switch ($notification_type) {
      case ProfileConst::INTEREST_NOTIFICATION_TYPE:
        $field = 'field_interest_notifications';
        break;
      case ProfileConst::COMMENTS_NOTIFICATION_TYPE:
        $field = 'field_comments_notifications';
        break;
      default:
        throw new InvalidArgumentException('Unknown notification type');
    }

    $value = !$profile->get($field)->value;
    $profile->set($field, $value);
    $profile->save();

    return $value;
  }

}
