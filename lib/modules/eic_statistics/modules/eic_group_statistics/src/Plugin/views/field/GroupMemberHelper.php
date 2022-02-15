<?php

namespace Drupal\eic_group_statistics\Plugin\views\field;

use Drupal\group\Entity\GroupContentInterface;
use Drupal\views\ResultRow;

/**
 * Views field hanlders helper class for group members.
 *
 * @ingroup views_field_handlers
 */
class GroupMemberHelper {

  /**
   * Looks for the group membership from a views result row and returns it.
   *
   * When working with group members in a view, the base entity might not be the
   * group_content entity itself but can be in relationships.
   * This function tries to find the group membership.
   *
   * @param \Drupal\views\ResultRow $values
   *   An object containing all retrieved values.
   * @param string $field
   *   Optional name of the field where the value is stored.
   */
  public static function getMembership(ResultRow $values, string $field = NULL) {
    $entity = $values->_entity;
    $membership = NULL;

    if ($entity instanceof GroupContentInterface) {
      /** @var \Drupal\group\Entity\GroupContentInterface $entity */
      if ($entity->getGroupContentType()->getContentPluginId() == 'group_membership') {
        $membership = $entity;
      }
    }
    else {
      // Depending on the base table of the view, the entity can be something
      // else than a GroupContent entity, so we need to check the relationships.
      /** @var \Drupal\group\Entity\GroupContentInterface $relationship_entity */
      foreach ($values->_relationship_entities as $relationship_entity) {
        if (!$relationship_entity instanceof GroupContentInterface) {
          continue;
        }

        // We get the first encountered membership.
        if ($relationship_entity->getGroupContentType()->getContentPluginId() == 'group_membership') {
          $membership = $relationship_entity;
          break;
        }
      }
    }

    return $membership;
  }

}
