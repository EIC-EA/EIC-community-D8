<?php

/**
 * @file
 * Prepares variables for block template.
 */

use Drupal\Core\Cache\Cache;
use Drupal\oe_theme\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_block() for all blocks.
 */
function eic_community_preprocess_block(array &$variables) {
  if ($variables['base_plugin_id'] == 'block_content') {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $variables['content']['#block_content'];

    // Initializes cache tags.
    $cache_tags = $block_content->getCacheTags();

    switch ($block_content->bundle()) {
      case 'page_banner':
        // Preprocess variables for page_banner block.
        if (!$block_content->get('field_media')->isEmpty()) {
          /** @var \Drupal\media\Entity\Media $media */
          $media = \Drupal::service('entity.repository')->getTranslationFromContext($block_content->get('field_media')->entity, $block_content->language()->getId());

          switch ($media->bundle()) {
            case 'image':
              $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
              $variables['hero']['image'] = $image_item->getSource();
              break;

          }
        }
        break;

      case 'facts_figures':
        // Preprocess variables for facts_figures block.
        if (!$block_content->get('field_facts_figures')->isEmpty()) {
          /** @var \Drupal\eic_statistics\StatisticsStorageInterface $eic_statistics_storage */
          $eic_statistics_storage = \Drupal::service('eic_statistics.storage');

          /** @var \Drupal\fragments\Entity\FragmentInterface[] $fragments */
          $fragments = $block_content->get('field_facts_figures')->referencedEntities();

          // Adds each fact and figure to the theme variables.
          foreach ($fragments as $fragment) {
            $fragment_translation = \Drupal::service('entity.repository')->getTranslationFromContext($fragment, $block_content->language()->getId());
            $cache_tags = Cache::mergeTags($cache_tags, $fragment->getCacheTags());

            // Extracts the entity type and bundle from
            // the value of field_fact_figure_type.
            $entity_info = explode('__', $fragment_translation->get('field_fact_figure_type')->getString());
            $entity_type = reset($entity_info);
            $bundle = end($entity_info);

            switch ($entity_type) {
              case 'user':
                $counter = $eic_statistics_storage->getEntityCounter($entity_type);
                $cache_tags[] = $eic_statistics_storage->getEntityCounterCacheTag($entity_type);
                break;

              default:
                $counter = $eic_statistics_storage->getEntityCounter($entity_type, $bundle);
                $cache_tags[] = $eic_statistics_storage->getEntityCounterCacheTag($entity_type, $bundle);
                break;

            }

            $variables['block_content']['items'][] = [
              'title' => eic_field_fact_figure_type_allowed_values()[$fragment_translation->get('field_fact_figure_type')->getString()],
              'description' => $fragment_translation->get('field_body')->value,
              'value' => $counter,
              'fact_figure_type' => $fragment_translation->get('field_fact_figure_type')->getString(),
            ];
          }
        }
        break;

      case 'social_media':
        // Adds social media link allowed values.
        $social_media_links = $block_content->get('field_social_media_links');
        $provider = $social_media_links->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getOptionsProvider('link_type', $social_media_links->getEntity());
        $variables['social_media_link_types'] = $provider->getPossibleOptions();
        break;

      case 'latest_news_stories':
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
        break;

    }

    // Adds cache tags.
    $variables['#cache']['tags'] = $cache_tags;
  }
}

/**
 * Implements hook_preprocess_eic_group_header_block() for eic_group_header block.
 */
function eic_community_preprocess_eic_group_header_block(array &$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  // Adds group banner image.
  if (!$group->get('field_hero')->isEmpty()) {
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_hero')->entity, $group->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $variables['group_values']['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }
}
