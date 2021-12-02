<?php

/**
 * @file
 * Contains implementation for hook_theme_suggestions_block_alter().
 */

use Drupal\block\BlockInterface;
use Drupal\eic_topics\TopicsManager;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 *
 * Add block region as a suggestion.
 */
function eic_community_theme_suggestions_block_alter(
  array &$suggestions,
  array $variables
) {
  $is_taxonomy_page = TopicsManager::isTopicPage();

  if (
    $is_taxonomy_page
    && isset($variables['elements']['#configuration']['region'])
    && 'content' === $variables['elements']['#configuration']['region']
    && 'views' === $variables['elements']['#configuration']['provider']
  )
  {
    $suggestions[] = 'block__global_block_taxonomy_term_page';
  }

  if ($is_taxonomy_page && 'system_main_block' === $variables['elements']['#plugin_id']) {
    $suggestions[] = 'block__system_main_block__taxonomy_term';
  }

  // Blocks that are not placed in a region might not have an id (if they
  // are rendered through a block_reference field for instance).
  if (!isset($variables['elements']['#id'])) {
    return;
  }
  /** @var \Drupal\block\Entity\Block $block */
  $block = \Drupal::entityTypeManager()
    ->getStorage('block')
    ->load($variables['elements']['#id']);
  if (!$block instanceof BlockInterface || !$block->getRegion()) {
    return;
  }
  $suggestions[] = 'block__region_' . $block->getRegion();
  $suggestions[] = 'block__region_' . $block->getRegion(
    ) . '__' . $variables['elements']['#id'];
}
