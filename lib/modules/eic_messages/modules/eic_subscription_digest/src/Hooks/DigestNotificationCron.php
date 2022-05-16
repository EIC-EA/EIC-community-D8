<?php

namespace Drupal\eic_subscription_digest\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DigestNotificationCron
 *
 * @package Drupal\eic_message_subscriptions\Hooks
 */
class DigestNotificationCron implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static('state');
  }

  /**
   * @param string $digest_type
   *
   * @return void
   */
  public function sendNotifications(string $digest_type): bool {

  }

}
