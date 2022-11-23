<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for page_banner.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_block() for page_banner.
 */
function eic_community_preprocess_block__page_banner(&$variables, &$cache_tags = []) {
  /** @var \Drupal\block_content\Entity\BlockContent $block_content */
  $block_content = $variables['content']['#block_content'];

  if (!$block_content->get('field_media')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($block_content->get('field_media')->entity, $block_content->language()->getId());

    switch ($media->bundle()) {
      case 'image':
        $image = $media->get('oe_media_image')->first();

        if (!$image) {
          return;
        }
        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $variables['hero']['image'] = $image_item->getSource();

        // @todo harmonise templates, so we don't need to make exceptions.
        switch ($variables['elements']['#id']) {
          case 'block_content_8d5fdf72_3a4e_4f98_ae22_2652ed4be3d3':
            $variables['overview_header'] = [
              'title' => $block_content->field_title->value,
              'image' => [
                'src' => $image_item->getSource(),
                'alt' => $image_item->getAlt(),
              ],
            ];
            break;
        }

        break;

    }
  }
}
