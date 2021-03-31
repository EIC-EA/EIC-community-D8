<?php

/**
 * @file
 * Prepares variables for node page template.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_node() for page node.
 */
function eic_community_preprocess_node__page(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  // Adds subtitle text.
  if (!$node->get('field_subtitle')->isEmpty()) {
    $variables['introduction_header']['meta'] = [
      ['label' => $node->get('field_subtitle')->value],
    ];
  }

  // Adds call to action link.
  if (!$node->get('field_page_cta')->isEmpty()) {
    $variables['introduction_header']['call_to_action'] = [
      'link' => [
        'label' => $node->get('field_page_cta')->first()->get('title')->getValue() ?? '',
        'path' => $node->get('field_page_cta')->first()->getUrl()->toUriString(),
      ],
    ];
  }

  // Adds media image.
  if (!$node->get('field_image')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($node->get('field_image')->entity, $node->language()->getId());

    switch ($media->bundle()) {
      case 'image':
        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $variables['media'] = [
          'image' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
        break;

    }
  }
}
