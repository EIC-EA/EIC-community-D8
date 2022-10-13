<?php

/**
 * @file
 * Prepares variables for node gallery templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_media\MediaHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\image\Entity\ImageStyle;

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

      $node = $variables['node'];
      $files = $node->get('field_gallery_slides')->referencedEntities();
      $files_list = [];
      $slides = [];

      foreach ($files as $file) {
        $media = $file->get('field_gallery_slide_media')->referencedEntities()[0];
        $download = File::load($media->oe_media_image->getValue()[0]['target_id']);
        $download_url = MediaHelper::formatMediaDownloadLink($media)->toString();
        $explode_file_name = explode('/', $download->getFileUri());
        // We grab the filename based on the file URI.
        $file_name = end($explode_file_name);
        // We use the filename based on the file URI if it has the same name as
        // the media entity.
        $file_name = $media->getName() === $file_name ? $file_name : $download->getFilename();

        $files_list[] = [
          'name' => $file_name,
          'type' => t('Image', [], ['context' => 'eic_community']),
          'stats' => [
            [
              'hide_label' => FALSE,
              'icon' => [
                'name' => 'download',
                'type' => 'custom',
              ],
              'label' => t('download(s)'),
              // @todo Add statistics value
              'value' => \Drupal::service('eic_media_statistics.entity_file_download_count')->getFileDownloads($file),
            ],
            [
              'hide_label' => TRUE,
              'value' => format_size($download->get('filesize')->getString()),
            ],
          ],
          'mime_type' => substr(strrchr($download->get('filemime')->getString(), '/'), 1),
          'path' => $download_url,
          'image' => [
            'src' => ImageStyle::load('oe_theme_ratio_3_2_medium')->buildUrl($download->getFileUri()),
            'alt' => $file_name,
          ],
        ];

        $slide = [
          'name' => $file_name,
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
          'path' => $download_url,
          'image' => [
            'src' => ImageStyle::load('oe_theme_small_2x_no_crop')->buildUrl($download->getFileUri()),
            'alt' => $file_name,
          ],
          'thumb' => [
            'src' => ImageStyle::load('media_library')->buildUrl($download->getFileUri()),
            'alt' => $file_name,
          ],
        ];

        // Adds slide legend.
        if (!$file->get('field_gallery_slide_legend')->isEmpty()) {
          $slide['description'] = $file->get('field_gallery_slide_legend')->getString();
        }

        $slides[] = $slide;
      }

      $variables['slides'] = [
        'files' => $slides,
        'title' => '',
        'body' => '',
        'icon_file_path' => $variables['eic_icon_path'],
      ];

      $variables['file_list'] = [
        'title' => t('File list (%count)', ['%count' => count($files)]),
        'body' => '',
        'files' => $files_list,
        'icon_file_path' => $variables['eic_icon_path'],
        'download' => t('Download'),
      ];
      break;
    case 'mail_teaser':
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

      // Remove unwanted items.
      if (!empty($teaser['flags'])) {
        $teaser['flags'] = [];
      }

      $teaser['images'] = $images;
      $teaser['type'] = [
        'label' => $node->type->entity->label(),
      ];

      // If we have flags, attach the js library.
      if (!empty($teaser['flags'])) {
        $variables['#attached']['library'][] = 'flag/flag.link_ajax';
      }

      $teaser['description'] = Markup::create(Xss::filter($node->get('field_body')->value));
      $variables['gallery_item'] = $teaser;
      break;
  }
}
