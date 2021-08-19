<?php

namespace Drupal\eic_messages\Util;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ActivityStreamMessageTemplates.
 */
final class ActivityStreamMessageTemplates {

  /**
   * Array of activity stream message templates per entity type.
   *
   * @var array
   */
  private static $templates = [
    'node' => [
      'discussion' => 'stream_discussion_insert_update',
      'wiki_page' => 'stream_wiki_page_insert_update',
      'document' => 'stream_document_insert_update',
      'gallery' => 'stream_gallery_insert_update',
    ],
    'comment' => [
      'node_comment' => 'stream_comment_insert_update',
    ],
  ];

  /**
   * Gets list of defined templates.
   *
   * @return array
   *   Array of templates.
   */
  public static function getAllowedTemplates(): array {
    $flat_values = call_user_func_array('array_merge', self::$templates);

    return array_values($flat_values);
  }

  /**
   * Returns an activity stream message template for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The template name.
   */
  public static function getTemplate(ContentEntityInterface $entity): string {
    if (!isset(self::$templates[$entity->getEntityTypeId()][$entity->bundle()])) {
      throw new \InvalidArgumentException('Invalid entity / bundle provided');
    }

    return self::$templates[$entity->getEntityTypeId()][$entity->bundle()];
  }

  /**
   * Checks if an entity has activity message template.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if the entity has activity message template.
   */
  public static function hasTemplate(ContentEntityInterface $entity): bool {
    try {
      self::getTemplate($entity);
    }
    catch (\InvalidArgumentException $e) {
      return FALSE;
    }

    return TRUE;
  }

}
