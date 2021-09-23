<?php

/**
 * @file
 * Contains implementation for ook_preprocess_block() for latest_news_stories.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_block() for latest_news_stories.
 */
function eic_community_preprocess_block__latest_news_stories(&$variables, &$cache_tags = []) {
  $block_content = $variables['content']['#block_content'];

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
    if (!$node instanceof NodeInterface) {
      continue;
    }

    // We need to check the node access otherwise private nodes will always be
    // visible.
    if (!$node->access('view')) {
      continue;
    }

    $node_translation = \Drupal::service('entity.repository')->getTranslationFromContext($node, $block_content->language()->getId());
    $node_author = eic_community_get_teaser_user_display($node_translation->getOwner());
    $time_ago = \Drupal::service('date.formatter')->formatTimeDiffSince($node_translation->get('published_at')->value, ['granularity' => 1]);

    if (isset($variables['content']['node_stats'][$node->id()])) {
      $node_stats = $variables['content']['node_stats'][$node->id()];
    }

    // Add node statistics.
    $stats = [
      [
        'icon' => [
          'type' => 'custom',
          'name' => 'comment',
        ],
        'label' => t('Comments'),
        'value' => isset($node_stats) ? $node_stats['comments_count']['#value'] : 0,
      ],
      [
        'icon' => [
          'type' => 'custom',
          'name' => 'views',
        ],
        'label' => t('Views'),
        'value' => isset($node_stats) ? $node_stats['page_views']['#value'] : 0,
      ],
      [
        'icon' => [
          'type' => 'custom',
          'name' => 'like',
        ],
        'label' => t('Likes'),
        'value' => isset($node_stats['flag_counts']['#items']['like_content']) ? $node_stats['flag_counts']['#items']['like_content'] : 0,
      ],
    ];

    $item = [
      'title' => $node_translation->label(),
      'description' => Xss::filter($node_translation->get('field_introduction')->value),
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
      'author' => $node_author,
      'stats' => $stats,
    ];

    if (!$node_translation->get('field_header_visual')->isEmpty()) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = \Drupal::service('entity.repository')->getTranslationFromContext($node_translation->get('field_header_visual')->entity, $block_content->language()->getId());
      $file_uri = $media->get('oe_media_image')->first()->entity->getFileUri();
      $item['media'] = [
        'image' => ImageStyle::load('oe_theme_ratio_3_2_medium')->buildUrl($file_uri),
      ];
    }

    $variables['news_stories_items'][] = $item;

    // Add node cache tags.
    $cache_tags = Cache::mergeTags(
      $cache_tags,
      $node->getCacheTags(),
      ['eic_statistics:node:' . $node->id()]
    );
  }
}
