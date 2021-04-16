<?php

/**
 * @file
 * Prepares variables for paragraph banner template.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_paragraph() for banner paragraph.
 */
function eic_community_preprocess_paragraph__banner(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  // Adds paragraph variables from field_banner.
  if (!$paragraph->get('field_banner')->isEmpty()) {
    /** @var \Drupal\fragments\Entity\FragmentInterface $fragment_banner */
    $fragment_banner = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph->get('field_banner')->entity, $paragraph->language()->getId());

    // Sets default extra classes.
    $extra_classes = ['ecl-editable-hero-banner--is-wide'];

    // Sets background classes.
    if (!$fragment_banner->get('field_background_color')->isEmpty()) {
      $extra_classes[] = "ecl-editable-hero-banner--is-{$fragment_banner->get('field_background_color')->value}";
    }

    // Adds banner image.
    if (!$fragment_banner->get('field_background_image')->isEmpty()) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = \Drupal::service('entity.repository')->getTranslationFromContext($fragment_banner->get('field_background_image')->entity, $fragment_banner->language()->getId());

      $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
      $variables['paragraph_content']['image'] = $image_item->getSource();
    }

    // Adds extra classes.
    if (!empty($extra_classes)) {
      $variables['paragraph_content']['extra_classes'] = implode(' ', $extra_classes);
    }
  }
}
