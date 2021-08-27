<?php

/**
 * @file
 * Prepares variables for node document templates.
 */

use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_node() for document node.
 */
function eic_community_preprocess_node__document(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  if ($variables['teaser']) {
    $file = File::load($node->get('field_document_media')->getString());

    if (!$file) {
      return;
    }

    $document = [
      'language' => $node->language()->getName(),
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
            'value' => 0,
          ],
          [
            'hide_label' => TRUE,
            'value' => format_size($download->get('filesize')->getString()),
          ],
        ],
        'mime_type' => substr(strrchr($download->get('filemime')->getString(), '/'), 1),
        'path' => $download->createFileUrl(FALSE),
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
