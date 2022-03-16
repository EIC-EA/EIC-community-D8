<?php

/**
 * @file
 * Prepares variables for paragraph quote template.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_paragraph() for quote paragraph.
 */
function eic_community_preprocess_paragraph__quote(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  switch ($paragraph->get('paragraph_view_mode')->value) {
    case 'external_person':
      // Add external person name.
      if (!$paragraph->get('field_name')->isEmpty()) {
        $variables['paragraph_content']['name'] = $paragraph->get('field_name')->value;
        $variables['paragraph_content']['author'] = TRUE;
      }

      // Add external person image.
      if (!$paragraph->get('field_media')->isEmpty()) {
        /** @var \Drupal\media\Entity\Media $media */
        $media = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph->get('field_media')->entity, $paragraph->language()->getId());

        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $variables['paragraph_content']['image'] = [
          'src' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
      }
      break;

    case 'platform_member':
      if (!$paragraph->get('field_user_ref')->isEmpty()) {
        $variables['paragraph_content']['author'] = TRUE;
        /** @var \Drupal\user\UserInterface $user */
        $user = $paragraph->get('field_user_ref')->entity;

        // Add user full name to the theme variables.
        $variables['paragraph_content']['name'] = $user->getDisplayName();

        // Add user image.
        if (!$user->get('field_media')->isEmpty()) {
          /** @var \Drupal\media\Entity\Media $media */
          $media = $user->get('field_media')->entity;

          $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
          $variables['paragraph_content']['image'] = [
            'src' => $image_item->getSource(),
            'alt' => $image_item->getAlt(),
          ];
        }
      }
      break;

  }
}
