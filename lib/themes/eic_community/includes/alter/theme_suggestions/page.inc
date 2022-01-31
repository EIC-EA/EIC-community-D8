<?php

/**
 * @file
 * Contains implementation for hook_theme_suggestions_page_alter().
 */

/**
 * Implements hook_theme_suggestions_page_alter().
 */
function eic_community_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  // Adds suggestions for the front page.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    if (\Drupal::currentUser()->isAnonymous()) {
      $suggestions[] = 'page__front__anonymous';
    }
  }

  $route = \Drupal::routeMatch();
  if ($route->getRouteName() === 'entity.node.canonical') {
    $suggestions[] = 'page__node__' . $route->getParameter('node')->bundle();
  }
  if ($route->getRouteName() === 'entity.node.preview') {
    $suggestions[] = 'page__node__' .
      $route->getParameter('node_preview')->bundle();
    $suggestions[] = 'page__node__preview__' .
      $route->getParameter('node_preview')->bundle();
  }

  // If the current page is access denied page.
  if (\Drupal::routeMatch()->getRouteName() === 'system.403') {

    // Create a suggestion for group under review pages.
    if (\Drupal::service('eic_groups.helper')->isGroupUnderReviewPage(NULL)) {
      $suggestions[] = 'page__group__review';
    }
  }
}

/**
 * Implements hook_theme_suggestions_alter().
 */
function eic_community_theme_suggestions_alter(array &$suggestions, array $variables, $hook) {
  if (isset($variables['elements']['#view_mode'])) {
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');
    $suggestion = $hook . '__' . $sanitized_view_mode;
    if (!in_array($suggestion, $suggestions)) {
      $suggestions[] = $suggestion;
    }
  }
}
