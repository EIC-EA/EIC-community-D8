<?php

/**
 * @file
 * Contains implementation for hook_preprocess_media__document__default().
 */

use Drupal\eic_community\DocumentMediaValueExtractor;

/**
 * Implements hook_preprocess_media__document__default().
 *
 * Prepares the default view mode for the document media type.
 */
function eic_community_preprocess_media__document__default(&$variables) {
  /** @var \Drupal\media\Entity\Media $media */
  $media = $variables['media'];
  $file_value = DocumentMediaValueExtractor::getFileValue($media);
  if (!$file_value || is_null($file_value)) {
    return;
  }

  $variables['file'] = $file_value;

  // Generate the file information for all available translations.
  foreach ($media->getTranslationLanguages() as $langcode => $language) {
    // We don't want to include the information of the current language again.
    if ($media->language()->getId() === $langcode) {
      continue;
    }

    $translation = $media->getTranslation($langcode);
    $translation_file_value = DocumentMediaValueExtractor::getFileValue($translation);
    if ($translation_file_value) {
      $variables['translations'][] = $translation_file_value;
    }
  }
}
