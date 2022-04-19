<?php

namespace Drupal\eic_migrate\Constants;

/**
 * Defines group related constants.
 *
 * @package Drupal\eic_migrate\Constants
 */
final class Groups {

  /**
   * Maps the old role ids the new ones.
   *
   * @var array
   */
  const GROUP_ROLES_MAPPING = [
    6 => 'group-administrator',
    9 => 'event-administrator',
    10 => 'organisation-administrator',
    // 3 => 'project-administrator',
    // 12 => 'event-attendee',
  ];

  /**
   * Maps the old group types the new ones.
   *
   * @var array
   */
  const GROUP_TYPES_MAPPING = [
    'group' => 'group',
    'organisation' => 'organisation',
    'event_site' => 'event',
  ];

  /**
   * Maps old group type names to new ones.
   *
   * @param string $old_group_type
   *   The old group type machine name.
   *
   * @return false|mixed|string
   *   The new group type machine name or FALSE if not found.
   */
  public static function getDestinationGroupType(string $old_group_type) {
    return self::GROUP_TYPES_MAPPING[$old_group_type] ?? FALSE;
  }

}
