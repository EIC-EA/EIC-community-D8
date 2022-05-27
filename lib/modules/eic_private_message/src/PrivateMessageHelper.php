<?php

namespace Drupal\eic_private_message;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;

/**
 * Provides a PrivateMessageHelper service.
 */
class PrivateMessageHelper {

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The contact settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $contactSettings;

  /**
   * Constructs a PrivateMessageHelper object.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(UserDataInterface $user_data, ConfigFactoryInterface $config_factory) {
    $this->userData = $user_data;
    $this->contactSettings = $config_factory->get('contact.settings');
  }

  /**
   * Check if a given user has private message enabled.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object.
   */
  public function userHasPrivateMessageEnabled(AccountInterface $account) {
    $is_enabled = $this->userData->get('contact', $account->id(), 'enabled') ??
      $this->contactSettings->get('user_default_enabled');
    return (bool) $is_enabled;
  }

}
