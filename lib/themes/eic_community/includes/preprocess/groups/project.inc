<?php

/**
 * @file
 * Prepares variables for group type project templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\group\Entity\GroupInterface;
use Drupal\eic_helper\DateTimeHelper;

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
 *
 * @param array $variables
 * @param \Drupal\group\Entity\GroupInterface $group
 *
 * TODO: Prepare the rest of the fields.
 *       Check /themes/custom/eic_community/patterns/compositions/project/project.teaser.html.twig
 */
function _eic_community_render_project_teaser(&$variables, GroupInterface $group) {
  $teaser['description'] = $group->hasField('field_project_teaser') ? Markup::create(Xss::filter($group->get('field_project_teaser')->value)) : '';
  $teaser['project_grant_agreement'] = $group->hasField('field_project_grant_agreement_id') ? $group->get('field_project_grant_agreement_id')->value : '';
  $teaser['project_teaser'] = $group->hasField('field_project_teaser') ? Markup::create(Xss::filter($group->get('field_project_teaser')->value)) : '';
  $teaser['project_funding_programme'] = $group->hasField('field_project_funding_programme') ? $group->get('field_project_funding_programme')->referencedEntities()[0]->getName() : '';
  // TODO: Get project fields of science from group object.
  $teaser['project_fields_of_science'] = array_filter($variables['content']['field_project_fields_of_science'], function ($key) {
    return strpos($key, '#') === FALSE;
  }, ARRAY_FILTER_USE_KEY);
  $teaser['project_horizon_results'] = $group->hasField('field_project_horizon_results') ? $group->get('field_project_horizon_results')->uri : '';
  $teaser['project_innovations'] = $group->hasField('field_project_innovations') ? $group->get('field_project_innovations')->uri : '';
  $teaser['project_website'] = $group->hasField('field_project_website') ? $group->get('field_project_website')->uri : '';
  $view_id = 'project_content';
  $teaser['project_coordinators_view'] = _eic_community_get_rendered_view($view_id, 'project_coordinators_teaser');
  $teaser['project_participants_view'] = _eic_community_get_rendered_view($view_id, 'project_coordinators_teaser');
  $teaser['project_coordinators_view_results'] = $teaser['project_coordinators_view'];
  $teaser['project_participants_view_results'] = $teaser['project_participants_view'];
//  dump($teaser['project_coordinators_view']);
  // TODO: Get dates from group object.
  if ($group->hasField('field_project_date')) {
    $teaser['project_date'] = [
      'start_date' => $variables['content']['field_project_date'][0]['start_date'],
      'end_date' => $variables['content']['field_project_date'][0]['end_date'],
    ];
  }

  $variables['project_item'] = $teaser;
}
