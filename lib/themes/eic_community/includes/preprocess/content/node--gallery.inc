<?php

/**
 * @file
 * Prepares variables for node gallery templates.
 */

use Drupal\eic_media\MediaHelper;
use Drupal\file\Entity\File;
use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_node__bundle() for gallery node.
 */
function eic_community_preprocess_node__gallery(array &$variables) {
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
      $node = $variables['node'];
      $files = $node->get('field_gallery_slides')->referencedEntities();
      $files_list = [];

      foreach ($files as $file) {
        $media = $file->get('field_gallery_slide_media')->referencedEntities()[0];
        $download = File::load($media->oe_media_image->getValue()[0]['target_id']);

        $files_list[] = [
          'name' => $download->getFileName(),
          'stats' => [
            [
              'hide_label' => TRUE,
              'icon' => [
                'name' => 'download',
                'type' => 'custom',
              ],
              'label' => t('downloads'),
              // @todo Add statistics value
              'value' => \Drupal::service('eic_media_statistics.entity_file_download_count')->getFileDownloads($file),
            ],
            [
              'hide_label' => TRUE,
              'value' => format_size($download->get('filesize')->getString()),
            ],
          ],
          'mime_type' => substr(strrchr($download->get('filemime')->getString(), '/'), 1),
          'path' => MediaHelper::formatMediaDownloadLink($media)->toString(),
        ];
      }

      $variables['file_list'] = [
        'title' => t('Files (%count)', ['%count' => count($files)]),
        'body' => '',
        'files' => $files_list,
        'icon_file_path' => $variables['eic_icon_path'],
        'download' => t('Download'),
      ];
      break;

    case 'teaser':
      $teaser = _eic_community_prepare_node_teaser_array($node);

      // Prepare images.
      $images = [];
      foreach ($node->get('field_gallery_slides')->referencedEntities() as $index => $paragraph) {
        $media = $paragraph->get('field_gallery_slide_media')->referencedEntities()[0];
        $style_name = $index === 0 ? 'gallery_teaser_crop_160x160' : 'crop_50x50';
        $image_item = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), $style_name);
        $images[] = $image_item;
      }

      $teaser['images'] = $images;
      $teaser['type'] = [
        'label' => $node->type->entity->label(),
      ];
      $variables['gallery_item'] = $teaser;

      // If we have flags, attach the js library.
      if (!empty($teaser['flags'])) {
        $variables['#attached']['library'][] = 'flag/flag.link_ajax';
      }
      break;
  }
}
