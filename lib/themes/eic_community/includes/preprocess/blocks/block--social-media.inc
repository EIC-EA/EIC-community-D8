<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for social_media.
 */

/**
 * Implements hook_preprocess_block() for social_media.
 */
function eic_community_preprocess_block__social_media(&$variables) {
  /** @var \Drupal\block_content\Entity\BlockContent $block_content */
  $block_content = $variables['content']['#block_content'];

  // Adds social media link allowed values.
  $social_media_links = $block_content->get('field_social_media_links');
  $provider = $social_media_links->getFieldDefinition()
    ->getFieldStorageDefinition()
    ->getOptionsProvider('link_type', $social_media_links->getEntity());
  $variables['social_media_link_types'] = $provider->getPossibleOptions();
}
