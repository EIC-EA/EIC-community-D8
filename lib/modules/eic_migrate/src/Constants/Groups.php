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
   * Maps the old node types the new ones.
   *
   * @var array
   */
  const NODE_TYPE_MAPPING = [
    'book' => 'book',
    'discussion' => 'discussion',
    'document' => 'document',
    'event' => 'event',
    'news' => 'news',
    'photoalbum' => 'gallery',
    'article' => 'story',
    'wiki_page' => 'wiki_page',
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

  /**
   * Maps old node type names to new ones.
   *
   * @param string $old_node_type
   *   The old node type machine name.
   *
   * @return false|mixed|string
   *   The new group type machine name or FALSE if not found.
   */
  public static function getDestinationNodeType(string $old_node_type) {
    return self::NODE_TYPE_MAPPING[$old_node_type] ?? FALSE;
  }

}
