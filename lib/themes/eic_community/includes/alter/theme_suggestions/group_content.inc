<?php

/**
 * @file
 * Prepares variables for Group template.
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function eic_community_theme_suggestions_group_content_alter(array &$suggestions, array $variables) {
  $suggestions = [];

  /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
  $group_content = $variables['elements']['#group_content'];
  $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
  $sanitized_plugin = strtr($group_content->getGroupContentType()->getContentPluginId(), '-', '_');

  $suggestions[] = 'group_content__' . $sanitized_plugin;
  $suggestions[] = 'group_content__' . $sanitized_plugin . '__' . $sanitized_view_mode;

  return $suggestions;
}
