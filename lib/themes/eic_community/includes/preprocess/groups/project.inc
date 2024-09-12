<?php

/**
 * @file
 * Prepares variables for group type project templates.
 */

/**
 * Implements hook_preprocess_group__TYPE().
 */
function eic_community_preprocess_group__project(&$variables) {
  $current_time = \Drupal::time()->getCurrentTime();
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_render_project_detail_page($variables, $group);
      break;

    case 'teaser':
    case 'small_teaser':
    case 'mail_teaser':
      // Get event description.
  }
}

