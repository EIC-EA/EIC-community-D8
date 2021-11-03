<?php

namespace Drupal\eic_flags\Service;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\BlockFlagTypes;
use Drupal\flag\Entity\Flag;
use Drupal\flag\FlagService;

/**
 * Class EntityBlockHandler.
 *
 * @package Drupal\eic_flags\Service\Handler
 */
class EntityBlockHandler {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Flag service provided by the flag module.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * Core's moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * EntityBlockHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\flag\FlagService $flag_service
   *   Flag service provided by the flag module.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   Core's moderation information service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FlagService $flag_service,
    ModerationInformationInterface $moderation_information
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * Blocks the given entity..
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   *
   * @return bool
   *   Result of the block operation.
   */
  public function blockEntity(ContentEntityInterface $content_entity) {
    if ($this->moderationInformation->isModeratedEntity($content_entity)) {
      $content_entity->set('moderation_state', 'blocked');
    }
    else {
      $content_entity->set('status', FALSE);
    }

    $content_entity->save();
  }

  /**
   * Applies the given the corresponding flag to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The concerned entity.
   * @param string $reason
   *   Reason given when blocking the group.
   *
   * @return bool|null
   *   Result of the operation.
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason) {
    $support_entity_types = BlockFlagTypes::getSupportedEntityTypes();
    // Entity type is not supported.
    if (!array_key_exists($entity->getEntityTypeId(), $support_entity_types)) {
      return NULL;
    }

    $entity_moderation_state = $entity->get('moderation_state')->value;

    // Group is not published, so we do nothing.
    if ($entity_moderation_state !== 'published') {
      return NULL;
    }

    $current_user = \Drupal::currentUser();
    if (!$current_user->isAuthenticated()) {
      return NULL;
    }

    $flag = \Drupal::service('flag')->getFlagById(
      $support_entity_types[$entity->getEntityTypeId()]
    );

    if (!$flag instanceof Flag) {
      return NULL;
    }

    $flag = $this->entityTypeManager->getStorage('flagging')->create([
      'uid' => $current_user->id(),
      'session_id' => NULL,
      'flag_id' => $flag->id(),
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'global' => $flag->isGlobal(),
    ]);

    $flag->set('field_block_reason', $reason);
    $flag->save();
    return $flag;
  }

}
