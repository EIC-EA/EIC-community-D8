<?php

namespace Drupal\eic_flags;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagServiceInterface;

/**
 * Provides helper methods for flags.
 */
class FlagHelper {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FlagHelper object.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(FlagServiceInterface $flag_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->flagService = $flag_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get a list of users that have flagged an entity with a given flag ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flagged entity.
   * @param array $flag_ids
   *   Array of flag machine names.
   * @param bool $include_anonymous
   *   Whether to include anonymous flaggings. Defaults to FALSE.
   *
   * @return array
   *   An array of users who have flagged the entity.
   */
  public function getFlaggingUsersByFlagIds(EntityInterface $entity, array $flag_ids = [], $include_anonymous = FALSE) {
    if (empty($flag_ids)) {
      return $this->flagService->getFlaggingUsers($entity);
    }
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();
    $query->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());
    if (!empty($flag_ids)) {
      $query->condition('flag_id', $flag_ids, 'IN');
    }

    if (!$include_anonymous) {
      $query->condition('uid', 0, '<>');
    }

    $ids = $query->execute();

    // Load the flaggings.
    $flaggings = $this->entityTypeManager->getStorage('flagging')->loadMultiple($ids);

    $users = [];
    foreach ($flaggings as $flagging) {
      $user_id = $flagging->get('uid')->first()->getValue()['target_id'];
      $users[$user_id] = $this->entityTypeManager->getStorage('user')->load($user_id);
    }

    return $users;
  }

  /**
   * Timeout is stored as days (int) value, we need to always get the seconds
   * value for comparison.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *
   * @return int
   */
  public function getRawTimedOutValue(FlaggingInterface $flagging): int {
    $timeout = $flagging->get(
      HandlerInterface::REQUEST_TIMEOUT_FIELD
    )->value ?: 0;

    return $timeout * 86400;
  }

  /**
   * Returns true if the request has timed out.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *
   * @return bool
   */
  public function isRequestTimedOut(FlaggingInterface $flagging): bool {
    $timeout = $this->getRawTimedOutValue($flagging);
    $created = $flagging->get('created')->value;

    return time() - ($created + $timeout) >= 0;
  }

}
