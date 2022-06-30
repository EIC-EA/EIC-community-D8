<?php

/**
 * @file
 * Prepares variables for paragraph full text content template.
 */

/**
 * Implements hook_preprocess_paragraph() for full_text_content paragraph.
 */
function eic_community_preprocess_paragraph__full_text_content(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  switch ($paragraph->getParentEntity()->getEntityTypeId()) {
    case 'group':
      $container_classes = '';
      break;

    default:
      $container_classes = 'ecl-container';
      break;
  }
  $variables['container_classes'] = $container_classes;
}
