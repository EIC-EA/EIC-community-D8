<?php

namespace Drupal\eic_subscription_digest\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\eic_subscription_digest\Constants\DigestTypes;
use Drupal\eic_user\UserHelper;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class DigestManager
 *
 * @package Drupal\eic_subscription_digest\Service
 */
class DigestManager {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\eic_user\UserHelper
   */
  private $userHelper;

  public function __construct(StateInterface $state, AccountProxyInterface $account_proxy, UserHelper $user_helper) {
    $this->state = $state;
    $this->currentUser = $account_proxy;
    $this->userHelper = $user_helper;
  }

  /**
   * @param string $digest_type
   *
   * @return bool
   */
  public function shouldSend(string $digest_type): bool {

  }

  /**
   * @param bool $status
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setDigestStatus(bool $status): bool {
    $user = User::load($this->currentUser->id());
    if (!$user instanceof UserInterface) {
      throw new \InvalidArgumentException('Current user does not exist');
    }

    $profile = $this->userHelper->getUserMemberProfile($user);
    if (!$profile instanceof ProfileInterface) {
      return FALSE;
    }

    $profile->set('field_digest_status', $status);
    $profile->save();

    return $status;
  }

  /**
   * @param string $frequency
   *
   * @return string
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setDigestFrequency(string $frequency): string {
    if (!in_array($frequency, DigestTypes::getAll())) {
      throw new \InvalidArgumentException('Invalid frequency');
    }

    $user = User::load($this->currentUser->id());
    if (!$user instanceof UserInterface) {
      throw new \InvalidArgumentException('Current user does not exist');
    }

    $profile = $this->userHelper->getUserMemberProfile($user);
    if (!$profile instanceof ProfileInterface) {
      return FALSE;
    }


    $profile->set('field_digest_frequency', $frequency);
    $profile->save();

    return $frequency;
  }

}
