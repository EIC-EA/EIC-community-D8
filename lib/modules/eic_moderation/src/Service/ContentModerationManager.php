<?php

namespace Drupal\eic_moderation\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_moderation\ModerationInformationInterface;

/**
 * Class that manages functions for the content moderation.
 *
 * @package Drupal\eic_moderation\Service
 */
class ContentModerationManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs a new ContentModerationManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The Moderation information service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModerationInformationInterface $moderation_information
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;

  }

  /**
   * Checks if an entity is moderated by the given workflow.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $workflow_name
   *   The machine name of the workflow to check.
   *
   * @return bool
   *   TRUE if the entity is moderated by the given workflow.
   */
  public function isSupportedByWorkflow(EntityInterface $entity, string $workflow_name): bool {
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return FALSE;
    }

    if ($this->moderationInformation->getWorkflowForEntity($entity)->id() !== $workflow_name) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Determines if the moderation state has changed.
   *
   * This function is to be used when an entity is being updated.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if moderation state has changed, FALSE otherwise.
   */
  public function isTransitioned(ContentEntityInterface $entity) {
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return FALSE;
    }

    if ($this->moderationInformation->getOriginalState($entity)->id() != $entity->moderation_state->value) {
      return TRUE;
    }

    return FALSE;
  }

}
