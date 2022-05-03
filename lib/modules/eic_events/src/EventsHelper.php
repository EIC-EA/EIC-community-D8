<?php

namespace Drupal\eic_events;

use Drupal\group\Entity\GroupInterface;

/**
 * EventsHelper service that provides helper functions for events.
 */
class EventsHelper {

  /**
   * Sets default values for defined fields that don't have a value yet.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  public static function setRequiredFieldsDefaultValues(GroupInterface &$group): void {
    $fields = [
      'field_body' => [
        'value' => ' ',
        'format' => 'filtered_html',
      ],
    ];

    foreach ($fields as $field_name => $values) {
      // Check if field exists.
      if (!$group->hasField($field_name)) {
        continue;
      }

      // Check if field is empty.
      if (!$group->get($field_name)->isEmpty()) {
        continue;
      }

      // Set the values for this field.
      foreach ($values as $key => $value) {
        $group->{$field_name}->{$key} = $value;
      }
    }
  }

}
