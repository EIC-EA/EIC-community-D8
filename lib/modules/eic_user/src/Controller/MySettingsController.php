<?php

namespace Drupal\eic_user\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\eic_user\NotificationFrequencies;
use Drupal\eic_user\NotificationTypes;
use Drupal\eic_user\Service\NotificationSettingsManager;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The "my settings" controller.
 */
class MySettingsController extends ControllerBase {

  /**
   * The service which manages notification settings.
   */
  private NotificationSettingsManager $notificationSettingsManager;

  /**
   * @param \Drupal\eic_user\Service\NotificationSettingsManager $notification_settings_manager
   */
  public function __construct(
    NotificationSettingsManager $notification_settings_manager
  ) {
    $this->notificationSettingsManager = $notification_settings_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\eic_user\Controller\MySettingsController|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_user.notification_settings_manager')
    );
  }

  /**
   * The member activities endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function settings(Request $request): array {
    return [];
  }

  /**
   * @param string $notification_type
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function setProfileNotificationSettings(string $notification_type, Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);
    if (!isset($body['value'])) {
      throw new \InvalidArgumentException('Invalid request');
    }

    $value = filter_var($body['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if (!is_bool($value)) {
      throw new \InvalidArgumentException('Invalid request');
    }

    $new_value = NULL;
    try {
      $new_value = $this->notificationSettingsManager->setSettingValue($notification_type, $value);
    } catch (\Exception $exception) {
      $this->messenger()->addError('Something wrong happened when toggling settings for @notification_type: @error',
        [
          '@notification_type' => $notification_type,
          '@error' => $exception->getMessage(),
        ]);
    }

    return new JsonResponse([
      'status' => is_bool($new_value),
      'value' => $new_value ?? FALSE,
    ]);
  }

  /**
   * @param string $notification_type
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function setFollowFlagValue(
    string $notification_type,
    FlaggingInterface $flagging,
    Request $request
  ): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);
    if (!isset($body['value'])) {
      throw new \InvalidArgumentException('Invalid request');
    }

    $value = filter_var($body['value'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if (!is_bool($value)) {
      throw new \InvalidArgumentException('Invalid request');
    }

    $new_value = NULL;
    try {
      $new_value = $this->notificationSettingsManager->setSettingValue($notification_type, $value, $flagging);
    } catch (\Exception $exception) {
      $this->messenger()->addError('Something wrong happened when toggling settings for @notification_type: @error',
        [
          '@notification_type' => $notification_type,
          '@error' => $exception->getMessage(),
        ]);
    }

    return new JsonResponse([
      'status' => is_bool($new_value),
      'value' => $new_value ?? FALSE,
    ]);
  }

  /**
   * @param $notification_type
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getFollowFlags(string $notification_type): JsonResponse {
    $flaggings = $this->notificationSettingsManager->getValues($notification_type);
    $formatted_flaggings = [
      'title' => $this->t(ucfirst($notification_type)),
      'items' => [],
    ];

    $target_bundle = $notification_type === NotificationTypes::EVENTS_NOTIFICATION_TYPE ? 'event' : 'group';
    foreach ($flaggings as $flagging) {
      $target_entity = $this->entityTypeManager()
        ->getStorage($flagging->get('entity_type')->value)
        ->load($flagging->get('entity_id')->value);

      if (!$target_entity instanceof ContentEntityInterface) {
        continue;
      }

      if ($target_entity->bundle() !== $target_bundle) {
        continue;
      }

      $formatted_flaggings['items'][] = [
        'id' => $flagging->id(),
        'state' => $flagging->get('field_notification_frequency')->value === NotificationFrequencies::ON,
        'update_url' => Url::fromRoute('eic_user.toggle_follow_flag', [
          'notification_type' => $notification_type,
          'flagging' => $flagging->id(),
        ])->toString(),
        'unsubscribe_url' => Url::fromRoute('eic_user.unsubscribe_follow_flag', [
          'notification_type' => $notification_type,
          'flagging' => $flagging->id(),
        ])->toString(),
        'name' => [
          'path' => $target_entity->toUrl()->toString(),
          'label' => $target_entity->label(),
        ],
      ];
    }

    return new JsonResponse($formatted_flaggings);
  }

  /**
   * @param string $notification_type
   * @param \Drupal\flag\FlaggingInterface $flagging
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function unsubscribe(string $notification_type, FlaggingInterface $flagging): JsonResponse {
    if (!in_array($notification_type, [
      NotificationTypes::GROUPS_NOTIFICATION_TYPE,
      NotificationTypes::EVENTS_NOTIFICATION_TYPE,
    ])) {
      throw new \InvalidArgumentException('Invalid request');
    }

    $result = FALSE;
    try {
      $result = $this->notificationSettingsManager->unsubscribe($flagging);
    } catch (\Exception $exception) {
      $this->messenger()->addError('Something wrong happened when toggling settings for @notification_type: @error',
        [
          '@notification_type' => $notification_type,
          '@error' => $exception->getMessage(),
        ]);
    }

    return new JsonResponse([
      'status' => $result,
    ]);
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var FlaggingInterface $flagging */
    $flagging = $route_match->getParameter('flagging');

    return $flagging->getOwnerId() === $account->id() ?
      AccessResult::allowed() :
      AccessResult::forbidden();
  }

}
