<?php

/**
 * @file
 * Prepares variables for node document templates.
 */

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

    $document_media = reset($node->get('field_document_media')->referencedEntities());

    $file = $document_media->get('field_media_file')->entity;

    if (!$file) {
      return;
    }

    $document_media = $node->get('field_document_media')->entity;
    $language = $node->language()->getName();

    // Gets the document language based on the field_language of the Media
    // type.
    if (!$document_media->get('field_language')->isEmpty()) {
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
}
