<?php

/**
 * @file
 * Contains implementation for docuemnt media preprocessor.
 */

use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_field__field_related_downloads(array &$variables): void {
  /** @var \Drupal\media\Entity\Media $file */
  $node = $variables['element']['#object'];
  $documents = [];

  foreach ($variables['element']['#items'] as $item) {
    $media = \Drupal\media\Entity\Media::load($item->target_id);
    $file = File::load($media->field_media_file->target_id);

    $document = [
      'title' => $media->getName(),
      'language' => $media->language()->getName(),
      'timestamp' => eic_community_get_teaser_time_display($media->get('changed')->getString()),
      'filesize' => format_size($file->get('filesize')->getString()),
      'highlight' => FALSE,
      'icon' => [
        'type' => 'custom',
        'name' => substr(strrchr($file->get('filemime')->getString(), '/'), 1),
      ],
    ];


    $documents[]= $document;
  }

  $variables['documents'] = $documents;
}
