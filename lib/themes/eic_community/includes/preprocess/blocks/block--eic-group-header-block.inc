<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for eic_group_header.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_eic_group_header_block() for eic_group_header block.
 */
function eic_community_preprocess_eic_group_header_block(array &$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  // Adds group banner image.
  if (!$group->get('field_hero')->isEmpty()) {
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_hero')->entity, $group->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $variables['group_values']['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }
}
