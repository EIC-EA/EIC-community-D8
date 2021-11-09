<?php

/**
 * @file
 * Prepares variables for node gallery templates.
 */

use Drupal\eic_media\MediaHelper;
use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_node__bundle() for gallery node.
 */
function eic_community_preprocess_node__gallery(array &$variables) {
  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_display_flags($variables);
      _eic_community_display_topics($variables);
      _eic_community_display_document_contributors($variables);
      $variables['editorial_actions']['items'][]['content'] = _eic_community_get_social_share_block();

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
  }
}
