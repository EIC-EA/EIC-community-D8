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

    $language = $media->language()->getName();
    if ($media_language = _eic_community_get_entity_field_language($media)) {
      $language = $media_language;
    }

    $file_type = strstr($file->get('filemime')->getString(), '/', TRUE);

    $download_url = MediaHelper::formatMediaDownloadLink($media)->toString();

    $document = [
      'title' => $media->getName(),
      'language' => $language,
      'timestamp' => eic_community_get_teaser_time_display($media->get('changed')->getString()),
      'filesize' => format_size($file->get('filesize')->getString()),
      'entity' => $media,
      'highlight' => FALSE,
      'path' => $download_url,
      'icon_file_path' => $variables['eic_icon_path'],
      'icon' => [
        'type' => in_array($file_type, ['video', 'image']) ? 'general' : 'custom',
        'name' => in_array($file_type, ['video', 'image']) ? $file_type : 'document_' . _eic_community_get_file_type_icon_name(
          pathinfo($file->getFilename(), PATHINFO_EXTENSION)
        ),
      ],
    ];

    $documents[] = $document;
  }

  $variables['documents'] = $documents;
}
