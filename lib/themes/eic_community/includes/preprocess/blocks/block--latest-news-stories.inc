<?php

/**
 * @file
 * Contains implementation for ook_preprocess_block() for latest_news_stories.
 */

use Drupal\Core\Cache\Cache;

/**
 * Implements hook_preprocess_block() for latest_news_stories.
 */
function eic_community_preprocess_block__latest_news_stories(&$variables) {
  $block_content = $variables['content']['#block_content'];

  // Initializes cache tags.
  $cache_tags = $block_content->getCacheTags();
  $nodes = [];

  // Adds News/Stories to the array of nodes to process.
  if (!$block_content->get('field_articles')->isEmpty()) {
    $nodes = $block_content->get('field_articles')->referencedEntities();
  }

  // Adds featured News/Story to the beginning of array of nodes.
  if (!$block_content->get('field_featured_article')->isEmpty()) {
    array_unshift($nodes, $block_content->get('field_featured_article')->entity);
  }

  // Process the array of nodes and add it to the theme variables.
  foreach ($nodes as $node) {
    $node_translation = \Drupal::service('entity.repository')->getTranslationFromContext($node, $block_content->language()->getId());
    $node_author = $node_translation->getOwner();
    $time_ago = \Drupal::service('date.formatter')->formatTimeDiffSince($node_translation->get('published_at')->value, ['granularity' => 1]);

    $item = [
      'title' => $node_translation->label(),
      'description' => $node_translation->get('field_introduction')->value,
      'path' => $node_translation->toUrl()->toString(),
      'type' => [
        'label' => $node_translation->type->entity->label(),
        'icon' => [
          'name' => $node_translation->bundle(),
          'type' => 'custom',
        ],
      ],
      'timestamp' => [
        'label' => t('@time ago', ['@time' => $time_ago]),
      ],
      'author' => [
        'author' => $node_author->get('field_full_name')->value,
        'path' => $node_author->toUrl()->toString(),
      ],
    ];

    if (!$node_translation->get('field_header_visual')->isEmpty()) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = \Drupal::service('entity.repository')->getTranslationFromContext($node_translation->get('field_header_visual')->entity, $block_content->language()->getId());
      $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
      $item['media'] = [
        'image' => $image_item->getSource(),
      ];
    }

    $variables['news_stories_items'][] = $item;

    // Add node cache tags.
    $cache_tags[] = Cache::mergeTags($cache_tags, $node->getCacheTags());
  }
}
