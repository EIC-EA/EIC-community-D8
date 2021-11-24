<?php

/**
 * @file
 * Contains implementation for hook_theme_suggestions_block_alter().
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 *
 * Add block region as a suggestion.
 */
function eic_community_theme_suggestions_taxonomy_term_alter(array &$suggestions, array $variables) {
  if (empty($variables['elements']['#taxonomy_term'])) {
    return;
  }

  /** @var \Drupal\taxonomy\TermInterface $term */
  $term = $variables['elements']['#taxonomy_term'];

  $suggestions[] = 'taxonomy_term__' . $term->bundle() . '__' . $variables['elements']['#view_mode'];
}
