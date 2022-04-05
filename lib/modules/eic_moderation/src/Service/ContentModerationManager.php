<?php

namespace Drupal\eic_moderation\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\workflows\Transition;

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

  /**
   * Returns allowed transitions for the given entity, group and user account.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The moderated entity.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group context.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\workflows\TransitionInterface[]
   *   Array of allowed transitions.
   */
  public function getAllowedTransitions(ContentEntityInterface $entity, GroupInterface $group, AccountInterface $account) {
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $current_state = $entity->moderation_state->value ?
    $workflow->getTypePlugin()->getState($entity->moderation_state->value) :
    $workflow->getTypePlugin()->getInitialState($entity);

    // Check the group permissions.
    return array_filter($current_state->getTransitions(), function (Transition $transition) use ($workflow, $account, $group) {
      if ($group->hasPermission('use ' . $workflow->id() . ' transition ' . $transition->id(), $account)) {
        return TRUE;
      }
    });
  }

}
