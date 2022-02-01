<?php

/**
 * @file
 * Prepares variables for block template.
 */

/**
 * Implements hook_preprocess_block() for all blocks.
 */
function eic_community_preprocess_block(array &$variables) {
  // Style block titles using ECL.
  $variables['title_attributes'] = array_merge_recursive($variables['title_attributes'],
    [
      'class' => [
        'ecl-u-type-heading-2',
      ],
    ]
  );
  $variables['icon_file_path'] = $variables['eic_icon_path'];

  if ($variables['base_plugin_id'] == 'block_content') {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $variables['content']['#block_content'];

    // Initializes cache tags.
    $cache_tags = $block_content->getCacheTags();

    switch ($block_content->bundle()) {
      case 'page_banner':
        eic_community_preprocess_block__page_banner($variables, $cache_tags);
        break;

      case 'cta_tiles':
        eic_community_preprocess_block__cta_tiles($variables, $cache_tags);
        break;

      case 'facts_figures':
        eic_community_preprocess_block__facts_figures($variables, $cache_tags);
        break;

      case 'social_media':
        eic_community_preprocess_block__social_media($variables, $cache_tags);
        break;

      case 'latest_news_stories':
        eic_community_preprocess_block__latest_news_stories($variables, $cache_tags);
        break;

      case 'basic':
        eic_community_preprocess_block__basic($variables, $cache_tags);
        break;

    }

    // Adds cache tags.
    $variables['#cache']['tags'] = $cache_tags;
  }

  // Preprocess group content menu plugin blocks.
  if (in_array($variables['base_plugin_id'], [
    'group_content_menu',
    'eic_group_content_menu',
  ])) {
    eic_community_preprocess_block__group_menu($variables);
  }
}
