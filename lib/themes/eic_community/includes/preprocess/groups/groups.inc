<?php

/**
 * @file
 * Prepares variables for Group template.
 */

/**
 * Implements hook_theme_suggestions_group_alter().
 */
function eic_community_theme_suggestions_group_alter(array &$suggestions, array $variables) {
  switch ($variables['elements']['#group']->bundle()) {
    case 'group':
      if ($variables['elements']['#view_mode'] == 'full') {
        $suggestions[] = "group__empty";
      }
      break;

  }
}
