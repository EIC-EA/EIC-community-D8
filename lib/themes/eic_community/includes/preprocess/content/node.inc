<?php

/**
 * @file
 * Prepares variables for node document templates.
 */

/**
 * Implements hook_preprocess_node() for document node.
 */
function eic_community_preprocess_node(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  // Adds node cache tags.
  if (!isset($variables['#cache']['tags'])) {
    $variables['#cache']['tags'] = $node->getCacheTags();
  }
}
