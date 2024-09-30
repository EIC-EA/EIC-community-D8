<?php

/**
 * @file
 * Prepares variables for group type project templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_search\SearchHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\paragraphs\ParagraphInterface;

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
      _eic_community_render_project_teaser($variables, $group);
      break;

    case 'small_teaser':
    case 'mail_teaser':
      // Get event description.
  }

  // About page preprocessing.
  if (isset($variables['elements']['owners'])) {
    _preprocess_about_page($variables);
  }
}

/**
 * Fill variables for project teaser.
 */
function _eic_community_render_project_teaser(&$variables, GroupInterface $group) {
  $teaser['description'] = $group->hasField('field_project_teaser') ? Markup::create(Xss::filter($group->get('field_project_teaser')->value)) : '';
  $teaser['project_grant_agreement'] = $group->hasField('field_project_grant_agreement_id') ? $group->get('field_project_grant_agreement_id')->value : '';

  if ($group->hasField('field_project_date')) {
    $date_range = $group->get('field_project_date')->first();
    $teaser['project_date'] = [
      'start_date' => $date_range->get('value')->getValue(),
      'end_date' => $date_range->get('end_value')->getValue(),
    ];
  }

  $variables['project_item'] = $teaser;
}
