<?php

namespace Drupal\eic_groups;

/**
 * Defines a list of content types that should not live outside a group.
 *
 * Class ForbiddenOrphanContents.
 */
final class ForbiddenOrphanContentTypes {

  /**
   * List of forbidden routes, with their parameters.
   *
   * @todo Maybe we should have this dynamic, based on the global permissions.
   *
   * @var array
   */
  const FORBIDDEN_ENTITY_ROUTES = [
    'node.add' => [
      'bundles' => [
        'discussion',
        'document',
        'event',
        'gallery',
        'news',
        'video',
        'wiki_page',
      ],
    ],
  ];

}
