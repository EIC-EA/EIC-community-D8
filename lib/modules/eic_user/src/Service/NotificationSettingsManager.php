<?php

namespace Drupal\eic_user\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_user\NotificationFrequencies;
use Drupal\eic_user\NotificationTypes;
use Drupal\eic_user\UserHelper;
use Drupal\flag\Entity\Flagging;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
  private static array $flags = [
    NotificationTypes::GROUPS_NOTIFICATION_TYPE => ['follow_group'],
    NotificationTypes::EVENTS_NOTIFICATION_TYPE => ['follow_group', 'follow_event'],
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\eic_user\UserHelper
   */
  private UserHelper $userHelper;

  /**
   * @var \Drupal\flag\FlagService
   */
  private FlagService $flagService;

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
   * @param $value
   *
   * @return bool|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setSettingValue(string $notification_type, $value, ?FlaggingInterface $flagging = NULL): ?bool {
    $user = User::load($this->currentUser->id());
    if (!$user instanceof UserInterface) {
      throw new \InvalidArgumentException('Current user does not exist');
    }

    if (!in_array($notification_type, NotificationTypes::ALLOWED_NOTIFICATION_TYPES)) {
      throw new \InvalidArgumentException('Given type is not allowed');
    }

    $new_value = NULL;
    switch ($notification_type) {
      case NotificationTypes::GROUPS_NOTIFICATION_TYPE:
      case NotificationTypes::EVENTS_NOTIFICATION_TYPE:
        $new_value = $this->updateFollowFlag($flagging, $user, $value);
        break;
      case NotificationTypes::COMMENTS_NOTIFICATION_TYPE:
      case NotificationTypes::INTEREST_NOTIFICATION_TYPE:
        $new_value = $this->updateProfileSetting($notification_type, $user, $value);
    }

    return $new_value;
  }

  /**
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\user\UserInterface $user
   * @param $value
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateFollowFlag(FlaggingInterface $flagging, UserInterface $user, $value) {
    $new_value = (bool) $value === TRUE
      ? NotificationFrequencies::ON
      : NotificationFrequencies::OFF;

    $flagging->set('field_notification_frequency', $new_value);
    $flagging->save();

    return $new_value === NotificationFrequencies::ON;
  }

  /**
   * @param string $notification_type
   * @param \Drupal\user\UserInterface $user
   * @param $value
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function updateProfileSetting(string $notification_type, UserInterface $user, $value): bool {
    $profile = $this->userHelper->getUserMemberProfile($user);
    if (!$profile instanceof ProfileInterface) {
      return FALSE;
    }

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

    $profile->set($field, (bool) $value);
    $profile->save();

    return $value;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   * @param string $notification_type
   *
   * @return bool
   */
  public function getProfileSetting(UserInterface $user, string $notification_type): bool {
    if (!in_array(
      $notification_type,
      [NotificationTypes::COMMENTS_NOTIFICATION_TYPE, NotificationTypes::INTEREST_NOTIFICATION_TYPE]
    )) {
      throw new InvalidArgumentException('Unsupported profile notification type');
    }

    $profile = $this->userHelper->getUserMemberProfile($user);
    if (!$profile instanceof ProfileInterface) {
      return TRUE;
    }

    $field = $notification_type === NotificationTypes::COMMENTS_NOTIFICATION_TYPE
      ? 'field_comments_notifications'
      : 'field_interest_notifications';

    return (bool) $profile->get($field)->value;
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

    $user = User::load($this->currentUser->id());
    if (!$user instanceof UserInterface) {
      throw new \InvalidArgumentException('Something went wrong, either the flag doesn\'t exist or the user is invalid.');
    }

    $entity_ids = $this->entityTypeManager->getStorage('flagging')
      ->getQuery()
      ->condition('flag_id', self::$flags[$type], 'IN')
      ->condition('uid', $user->id())
      ->execute();

    if (empty($entity_ids)) {
      return [];
    }

    return Flagging::loadMultiple($entity_ids);
  }

  /**
   * @param string $type
   * @param \Drupal\user\UserInterface $user
   * @param ContentEntityInterface $entity
   *   Array of additional filters (e.g: the entity id, entity type, etc.)
   *
   * @return string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFollowFlagValue(string $type, UserInterface $user, ContentEntityInterface $entity): ?string {
    if (!array_key_exists($type, self::$flags)) {
      throw new \InvalidArgumentException('Given type is not supported');
    }

    $flagging_ids = $this->entityTypeManager->getStorage('flagging')
      ->getQuery()
      ->condition('flag_id', self::$flags[$type], 'IN')
      ->condition('uid', $user->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->range(0, 1)
      ->execute();

    if (empty($flagging_ids)) {
      return NULL;
    }

    $flagging = Flagging::load(reset($flagging_ids));

    return $flagging->get('field_notification_frequency')->value;
  }

  /**
   * @param \Drupal\flag\FlaggingInterface $flagging
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function unsubscribe(FlaggingInterface $flagging): bool {
    $user = User::load($this->currentUser->id());
    if ($user->id() !== $flagging->getOwnerId()) {
      throw new AccessDeniedHttpException('Operation is not allowed');
    }

    $target_entity = $this->entityTypeManager
      ->getStorage($flagging->get('entity_type')->value)
      ->load($flagging->get('entity_id')->value);

    if (!$target_entity instanceof ContentEntityInterface) {
      throw new InvalidArgumentException('Entity does not exists');
    }

    $this->flagService->unflag($flagging->getFlag(), $target_entity, $user);

    return TRUE;
  }

}
