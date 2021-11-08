<?php

/**
 * @file
 * Prepares variables for node video templates.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_node__bundle() for vide node.
 */
function eic_community_preprocess_node__video(array &$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_display_flags($variables);
      _eic_community_display_topics($variables);
      _eic_community_display_document_contributors($variables);

      // Provide the Social Share block.
      if ($share_block = _eic_community_get_social_share_block()) {
        $variables['social_share'] = $share_block;
      }
      break;

    case 'teaser':
      $teaser = _eic_community_prepare_node_teaser_array($node);

      // Get the thumbnail.
      /** @var \Drupal\media\MediaInterface $media */
      $media = $node->get('field_video_media')->referencedEntities()[0];
      if ($media && !empty($media->get('thumbnail')->getValue())) {
        $style_name = 'gallery_teaser_crop_160x160';
        $teaser['image'] = ImageValueObject::fromStyledImageItem($media->get('thumbnail')->first(), $style_name);
      }

      $teaser['type'] = [
        'label' => $node->type->entity->label(),
        'icon' => [
          'name' => 'media',
          'type' => 'custom',
        ],
      ];

      $variables['video_item'] = $teaser;
      break;
  }
}
