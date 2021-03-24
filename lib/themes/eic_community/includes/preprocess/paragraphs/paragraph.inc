<?php

/**
 * @file
 * Prepares variables for paragraph template.
 */

/**
 * Implements hook_preprocess_paragraph() for all paragraph types.
 */
function eic_community_preprocess_paragraph(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  switch ($paragraph->bundle()) {
    case 'text_and_media_content':
      // We need to force running the preprocess hook because paragraphs view
      // mode doesn't triger.
      if (!in_array($variables['view_mode'], ['default', 'media_left'])) {
        eic_community_preprocess_paragraph__text_and_media_content($variables);
      }
      break;

  }
}
