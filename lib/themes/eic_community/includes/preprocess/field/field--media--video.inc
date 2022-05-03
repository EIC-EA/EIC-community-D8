<?php

/**
 * @file
 * Contains implementation for video media preprocessor.
 */

use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_field__field_video_media(array &$variables): void {
  eic_community_preprocess_field__field_related_downloads($variables);
  foreach ($variables['documents'] as &$document) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $document['entity'];
    $file = $media->get('field_media_video_file')->entity;

    $document['file_name'] = StreamWrapperManager::getTarget($file->get('uri')->value);
  }

}
