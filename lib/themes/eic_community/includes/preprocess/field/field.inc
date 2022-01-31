<?php

/**
 * @file
 * Contains implementation for hook_preprocess_field().
 */

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
}
