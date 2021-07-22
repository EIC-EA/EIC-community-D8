<?php

namespace Drupal\eic_groups;

/**
 * The goal of this class is to list node bundles
 * which are not allowed to exist without belonging to a group and group content
 *
 * Class ForbiddenOrphanContents
 */
final class ForbiddenOrphanContentTypes {

  const FORBIDDEN_ENTITIES = [
    'node.add' => [
      'bundles' => [
        'wiki_page',
        'discussion',
      ],
    ],
  ];

}
