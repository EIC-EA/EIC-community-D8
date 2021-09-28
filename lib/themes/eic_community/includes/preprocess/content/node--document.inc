<?php

/**
 * @file
 * Prepares variables for node document templates.
 */

use Drupal\eic_media\MediaHelper;
use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_node() for document node.
 */
function eic_community_preprocess_node__document(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  if ($variables['teaser']) {

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
        $translated_lang_term = \Drupal::service('entity.repository')->getTranslationFromContext($lang_term, $language);
        $language = $translated_lang_term->getName();
      }
      else {
        $language = $lang_term->getName();
      }
    }

    $document = [
      'language' => $language,
      'timestamp' => eic_community_get_teaser_time_display($node->get('changed')->getString()),
      'filesize' => format_size($file->get('filesize')->getString()),
      'highlight' => FALSE,
      'icon' => [
        'type' => 'custom',
        'name' => substr(strrchr($file->get('filemime')->getString(), '/'), 1),
      ],
    ];

    $variables['document_item'] = $document;
  }
  else {
    // Content.
    $node = $variables['node'];
    $files = $node->get('field_document_media')->referencedEntities();
    $files_list = [];

    foreach ($files as $file) {
      $download = File::load($file->field_media_file->getValue()[0]['target_id']);
      $download_url = MediaHelper::formatMediaDownloadLink($file)->toString();
      $files_list[] = [
        'name' => $file->getName(),
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
        'path' => $download_url->toString(),
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
    $flags = array_filter($variables['elements'], function ($key) {
      return strpos($key, 'flag_') !== FALSE;
    }, ARRAY_FILTER_USE_KEY);

    foreach ($flags as $flag) {
      $variables['editorial_actions']['items'][] = [
        'content' => $flag,
      ];
    }
    _eic_community_display_topics($variables);
  }
}
