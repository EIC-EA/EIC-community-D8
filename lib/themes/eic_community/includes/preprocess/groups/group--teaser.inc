<?php

/**
 * @file
 * Prepares variables for group templates.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_group().
 */
function eic_community_preprocess_group(array &$variables) {
  /** @var \Drupal\group\Entity\Group $group */
  $group = $variables['group'];

  $item = [
    'owner' => eic_community_get_teaser_user_display($group->getOwner()),
    'stats' => [],
    'icon_file_path' => $variables['eic_icon_path'],
  ];

  if ($group->hasField('field_hero') && !$group->get('field_hero')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_hero')->entity, $group->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $item['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  // Move flags to group to pick them up in the group header.
  foreach($variables['elements'] as $key => $element) {
    if (strpos($key, 'flag_') !== false) {
      $variables['elements']['#group']->flags[$key] = $element;
    }
  }

  $variables['group_item'] = $item;
}
