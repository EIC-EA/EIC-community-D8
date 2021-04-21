<?php

/**
 * @file
 * Prepares variables for paragraph full media content template.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_paragraph() for full_media_content paragraph.
 */
function eic_community_preprocess_paragraph__full_media_content(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  if (!$paragraph->get('field_media')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph->get('field_media')->entity, $paragraph->language()->getId());

    // Add media to the theme variables.
    switch ($media->bundle()) {
      case 'image':
        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $variables['paragraph_content']['image'] = $image_item->getSource();
        $variables['paragraph_content']['alt'] = $image_item->getAlt();
        break;

      case 'remote_video':
        $variables['paragraph_content']['embedded_media'] = $media->get('oe_media_oembed_video')->view('oe_theme_main_content');
        break;

    }
  }
}
