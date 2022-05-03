<?php

namespace Drupal\eic_messages\Util;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eic_messages\MessageIdentifierInterface;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\message\MessageTemplateInterface;

/**
 * Helper class for activity stream message templates.
 */
final class ActivityStreamMessageTemplates implements MessageIdentifierInterface {

  /**
   * Message template for inserted/updated articles.
   */
  const ARTICLE_INSERT_UPDATE = 'stream_article_insert_update';

  /**
   * Message template for inserted/updated events.
   */
  const EVENT_INSERT_UPDATE = 'stream_event_insert_update';

  /**
   * Message template for inserted/updated discussions.
   */
  const DISCUSSION_INSERT_UPDATE = 'stream_discussion_insert_update';

  /**
   * Message template for inserted/updated documents.
   */
  const DOCUMENT_INSERT_UPDATE = 'stream_document_insert_update';

  /**
   * Message template for inserted/updated galleries.
   */
  const GALLERY_INSERT_UPDATE = 'stream_gallery_insert_update';

  /**
   * Message template for inserted/updated videos.
   */
  const VIDEO_INSERT_UPDATE = 'stream_video_insert_update';

  /**
   * Message template for inserted/updated wiki pages.
   */
  const WIKI_INSERT_UPDATE = 'stream_wiki_page_insert_update';

  /**
   * Message template for inserted/updated comments.
   */
  const COMMENT_INSERT_UPDATE = 'stream_wiki_page_insert_update';

  /**
   * Used for shared content activity items.
   */
  const SHARE_CONTENT = 'stream_share_content';

  /**
   * Array of activity stream message templates per entity type.
   *
   * @var array
   */
  private static $templates = [
    'node' => [
      'article' => self::ARTICLE_INSERT_UPDATE,
      'event' => self::EVENT_INSERT_UPDATE,
      'discussion' => self::DISCUSSION_INSERT_UPDATE,
      'document' => self::DOCUMENT_INSERT_UPDATE,
      'gallery' => self::GALLERY_INSERT_UPDATE,
      'video' => self::VIDEO_INSERT_UPDATE,
      'wiki_page' => self::WIKI_INSERT_UPDATE,
    ],
    'comment' => [
      'node_comment' => self::COMMENT_INSERT_UPDATE,
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
   * {@inheritdoc}
   */
  public static function getMessageTemplatePrimaryKeys(MessageTemplateInterface $message_template) {
    // Get the message template type.
    $message_template_type = $message_template->getThirdPartySetting('eic_messages', 'message_template_type');

    if ($message_template_type != MessageTemplateTypes::STREAM) {
      return FALSE;
    }

    // We assume all stream messages reference a node.
    // @todo Define if all stream templates should also include executing user.
    return [
      'field_referenced_node',
    ];
  }

}
