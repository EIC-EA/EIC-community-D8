<?php

/**
 * @file
 * Prepares variables for paragraph cta tile with image template.
 */

use Drupal\Core\Url;
use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_paragraph() for cta tile with image paragraph.
 */
function eic_community_preprocess_paragraph__cta_tile_with_image(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];
  // Adds paragraph variables from field_media.
  if (!$paragraph->get('field_media')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph->get('field_media')->entity, $paragraph->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $variables['paragraph_content']['image']['src'] = $image_item->getSource();
    $variables['paragraph_content']['image']['alt'] = $image_item->getAlt();
  }

  // Adds paragraph variables from field_title.
  if ($paragraph->hasField('field_title') && !$paragraph->get('field_title')->isEmpty()) {
    $variables['paragraph_content']['title'] = $paragraph->get('field_title')->value;
  }

  // Adds paragraph variables from field_cta_tile_link.
  if ($paragraph->hasField('field_cta_tile_link') && !$paragraph->get('field_cta_tile_link')->isEmpty()) {
    $variables['paragraph_content']['path'] = Url::fromUri($paragraph->get('field_cta_tile_link')->uri)->toString();
  }
}
