<?php

/**
 * @file
 * Prepares variables for group type organisation templates.
 */

/**
 * Implements hook_preprocess_group__TYPE().
 */
function eic_community_preprocess_group__organisation(&$variables) {
  $current_time = \Drupal::time()->getCurrentTime();
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_render_event_detail_page($variables, $group);
      break;

    case 'teaser':
    case 'small_teaser':
      // Add event topics to the theme variables.
      if (!$group->get('field_vocab_topics')->isEmpty()) {
        $topics = $group->get('field_vocab_topics')->referencedEntities();
      }
  }
}
