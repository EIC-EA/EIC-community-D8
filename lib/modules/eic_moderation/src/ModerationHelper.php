<?php

namespace Drupal\eic_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\user\UserInterface;

/**
 * Provides helper methods around (moderated) entities.
 */
class ModerationHelper {

  /**
   * Core's moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs a ModerationHelper object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   Core's moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_information) {
    $this->moderationInformation = $moderation_information;
  }

  /**
   * Checks if the entity is moderated and published.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE is entity is published.
   */
  public function isPublished(ContentEntityInterface $entity): bool {
    $is_published = FALSE;

    if ($workflow = $this->moderationInformation->getWorkflowForEntity($entity)) {
      switch ($workflow->id()) {
        case DefaultContentModerationStates::WORKFLOW_MACHINE_NAME:
          if ($entity->get('moderation_state')->value === DefaultContentModerationStates::PUBLISHED_STATE) {
            $is_published = TRUE;
          }
          break;

        case GroupsModerationHelper::WORKFLOW_MACHINE_NAME:
          if ($entity->get('moderation_state')->value === GroupsModerationHelper::GROUP_PUBLISHED_STATE) {
            $is_published = TRUE;
          }
          break;

        case EICContentModeration::MACHINE_NAME:
          if ($entity->get('moderation_state')->value === EICContentModeration::STATE_PUBLISHED) {
            $is_published = TRUE;
          }
          break;
      }
    }
    else {
      $check_entity = $entity;
      if ($entity instanceof GroupContentInterface) {
        $check_entity = $entity->getEntity();
      }

      if ($check_entity instanceof UserInterface) {
        $is_published = $check_entity->isActive();
      }
      elseif ($check_entity instanceof EntityPublishedInterface) {
        $is_published = $check_entity->isPublished();
      }
      else {
        // If entity type isn't one of the previous, we consider it as
        // published.
        $is_published = TRUE;
      }
    }

    return $is_published;
  }

  /**
   * Checks if the entity is moderated and archived.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return bool
   *   TRUE if entity is archived.
   */
  public function isArchived(ContentEntityInterface $entity): bool {
    $is_archived = FALSE;

    if ($workflow = $this->moderationInformation->getWorkflowForEntity($entity)) {
      switch ($workflow->id()) {
        case DefaultContentModerationStates::WORKFLOW_MACHINE_NAME:
          if ($entity->get('moderation_state')->value === DefaultContentModerationStates::ARCHIVED_STATE) {
            $is_archived = TRUE;
          }
          break;

        case GroupsModerationHelper::WORKFLOW_MACHINE_NAME:
          if ($entity->get('moderation_state')->value === GroupsModerationHelper::GROUP_ARCHIVED_STATE) {
            $is_archived = TRUE;
          }
          break;

        case EICContentModeration::MACHINE_NAME:
          if ($entity->get('moderation_state')->value === EICContentModeration::STATE_UNPUBLISHED) {
            $is_archived = TRUE;
          }
          break;
      }
    }

    return $is_archived;
  }

  /**
   * Checks if the entity is moderated and unpublished.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE is entity is published.
   */
  public function isUnpublished(ContentEntityInterface $entity): bool {
    $is_unpublished = FALSE;

    if ($workflow = $this->moderationInformation->getWorkflowForEntity($entity)) {
      switch ($workflow->id()) {
        case EICContentModeration::MACHINE_NAME:
          if ($entity->get('moderation_state')->value === EICContentModeration::STATE_UNPUBLISHED) {
            $is_unpublished = TRUE;
          }
          break;
      }
    }
    else {
      $check_entity = $entity;
      if ($entity instanceof GroupContentInterface) {
        $check_entity = $entity->getEntity();
      }

      if ($check_entity instanceof UserInterface) {
        $is_unpublished = $check_entity->isBlocked();
      }
      elseif ($check_entity instanceof EntityPublishedInterface) {
        $is_unpublished = !$check_entity->isPublished();
      }
    }

    return $is_unpublished;
  }

  /**
   * Checks if the entity is moderated and draft.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return bool
   *   TRUE if entity is archived.
   */
  public function isDraft(ContentEntityInterface $entity): bool {
    $is_draft = FALSE;

    if ($workflow = $this->moderationInformation->getWorkflowForEntity($entity)) {
      switch ($workflow->id()) {
        case DefaultContentModerationStates::WORKFLOW_MACHINE_NAME:
          if ($entity->get('moderation_state')->value === DefaultContentModerationStates::DRAFT_STATE) {
            $is_draft = TRUE;
          }
          break;

        case GroupsModerationHelper::WORKFLOW_MACHINE_NAME:
          if ($entity->get('moderation_state')->value === GroupsModerationHelper::GROUP_DRAFT_STATE) {
            $is_draft = TRUE;
          }
          break;

        case EICContentModeration::MACHINE_NAME:
          if ($entity->get('moderation_state')->value === EICContentModeration::STATE_DRAFT) {
            $is_draft = TRUE;
          }
          break;
      }
    }

    return $is_draft;
  }

}
