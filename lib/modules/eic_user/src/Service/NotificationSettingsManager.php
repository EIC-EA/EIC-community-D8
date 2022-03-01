<?php

namespace Drupal\eic_user\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_user\NotificationFrequencies;
use Drupal\eic_user\NotificationTypes;
use Drupal\eic_user\UserHelper;
use Drupal\flag\Entity\Flagging;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagService;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class NotificationSettingsManager
 *
 * @package Drupal\eic_user\Service
 */
class NotificationSettingsManager {

  /**
   * Matching flags for each supported notification type.
   *
   * @var string[]
   */
  private static $flags = [
    NotificationTypes::GROUPS_NOTIFICATION_TYPE => 'follow_group',
    NotificationTypes::EVENTS_NOTIFICATION_TYPE => 'follow_group',
  ];

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
   * @var \Drupal\flag\FlagService
   */
  private $flagService;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   * @param \Drupal\eic_user\UserHelper $user_helper
   * @param \Drupal\flag\FlagService $flag_service
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $account_proxy,
    UserHelper $user_helper,
    FlagService $flag_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $account_proxy;
    $this->userHelper = $user_helper;
    $this->flagService = $flag_service;
  }

  /**
   * @param string $notification_type
   * @param \Drupal\flag\FlaggingInterface|null $flagging
   *
   * @return bool|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function toggleSetting(string $notification_type, ?FlaggingInterface $flagging = NULL): ?bool {
    $user = User::load($this->currentUser->id());
    if (!$user instanceof UserInterface) {
      throw new \InvalidArgumentException('Current user doesn\'t exist');
    }

    if (!in_array($notification_type, NotificationTypes::ALLOWED_NOTIFICATION_TYPES)) {
      throw new \InvalidArgumentException('Given type isn\'t allowed');
    }

    $new_value = NULL;
    switch ($notification_type) {
      case NotificationTypes::GROUPS_NOTIFICATION_TYPE:
      case NotificationTypes::EVENTS_NOTIFICATION_TYPE:
        $new_value = $this->updateFollowFlag($flagging, $user);
        break;
      case NotificationTypes::COMMENTS_NOTIFICATION_TYPE:
      case NotificationTypes::INTEREST_NOTIFICATION_TYPE:
        $new_value = $this->updateProfileSetting($notification_type, $user);
    }

    return $new_value;
  }

  /**
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\user\UserInterface $user
   *
   * @return string
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFollowFlag(FlaggingInterface $flagging, UserInterface $user) {
    $current_value = $flagging->get('field_notification_frequency')->value;
    $new_value = empty($current_value) || $current_value === NotificationFrequencies::OFF
      ? NotificationFrequencies::ON
      : NotificationFrequencies::OFF;

    $flagging->set('field_notification_frequency', $new_value);
    $flagging->save();

    return $new_value;
  }

  /**
   * @param string $notification_type
   * @param \Drupal\user\UserInterface $user
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function updateProfileSetting(string $notification_type, UserInterface $user) {
    $profile = $this->userHelper->getUserMemberProfile($user);
    if (!$profile instanceof ProfileInterface) {
      return FALSE;
    }

    //TODO adapt this when group & event settings are being implemented
    switch ($notification_type) {
      case NotificationTypes::INTEREST_NOTIFICATION_TYPE:
        $field = 'field_interest_notifications';
        break;
      case NotificationTypes::COMMENTS_NOTIFICATION_TYPE:
        $field = 'field_comments_notifications';
        break;
      default:
        throw new \InvalidArgumentException('Unknown notification type');
    }

    $value = !$profile->get($field)->value;
    $profile->set($field, $value);
    $profile->save();

    return $value;
  }

  /**
   * @param string $type
   *
   * @return \Drupal\flag\FlaggingInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getValues(string $type): array {
    if (!array_key_exists($type, self::$flags)) {
      throw new \InvalidArgumentException('Given type is not supported');
    }

    $flag = $this->flagService->getFlagById(self::$flags[$type]);
    $user = User::load($this->currentUser->id());
    if (!$user instanceof UserInterface || !$flag instanceof FlagInterface) {
      throw new \InvalidArgumentException('Something went wrong, either the flag doesn\'t exist or the user is invalid.');
    }

    $entity_ids = $this->entityTypeManager->getStorage('flagging')
      ->getQuery()
      ->condition('flag_id', $flag->id())
      ->condition('uid', $user->id())
      ->execute();

    if (empty($entity_ids)) {
      return [];
    }

    return Flagging::loadMultiple($entity_ids);
  }

}
