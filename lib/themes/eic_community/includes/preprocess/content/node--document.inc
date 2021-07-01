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
}
