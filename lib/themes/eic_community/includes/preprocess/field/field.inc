<?php

/**
 * @file
 * Contains implementation for hook_preprocess_field().
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_preprocess_field().
 *
 * Add paragraph formatting to body.
 */
function eic_community_preprocess_field(&$variables, $hook): void {
  if ($variables['field_name'] === 'body') {
    $variables['attributes']['class'][] = 'ecl-editor';
  }

  if ($variables['field_name'] === 'field_body') {
    switch ($variables['element']['#entity_type']) {
      case 'group':
        // Add attributes on field_body for the group about page.
        if ($variables['element']['#view_mode'] === 'about_page') {
          $variables['attributes']['id'] = 'group-description-full';
        }
        break;

    }
  }

  // Add the ECL image class to the image fields.
  $field_name = $variables['field_name'];
  if (in_array($field_name, ['oe_media_avportal_photo'])) {
    foreach ($variables['items'] as &$item) {
      $item['content']['#attributes']['class'][] = 'ecl-u-width-100';
      $item['content']['#attributes']['class'][] = 'ecl-u-height-auto';
    }
  }

  if ($variables['field_name'] === 'oe_related_links') {
    foreach ($variables['items'] as $delta => $item) {
      $variables['items'][$delta]['content'] = [
        '#type' => 'pattern',
        '#id' => 'list_item',
        '#variant' => 'default',
        '#fields' => [
          'url' => $item['content']['#url'],
          'title' => $item['content']['#title'],
        ],
      ];
    }
  }

  $field_type = $variables['field_type'];
  switch ($field_type) {
    case 'text_long':
    case 'text_with_summary':
      // Adds custom css classes to long text fields.
      $variables['attributes']['class'][] = str_replace('_', '-', "$field_type") . " ckeditor__content";
      foreach ($variables['items'] as $item) {
        $text_format = $item['content']['#format'];
        if (count($variables['items']) === 1) {
          $variables['attributes']['class'][] = str_replace('_', '-', "$field_type--$text_format");
          break;
        }
        $item['attributes']->addClass(str_replace('_', '-', "$field_type--$text_format"));
      }
      break;

  }
}

/**
 * Gets the language field of a given entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 *
 * @return string|bool
 *   Return the language term name or FALSE if not found.
 */
function _eic_community_get_entity_field_language(EntityInterface $entity) {
  $language = FALSE;
  // Gets the entity language field if exists.
  if ($entity->hasField('field_language') && !$entity->get('field_language')->isEmpty()) {
    /** @var \Drupal\taxonomy\TermInterface[] $languages */
    $languages = $entity->get('field_language')->referencedEntities();
    $language = $languages[0]->getName();
    if ($languages[0]->hasTranslation($entity->language()->getId())) {
      $language = $languages[0]->getTranslation($entity->language()->getId())
        ->getName();
    }
  }
  return $language;
}
