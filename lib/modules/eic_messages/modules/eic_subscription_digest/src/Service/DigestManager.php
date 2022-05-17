<?php

namespace Drupal\eic_subscription_digest\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
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

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   * @param \Drupal\eic_user\UserHelper $user_helper
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   */
  public function __construct(
    StateInterface $state,
    AccountProxyInterface $account_proxy,
    UserHelper $user_helper,
    EntityTypeManagerInterface $entity_type_manager,
    QueueFactory $queue_factory
  ) {
    $this->state = $state;
    $this->currentUser = $account_proxy;
    $this->userHelper = $user_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue_factory->get('subscription_digest');
  }

  /**
   * @param string $type
   *
   * @return bool
   * @throws \Exception
   */
  public function shouldSend(string $type): bool {
    $now = new \DateTime('now');
    $last_run = $this->state->get('eic_subscription_digest_' . $type . '_time');
    if (!$last_run) {
      return TRUE;
    }

    $last_run = \DateTime::createFromFormat('U', $last_run);
    $last_run->add(DigestTypes::getInterval($type));
    if ($now >= $last_run) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param string $type
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function queueDigest(string $type): bool {
    // Queue user digests
    $profile_ids = $this->entityTypeManager->getStorage('profile')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->condition('field_digest_status', TRUE)
      ->condition('field_digest_frequency', $type)
      ->execute();

    $message = [
      'digest_type' => $type,
    ];

    foreach ($profile_ids as $id) {
      $this->queue->createItem($message + ['uid' => $id]);
    }

    // Save the new run time
    $this->state->set('eic_subscription_digest_' . $type . '_time', (new \DateTime('now'))->getTimestamp());

    return TRUE;
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
