<?php

/**
 * @file
 * Prepares variables for node video templates.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\group\Entity\GroupInterface;

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
      $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
      if ($group instanceof GroupInterface) {
        $variables['editorial_actions']['items'][]['content'] = _eic_community_get_share_group_content_link(
          $group,
          $node
        );

        $variables['#cache']['tags'] = array_merge($variables['#cache']['tags'], $group->getCacheTags());
        if (_eic_community_can_use_social_share($node)) {
          $variables['editorial_actions']['items'][]['content'] = _eic_community_get_social_share_block();
        }
      }
      break;

    case 'mail_teaser':
    case 'teaser':
      $teaser = _eic_community_prepare_node_teaser_array($node);

      // Get the thumbnail.
      if (!$node->get('field_image')->isEmpty()) {
        /** @var \Drupal\media\MediaInterface $media */
        $media = $node->get('field_image')->referencedEntities()[0];
        if ($media && !empty($media->get('thumbnail')->getValue())) {
          $style_name = 'gallery_teaser_crop_160x160';
          $teaser['image'] = ImageValueObject::fromStyledImageItem($media->get('thumbnail')->first(), $style_name);
        }
      }
      else {
        /** @var \Drupal\media\MediaInterface $media */
        $media = $node->get('field_video_media')->referencedEntities()[0];
        if ($media && !empty($media->get('thumbnail')->getValue())) {
          $style_name = 'gallery_teaser_crop_160x160';
          $teaser['image'] = ImageValueObject::fromStyledImageItem($media->get('thumbnail')->first(), $style_name);
        }
      }

      $teaser['type'] = [
        'label' => $node->type->entity->label(),
        'icon' => [
          'name' => 'media',
          'type' => 'custom',
        ],
      ];

      // Remove unwanted items.
      if (!empty($teaser['flags'])) {
        $teaser['flags'] = [];
      }

      // Privacy information, if current user is anonymous remove the author.
      if (\Drupal::currentUser()->isAnonymous()) {
        $teaser['author'] =  NULL;
      }

      // If we have flags, attach the js library.
      if (!empty($teaser['flags'])) {
        $variables['#attached']['library'][] = 'flag/flag.link_ajax';
      }

      $variables['video_item'] = $teaser;
      break;
  }
}
