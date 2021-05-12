<?php

/**
 * @file
 * Prepares variables for node News and Story templates.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_node__story() for story node.
 */
function eic_community_preprocess_node__story(array &$variables) {
  _eic_community_preprocess_node_news_story($variables);
}

/**
 * Implements hook_preprocess_node__news() for news node.
 */
function eic_community_preprocess_node__news(array &$variables) {
  _eic_community_preprocess_node_news_story($variables);
}

/**
 * Custom function to preprocess News and Story node.
 */
function _eic_community_preprocess_node_news_story(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  $item = [
    'type' => [
      'label' => $node->type->entity->label(),
      'icon' => [
        'name' => $node->bundle(),
        'type' => 'custom',
      ],
    ],
    'timestamp' => [
      'label' => eic_community_get_teaser_time_display($node->get('published_at')->value),
    ],
    'author' => eic_community_get_teaser_user_display($node->getOwner()),
  ];

  if (!$node->get('field_header_visual')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($node->get('field_header_visual')->entity, $node->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $item['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  $variables['story_item'] = $item;
}
