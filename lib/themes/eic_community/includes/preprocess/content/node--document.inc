<?php

/**
 * @file
 * Prepares variables for node document templates.
 */

use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_media\MediaHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;

/**
 * Implements hook_preprocess_node() for document node.
 */
function eic_community_preprocess_node__document(array &$variables) {
  /** @var \Drupal\node\NodeInterface $node */
  $node = $variables['node'];

  switch ($variables['view_mode']) {
    case 'teaser':
      $teaser = _eic_community_prepare_node_teaser_array($node);

      $teaser['type'] = [
        'label' => $node->type->entity->label(),
        'icon' => [
          'name' => 'media',
          'type' => 'custom',
        ],
      ];

      // Remove unwanted items.
      if (!empty($teaser['flags'])) {
        $supported_flags = ['like_content'];
        $teaser['flags'] = array_filter($teaser['flags'], function ($key) use ($supported_flags) {
          return in_array($key, $supported_flags);
        }, ARRAY_FILTER_USE_KEY);
      }

      // If we have flags, attach the js library.
      if (!empty($teaser['flags'])) {
        $variables['#attached']['library'][] = 'flag/flag.link_ajax';
      }

      $variables['document_item'] = $teaser;
      break;

    case 'small_teaser':
      // If document has no medias, we do nothing.
      if ($node->get('field_document_media')->isEmpty()) {
        return;
      }

      $document_media_items = $node->get('field_document_media')->referencedEntities();
      $document_media = reset($document_media_items);

      $file = $document_media->get('field_media_file')->entity;

      if (!$file) {
        return;
      }

      /** @var \Drupal\media\Entity\Media $document_media */
      $document_media = $node->get('field_document_media')->entity;
      $language = $node->language()->getName();

      // Gets the document language based on the field_language of the Media
      // type.
      if ($document_media->hasField('field_language') && !$document_media->get('field_language')->isEmpty()) {
        $lang_term = $document_media->get('field_language')->entity;
      }
      elseif (!$node->get('field_language')->isEmpty()) {
        // If the media document doesn't have a language we use the one from the
        // node.
        $lang_term = $node->get('field_language')->entity;
      }

      if (isset($lang_term)) {
        if ($lang_term->hasTranslation($node->language()->getId())) {
          $translated_lang_term = \Drupal::service('entity.repository')
            ->getTranslationFromContext($lang_term, $language);
          $language = $translated_lang_term->getName();
        }
        else {
          $language = $lang_term->getName();
        }
      }

      $file_type = strstr($file->get('filemime')->getString(), '/', TRUE);

      $document = [
        'language' => $language,
        'timestamp' => eic_community_get_teaser_time_display($node->get('changed')->getString()),
        'filesize' => format_size($file->get('filesize')->getString()),
        'highlight' => FALSE,
        'icon' => [
          'type' => in_array($file_type, ['video', 'image']) ? 'general' : 'custom',
          'name' => in_array($file_type,
            ['video', 'image']) ? $file_type : 'document_' . _eic_community_get_file_type_icon_name(
              pathinfo($file->getFilename(), PATHINFO_EXTENSION)
            ),
        ],
      ];

      $variables['document_item'] = $document;
      break;

    default:
      // Content.
      $node = $variables['node'];
      $files = $node->get('field_document_media')->referencedEntities();
      $files_list = [];

      foreach ($files as $file) {
        $download = File::load($file->field_media_file->getValue()[0]['target_id']);
        $download_url = MediaHelper::formatMediaDownloadLink($file)->toString();
        $files_list[] = [
          'name' => $file->getName(),
          'type' => t('File', [], ['context' => 'eic_community']),
          'stats' => [
            [
              'hide_label' => FALSE,
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
          'mime_type' => _eic_community_get_file_type_icon_name(
            pathinfo($download->getFilename(), PATHINFO_EXTENSION)
          ),
          'path' => $download_url,
        ];
      }

      $variables['file_list'] = [
        'title' => $node->getTitle(),
        'body' => $variables['content']['field_body'],
        'files' => $files_list,
        'icon_file_path' => $variables['eic_icon_path'],
        'download' => t('Download'),
      ];
      // Sidebar elements.
      $flags = array_filter($variables['elements'], function ($key) use ($node) {
        // If element is not a flag we skip it.
        if (strpos($key, 'flag') === FALSE) {
          return FALSE;
        }

        return _eic_community_get_flag_access(str_replace('flag_', '', $key), $node);
      }, ARRAY_FILTER_USE_KEY);

      foreach ($flags as $flag) {
        $variables['editorial_actions']['items'][] = [
          'content' => $flag,
        ];
      }

      $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
      if ($group instanceof GroupInterface) {
        $variables['editorial_actions']['items'][]['content'] = _eic_community_get_share_group_content_link(
          $group,
          $node
        );

        $visibility = \Drupal::service('oec_group_flex.group_visibility.storage')->load($group->id());
        if ($visibility instanceof GroupVisibilityRecordInterface
          && $visibility->getType() === GroupVisibilityType::GROUP_VISIBILITY_PUBLIC) {
          $variables['editorial_actions']['items'][]['content'] = _eic_community_get_social_share_block();
        }
      }
      _eic_community_display_topics($variables);
      break;

  }
}
