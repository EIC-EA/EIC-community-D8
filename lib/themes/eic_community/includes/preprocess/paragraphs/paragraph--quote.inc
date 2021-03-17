<?php

/**
 * @file
 * Prepares variables for paragraph quote template.
 */

use Drupal\oe_theme\ValueObject\ImageValueObject;

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
        $variables['paragraph_content']['author'] = $paragraph->get('field_name')->value;
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
        /** @var \Drupal\user\UserInterface $user */
        $user = $paragraph->get('field_user_ref')->entity;

        $full_name = [];

        // Gets first name.
        if (!$user->get('field_first_name')->isEmpty()) {
          $full_name[] = $user->get('field_first_name')->value;
        }
        // Gets last name.
        if (!$user->get('field_last_name')->isEmpty()) {
          $full_name[] = $user->get('field_last_name')->value;
        }
        // Add user full name to the theme variables.
        if (!empty($full_name)) {
          $variables['paragraph_content']['author'] = implode(' ', $full_name);
        }

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
