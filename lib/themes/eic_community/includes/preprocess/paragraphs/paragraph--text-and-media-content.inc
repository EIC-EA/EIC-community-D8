<?php

/**
 * @file
 * Prepares variables for paragraph text and media content template.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_paragraph() for text_and_media_content paragraph.
 */
function eic_community_preprocess_paragraph__text_and_media_content(array &$variables) {
  /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
  $paragraph = $variables['paragraph'];

  $items = [];

  // Get body text.
  if (!$paragraph->get('field_body')->isEmpty()) {
    $items['content'] = $paragraph->get('field_body')->view();
  }

  // Get call to action link.
  if (!$paragraph->get('field_cta')->isEmpty()) {
    $link = $paragraph->get('field_cta')->first()->getValue();
    $items['call_to_action'] = [
      'link' => [
        'label' => $link['title'],
        'path' => $link['uri'],
      ],
    ];
  }

  // Add body and call to action items.
  $variables['paragraph_content']['items'] = [$items];

  // Add media field.
  if (!$paragraph->get('field_media')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph->get('field_media')->entity, $paragraph->language()->getId());

    switch ($media->bundle()) {
      case 'image':
        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $variables['paragraph_content']['media'] = [
          'image' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
        break;

    }
  }
}
