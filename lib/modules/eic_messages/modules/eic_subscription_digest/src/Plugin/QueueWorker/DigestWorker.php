<?php

namespace Drupal\eic_subscription_digest\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eic_subscription_digest\Service\DigestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker to process sending of message subscriptions.
 *
 * @QueueWorker(
 *   id = "subscription_digest",
 *   title = @Translation("Process message digests"),
 *   cron = {"time" = 60}
 * )
 */
class DigestWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\eic_subscription_digest\Service\DigestManager
   */
  private $manager;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\eic_subscription_digest\Service\DigestManager $manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DigestManager $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_subscription_digest.manager')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // TODO: Implement processItem() method.
  }

}
