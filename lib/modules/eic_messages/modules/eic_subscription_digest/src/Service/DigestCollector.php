<?php

namespace Drupal\eic_subscription_digest\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Class DigestCollector
 *
 * @package Drupal\eic_subscription_digest\Service
 */
class DigestCollector {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   *
   * @return array
   */
  public function getList(UserInterface $user): array {
    
  }

}
