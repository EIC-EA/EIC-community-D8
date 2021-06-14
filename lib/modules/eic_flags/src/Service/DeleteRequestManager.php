<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_flags\Service\RequestManagerInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\flag\Entity\Flag;
use Drupal\flag\FlagService;
use Drupal\user\Entity\User;

/**
 * Class DeleteRequestManager
 *
 * @package Drupal\eic_flags\Service
 */
class DeleteRequestManager implements RequestManagerInterface {

  /**
   * Entity types allowed to go through the request a deletion flow.
   *
   * @var string[]
   */
  public static $supportedEntityTypes = [
    'node' => 'request_delete_content',
    'group' => 'request_delete_group',
    'comment' => 'request_delete_comment',
  ];

  /**
   * @var \Drupal\flag\FlagService
   */
  private $flagService;

  /**
   * DeleteRequestManager constructor.
   *
   * @param \Drupal\flag\FlagService $flagService
   */
  public function __construct(FlagService $flagService) {
    $this->flagService = $flagService;
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason) {
    // Entity type is not supported
    $class_name = strtolower((new \ReflectionClass($entity))->getShortName());
    if (!array_key_exists($class_name, self::$supportedEntityTypes)) {
      return NULL;
    }

    $current_user = \Drupal::currentUser();
    if (!$current_user->isAuthenticated()) {
      return NULL;
    }

    $current_user = User::load($current_user->id());
    $flag = $this->flagService->getFlagById(self::$supportedEntityTypes[$class_name]);
    if (!$flag instanceof Flag || $flag->isFlagged($entity, $current_user)) {
      return NULL;
    }

    $flag = $this->flagService->flag($flag, $entity, $current_user);
    $flag->set('field_deletion_reason', $reason);
    $flag->set('field_request_status', RequestStatus::OPEN);
    $flag->save();

    return $flag;
  }

}
