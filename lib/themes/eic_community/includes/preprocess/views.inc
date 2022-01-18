<?php

/**
 * @file
 * Contains Views preprocess functions.
 */

use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\views\Views;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\Core\Url;

/**
 * Implements template_preprocess_views_view_field().
 */
function eic_community_preprocess_views_view_field(&$variables) {
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];

  $key = $view->id() . '-' . $view->current_display . '-' . $variables['field']->field;

  switch ($key) {
    case 'taxonomy-subtopics-name':
      $variables['term_item'] = _eic_community_preprocess_ecl_tag($variables['row']->_entity)['tag'];
      break;

  }
}

/**
 * Implements template_preprocess_views_view().
 */
function eic_community_preprocess_views_view(&$variables) {
  $map_view_action = [
    'taxonomy_related_content-groups' => [
      'link' => [
        'label' => t('View all groups'),
        'path' => GlobalOverviewPages::getGlobalOverviewPageLink(
          GlobalOverviewPages::GROUPS
        )->getUrl(),
      ],
    ],
    'taxonomy_related_content-news_stories' => [
      'link' => [
        'label' => t('View all stories'),
        'path' => GlobalOverviewPages::getGlobalOverviewPageLink(
          GlobalOverviewPages::NEWS_STORIES
        )->getUrl(),
      ],
    ],
    'taxonomy_related_content-events' => [
      'link' => [
        'label' => t('View all events'),
        'path' => GlobalOverviewPages::getGlobalOverviewPageLink(
          GlobalOverviewPages::EVENTS
        )->getUrl(),
      ],
    ],
  ];
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];

  $key = $view->id() . '-' . $view->current_display;
  switch ($key) {
    case 'groups-block_user_profile_groups':
      $variables['extra_classes'] = 'ecl-teaser-overview--has-columns ecl-teaser-overview--has-compact-layout';
      break;

    case 'groups_homepage-block_groups_homepage':
      if ($call_to_action_url = GlobalOverviewPages::getGlobalOverviewPageLink(
        GlobalOverviewPages::GROUPS
      )->getUrl()) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all groups'),
            'path' => $call_to_action_url,
          ],
        ];
      }
      break;

    case 'groups_homepage-block_events_homepage':
      if ($call_to_action_url = GlobalOverviewPages::getGlobalOverviewPageLink(
        GlobalOverviewPages::EVENTS
      )->getUrl()) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all events'),
            'path' => $call_to_action_url,
          ],
        ];
      }
      break;

    case 'taxonomy-subtopics':
      $items = [];
      $rows = $variables['rows'][0]['#rows'];
      foreach ($rows as $row) {
        /** @var \Drupal\taxonomy\TermInterface $term */
        $term = $row['#row']->_entity;

        $items[] = [
          'label' => $term->getName(),
          'title' => $term->toUrl(),
        ];
      }
      $variables['items'] = $items;
      $variables['title'] = $variables['view']->getTitle();
      break;

    case 'taxonomy-block_taxonomy_overview':
      $variables['title'] = $variables['view']->getTitle();
      break;

    case 'taxonomy_related_content-groups':
      $variables['extra_classes'] = 'ecl-section-wrapper--is-white ecl-featured-content-collection--has-overview-layout';
      $variables['items_extra_classes'] = 'ecl-featured-content-collection__item--big';
      $variables['no_container'] = TRUE;
      break;

    case 'organisation_content-organisation_news':
      $variables['title'] = t('Latest news', [], ['context' => 'eic_community']);
      $variables['extra_classes'] = 'ecl-section-wrapper--is-white ecl-featured-content-collection--has-list-layout';
      $variables['extra_id'] = 'news';

      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
      if ($group instanceof \Drupal\group\Entity\GroupInterface) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all news'),
            'path' => Url::fromRoute(GroupOverviewPages::NEWS, ['group' => $group->id()]),
          ],
        ];
      }
      break;

    case 'organisation_content-organisation_events':
      $variables['title'] = t('Events attending', [], ['context' => 'eic_community']);
      $variables['extra_id'] = 'events';
      $variables['extra_classes'] = 'ecl-section-wrapper--is-white ecl-featured-content-collection--has-list-layout';

      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
      if ($group instanceof \Drupal\group\Entity\GroupInterface && !empty($variables['rows'])) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all events'),
            'path' => Url::fromRoute(GroupOverviewPages::EVENTS, ['group' => $group->id()]),
          ],
        ];
      }
      break;

    case 'taxonomy_related_content-files':
    case 'taxonomy_related_content-news_stories':
      $variables['extra_classes'] = 'ecl-section-wrapper--is-white ecl-featured-content-collection--has-list-layout';
      $variables['no_container'] = TRUE;
      break;

    case 'taxonomy_related_content-discussions':
    case 'taxonomy_related_content-events':
      $variables['extra_classes'] = 'ecl-section-wrapper--is-white ecl-featured-content-collection--has-list-layout';
      $variables['items_extra_classes'] = 'ecl-featured-content-collection__item--big';
      $variables['no_container'] = TRUE;
      break;

    case 'taxonomy_related_content-wiki_pages':
      $variables['extra_classes'] = 'ecl-section-wrapper ecl-section-wrapper--is-white ecl-featured-content-collection--has-list-layout';
      $variables['no_container'] = TRUE;
      break;

  }

  // Add the read more link if there is one.
  if ($view->display_handler->getOption('use_more') == TRUE) {
    $variables['call_to_action'] = [
      'link' => [
        'label' => $view->display_handler->getOption('use_more_text'),
        'path' => $view->display_handler->getOption('link_url'),
      ],
    ];
  }

  $call_to_action = array_key_exists(
    $key,
    $map_view_action
  ) ? $map_view_action[$key] : [];

  if (!empty($call_to_action) && !empty($call_to_action['link']['path'])) {
    $variables['call_to_action'] = $call_to_action;
  }
}

/**
 * Executes a view and gets output as renderable array.
 *
 * @param string $view_id
 *   The view machine name.
 * @param string $display_id
 *   The view display ID.
 * @param array $args
 *   (optional) Array of arguments to send to the view.
 *
 * @return array|null
 *   A renderable array containing the view output or NULL if the display ID
 *   of the view to be executed doesn't exist.
 */
function _eic_community_get_rendered_view($view_id, $display_id, array $args = []) {
  $view = Views::getView($view_id);

  if (!$view) {
    return NULL;
  }

  return $view->preview($display_id, $args);
}
