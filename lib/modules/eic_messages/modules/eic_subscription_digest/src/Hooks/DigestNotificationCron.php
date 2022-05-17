<?php

namespace Drupal\eic_subscription_digest\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\eic_subscription_digest\Constants\DigestTypes;
use Drupal\eic_subscription_digest\Service\DigestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DigestNotificationCron
 *
 * @package Drupal\eic_message_subscriptions\Hooks
 */
class DigestNotificationCron implements ContainerInjectionInterface {

  /**
   * @var \Drupal\eic_subscription_digest\Service\DigestManager
   */
  private $manager;

  /**
   * @param \Drupal\eic_subscription_digest\Service\DigestManager $manager
   */
  public function __construct(DigestManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('eic_subscription_digest.manager'));
  }

  /**
   * @return void
   * @throws \Exception
   */
  public function sendNotifications(): void {
    $types = DigestTypes::getAll();
    foreach ($types as $type) {
      if (!$this->manager->shouldSend($type)) {
        continue;
      }

      $this->manager->queueDigest($type);
    }
  }

}
