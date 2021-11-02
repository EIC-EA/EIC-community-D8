<?php

/**
 * @file
 * Prepares variables for node Event templates.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_node__event() for event node.
 */
function eic_community_preprocess_node__event(array &$variables) {
  /** @var \Drupal\Core\Entity\EntityInterface $node */
  $node = $variables['node'];

  // Get the start date of the event.
  $start_date = 0;
  if (!$node->field_date_range->isEmpty()) {
    $start_date = strtotime($node->field_date_range->getValue()[0]['value']);
  }

  // Get the event type.
  $event_type_label = '';
  if (!$node->field_vocab_event_type->isEmpty()) {
    /** @var \Drupal\taxonomy\Entity\Term $event_type */
    $event_type = Term::load($node->field_vocab_event_type->getValue()[0]['target_id']);
    $event_type_label = $event_type->get('name')->getString();
  }

  // Get the topics.
  $tags = [];
  if (!$node->get('field_vocab_topics')->isEmpty()) {
    foreach ($node->get('field_vocab_topics')->referencedEntities() as $term) {
      $tags[] = _eic_community_preprocess_ecl_tag($term);
    }
  }

  $item = [
    'type' => [
      'label' => $event_type_label,
      'icon' => [
        'name' => $node->bundle(),
        'type' => 'custom',
      ],
    ],
    'tags' => $tags,
    'date' => _eic_community_preprocess_ecl_date_block($start_date),
    'timestamp' => [
      'label' => eic_community_get_teaser_time_display($node->get('published_at')->value),
    ],
    'author' => eic_community_get_teaser_user_display($node->getOwner()),
    'stats' => $stats,
  ];

  $variables['event_item'] = $item;
}
