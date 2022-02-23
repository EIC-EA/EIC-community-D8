<?php

namespace Drupal\eic_user\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_user\Service\NotificationSettingsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The "my settings" controller.
 */
class MySettingsController extends ControllerBase {

  /**
   * @var \Drupal\eic_user\Service\NotificationSettingsManager
   */
  private $notificationSettingsManager;

  /**
   * @param \Drupal\eic_user\Service\NotificationSettingsManager $notification_settings_manager
   */
  public function __construct(NotificationSettingsManager $notification_settings_manager) {
    $this->notificationSettingsManager = $notification_settings_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\eic_user\Controller\MySettingsController|static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('eic_user.notification_settings_manager'));
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
   * Toggles the value for fields 'field_interest_notifications' and 'field_comments_notifications'
   * on current user's profile
   *
   * @param string $notification_type
   *
   * @return JsonResponse
   */
  public function toggleProfileNotificationSettings(string $notification_type) {
    try {
      $new_value = $this->notificationSettingsManager->toggleSetting($notification_type);
    } catch (\Exception $exception) {
      $this->messenger
        ->addError(
          'Something wrong happened when toggling settings for @notification_type: @error',
          [
            '@notification_type' => $notification_type,
            '@error' => $exception->getMessage(),
          ]);
    }

    return new JsonResponse([
      'status' => $new_value ?? FALSE,
      'value' => $new_value ?? FALSE,
    ]);
  }

}
