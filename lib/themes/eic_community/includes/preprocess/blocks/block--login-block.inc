<?php

/**
 * @file
 * Contains preprocessor for block__login-block.
 */

/**
 * Implements hook_preprocess_block() for login_block.
 */
function eic_community_preprocess_block__login_block(&$variables, &$cache_tags = []) {
  $variables['auth'] = [
    'title' => $variables['content']['title'],
    'login' => [
      'label' => $variables['content']['login_link']->getText(),
      'path' => $variables['content']['login_link']->getUrl(),
    ],
    'register' => [
      'label' => $variables['content']['registration_link']->getText(),
      'path' => $variables['content']['registration_link']->getUrl(),
    ],
    'icon_file_path' => $variables['eic_icon_path'],
  ];
}
