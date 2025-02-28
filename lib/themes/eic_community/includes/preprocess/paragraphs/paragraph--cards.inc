<?php

/**
 * @file
 * Prepares variables for paragraph cards template.
 */

/**
 * Implements hook_preprocess_paragraph() for cards paragraph type.
 */
function eic_community_preprocess_paragraph__cards(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  // Initialize the array for template data.
  $variables['paragraph_content'] = [];

  // Ensure field_card exists and is not empty.
  if ($paragraph->hasField('field_card') && !$paragraph->get('field_card')->isEmpty()) {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('paragraph');

    foreach ($paragraph->get('field_card')->referencedEntities() as $card) {
      $variables['paragraph_content'][] = $view_builder->view($card, 'default'); // Render with 'default' view mode
    }
  }
}
