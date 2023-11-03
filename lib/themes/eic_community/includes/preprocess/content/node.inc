<?php

/**
 * @file
 * Prepares variables for node document templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\node\NodeInterface;

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

  $variables['formatted_date']['label'] = eic_community_get_teaser_formatted_date_display($node->get('changed')->value);

  // Adds menu local tasks block to the theme variables.
  $menu_local_tasks_block = \Drupal::entityTypeManager()
    ->getStorage('block')
    ->load('eic_community_tabs');
  $variables['eic_local_tasks'] = \Drupal::entityTypeManager()
    ->getViewBuilder('block')
    ->view($menu_local_tasks_block);
}

/**
 * Prepares a teaser array for the given node.
 *
 * This function is a helper function to prepare the array.
 * Structure and values should be tailored for your specific use case.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node.
 *
 * @return array
 *   An array with following structure:
 *   - type: the node bundle label.
 *   - author: the author image linked to the user profile.
 *   - description: the teaser description. Defaults to empty.
 *   - tags: tags to be displayed. Defaults to field_vocab_topics.
 *   - language: language to be displayed. Defaults to field_language.
 *   - highlight: defaults to FALSE.
 *   - flags: array of flags available for this content and user as render
 *     arrays.
 *   - like: the like flag properties.
 *   - timestamp: the date to be displayed. Defaults to changed node property.
 *   - stats: statistics to be displayed. Defaults to an empty array.
 */
function _eic_community_prepare_node_teaser_array(NodeInterface $node) {
  // Get the topics.
  $tags = [];
  if ($node->hasField('field_vocab_topics') && !$node->get('field_vocab_topics')->isEmpty()) {
    foreach ($node->get('field_vocab_topics')->referencedEntities() as $term) {
      $tags[] = _eic_community_preprocess_ecl_tag($term)['tag'];
    }
  }

  // Get the language.
  $language = '';
  if (
    $node->hasField('field_language') &&
    !$node->get('field_language')->isEmpty() &&
    $language_entity = $node->get('field_language')->entity
  ) {
    $language = $language_entity->label();
  }

  // Get the content flags.
  $flags = [];
  foreach (\Drupal::service('flag')->getAllFlags($node->getEntityTypeId(), $node->bundle()) as $flag) {
    if (_eic_community_get_flag_access($flag->id(), $node)) {
      $flags[$flag->id()]['content'] = _eic_community_get_flag_render_array($flag->id(), $node);
    }
  }

  return [
    'type' => $node->type->entity->label(),
    'author' => eic_community_get_teaser_user_display($node->getOwner(), 'crop_80x80'),
    'description' => $node->hasField('field_body') ? Markup::create(Xss::filter($node->get('field_body')->value)) : '',
    'tags' => $tags,
    'language' => $language,
    'highlight' => FALSE,
    'flags' => $flags,
    'timestamp' => [
      'label' => eic_community_get_teaser_time_display($node->get('changed')->value),
    ],
    'formatted_date' => [
      'label' => eic_community_get_teaser_formatted_date_display($node->get('changed')->value),
    ],
    'stats' => _eic_community_get_entity_stats($node),
  ];
}
