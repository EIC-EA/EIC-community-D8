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
    ]);

  if ($variables['base_plugin_id'] == 'block_content') {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $variables['content']['#block_content'];

    // Initializes cache tags.
    $cache_tags = $block_content->getCacheTags();

    switch ($block_content->bundle()) {
      case 'page_banner':
        eic_community_preprocess_block__page_banner($variables);
        break;

      case 'cta_tiles':
        // Preprocess variables for cta_tiles block.
        if (!$block_content->get('field_cta_tiles')->isEmpty()) {
          /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
          $paragraphs = $block_content->get('field_cta_tiles')->referencedEntities();

          foreach ($paragraphs as $paragraph) {
            $paragraph_translation = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph, $block_content->language()->getId());
            $link = $paragraph_translation->get('field_cta_link')->first()->getValue();
            $cache_tags = Cache::mergeTags($cache_tags, $paragraph->getCacheTags());

            // Adds CTA Tiles to the theme variables.
            $variables['block_content']['items'][] = [
              'title' => $paragraph_translation->get('field_title')->value,
              'description' => $paragraph_translation->get('field_body')->value,
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
        break;

      case 'facts_figures':
        eic_community_preprocess_block__facts_figures($variables);
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
        eic_community_preprocess_block__latest_news_stories($variables);
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
