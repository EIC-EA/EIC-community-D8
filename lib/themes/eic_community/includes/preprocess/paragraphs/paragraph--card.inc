<?php

/**
 * @file
 * Prepares variables for paragraph card template.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_paragraph() for card paragraph.
 */
function eic_community_preprocess_paragraph__card(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];
  // Adds paragraph variables from field_card_image.
  if (!$paragraph->get('field_card_image')->isEmpty()) {
      /** @var \Drupal\media\Entity\Media $media */
    $image_item = ImageValueObject::fromImageItem($paragraph->get('field_card_image')->first());
    $variables['paragraph_content']['image']['src'] = $image_item->getSource();
    $variables['paragraph_content']['image']['alt'] = $image_item->getAlt();
  }

  // Adds paragraph variables from field_title.
  if ($paragraph->hasField('field_title') && !$paragraph->get('field_title')->isEmpty()) {
    $variables['paragraph_content']['title'] = $paragraph->get('field_title')->value;
  }

  // Adds paragraph variables from field_body.
  if ($paragraph->hasField('field_body') && !$paragraph->get('field_body')->isEmpty()) {
    $variables['paragraph_content']['description'] = $variables['content']['field_body'];
  }
}
