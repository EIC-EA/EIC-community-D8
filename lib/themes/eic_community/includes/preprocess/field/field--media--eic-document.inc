<?php

/**
 * @file
 * Contains implementation for docuemnt media preprocessor.
 */

use Drupal\eic_media\MediaHelper;

/**
 * Implements hook_preprocess_HOOK().
 */
function eic_community_preprocess_field__field_related_downloads(array &$variables): void {
  $documents = [];

  foreach ($variables['element']['#items'] as $item) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = $item->entity;
    $file = FALSE;

    // Load all fields definitions from the media and grab the file entity from
    // the file reference field.
    $entity_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('media', $media->bundle());
    foreach ($entity_fields as $field) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */

      // We already found the file, so we can break the loop.
      if ($file) {
        break;
      }

      switch ($field->getType()) {
        case 'file':
        case 'image':
          // We skip thumbnail property of the media.
          if ($field->getName() === 'thumbnail') {
            break;
          }

          $file = $media->get($field->getName())->entity;
          break;
      }
    }

    // This media has no file, so we skip it.
    if (!$file) {
      break;
    }

    $file_type = strstr($file->get('filemime')->getString(), '/', TRUE);

    $download_url = MediaHelper::formatMediaDownloadLink($media)->toString();

    $document = [
      'title' => $media->getName(),
      'language' => $media->language()->getName(),
      'timestamp' => eic_community_get_teaser_time_display($media->get('changed')->getString()),
      'filesize' => format_size($file->get('filesize')->getString()),
      'highlight' => FALSE,
      'path' => $download_url,
      'icon_file_path' => $variables['eic_icon_path'],
      'icon' => [
        'type' => $file_type === 'video' ? 'general' : 'custom',
        'name' => $file_type === 'video' ? $file_type : substr(strrchr($file->get('filemime')->getString(), '/'), 1),
      ],
    ];

    $documents[] = $document;
  }

  $variables['documents'] = $documents;
}
