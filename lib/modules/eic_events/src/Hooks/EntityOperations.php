<?php

namespace Drupal\eic_events\Hooks;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_events\Constants\Event;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_field_access().
   */
  public function entityFieldAccess(
    $operation,
    FieldDefinitionInterface $field_definition,
    AccountInterface $account,
    FieldItemListInterface $items = NULL
  ) {
    return $this->handleLegacyParagraphs($operation, $field_definition, $account, $items);
  }

  /**
   * Determines access to legacy paragraphs.
   */
  protected function handleLegacyParagraphs(
    $operation,
    FieldDefinitionInterface $field_definition,
    AccountInterface $account,
    FieldItemListInterface $items = NULL
  ) {
    $access = AccessResult::neutral();

    // We don't want users to add new paragraphs. Only the ones that were
    // migrated for legacy reasons can be accessed.
    if ($operation == 'edit' && $field_definition->getName() === Event::LEGACY_PARAGRAPHS_FIELD) {
      // If there are no items,we deny access.
      if ($items->isEmpty()) {
        return AccessResult::forbidden();
      }
      else {
        return AccessResult::allowed();
      }
    }

    return $access;
  }

}
