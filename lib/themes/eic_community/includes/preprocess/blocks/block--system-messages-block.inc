<?php

/**
 * @file
 * Prepare system messages for storybook template.
 */

/**
 * Implements hook_preprocess_block__system_messages_block().
 *
 * Add required variables to follow ECL guidelines.
 */
function eic_community_preprocess_block__system_messages_block(&$variables) {
  $messages = \Drupal::messenger()->all();
  // Show messages once.
  \Drupal::messenger()->deleteAll();
  $message_blocks = [];

  foreach ($messages as $type => $type_messages) {
    $icon_name = $type === 'status' ? 'information' : $type;

    $message_blocks[] = [
      '#theme' => 'eic_community_state_banner',
      '#icon' => [
        'name' => $icon_name,
        'type' => 'notifications',
      ],
      '#icon_file_path' => $variables['eic_icon_path'],
      '#description' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $type_messages,
      ],
    ];
  }

  $variables['message_blocks'] = $message_blocks;
}
