<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for cta_tiles.
 */

use Drupal\Core\Cache\Cache;

/**
 * Implements hook_preprocess_block() for cta_tiles.
 */
function eic_community_preprocess_block__cta_tiles(&$variables, &$cache_tags = []) {
  /** @var \Drupal\block_content\Entity\BlockContent $block_content */
  $block_content = $variables['content']['#block_content'];

  // Preprocess variables for cta_tiles block.
  if (!$block_content->get('field_cta_tiles')->isEmpty()) {
    /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
    $paragraphs = $block_content->get('field_cta_tiles')->referencedEntities();

    foreach ($paragraphs as $paragraph) {
      if (!$paragraph->hasField('field_cta_link')) {
        continue;
      }

      $paragraph_translation = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph, $block_content->language()->getId());
      $link = $paragraph_translation->get('field_cta_link')->first()->getValue();
      $cache_tags = Cache::mergeTags($cache_tags, $paragraph->getCacheTags());

      // Adds CTA Tiles to the theme variables.
      $variables['block_content']['items'][] = [
        'title' => $paragraph_translation->get('field_title')->value,
        'description' => check_markup(
          $paragraph_translation->get('field_body')->value,
          $paragraph_translation->get('field_body')->format
        ),
        'actions' => [
          [
            'link' => [
              'label' => $link['title'],
              'path' => $link['uri'],
            ],
            'extra_classes' => "ecl-link--button ecl-link--button-{$link['link_type']}",
          ],
        ],
      ];
    }
  }
}
