<?php

/**
 * @file
 * Contains Views preprocess functions.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\group\Entity\GroupInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

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
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];

  $key = $view->id() . '-' . $view->current_display;
  switch ($key) {
    case 'group_related_news_stories-block_related_news_stories':
      $tags = [];
      foreach ($view->result as $row) {
        $entity = $row->_entity;
        if (!$entity instanceof NodeInterface) {
          continue;
        }

        $tags[] = 'eic_statistics:node:' . $entity->id();
      }

      if ($tags) {
        $variables['#cache']['tags'] = $tags;
      }

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
          'path' => $term->toUrl()->toString(),
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
      if ($group instanceof GroupInterface && !empty($variables['rows'])) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all news'),
            'path' => Url::fromRoute(GroupOverviewPages::NEWS, ['group' => $group->id()]),
          ],
        ];
      }

      $url_add_news = Url::fromRoute(
        'entity.group_content.create_form',
        ['group' => $group->id(), 'plugin_id' => 'group_node:news']
      );

      if ($group instanceof GroupInterface && $url_add_news->access() && empty($variables['rows'])) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('Add news'),
            'path' => $url_add_news->toString(),
          ],
          'extra_classes' => 'ecl-link ecl-link--cta',
          'type' => 'cta',
        ];
      }
      break;

    case 'organisation_content-organisation_events':
      $variables['title'] = t('Events attending', [], ['context' => 'eic_community']);
      $variables['extra_id'] = 'events';
      $variables['extra_classes'] = 'ecl-section-wrapper--is-white ecl-featured-content-collection--has-list-layout';

      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
      if ($group instanceof GroupInterface && !empty($variables['rows'])) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('View all events'),
            'path' => Url::fromRoute(GroupOverviewPages::EVENTS, ['group' => $group->id()]),
          ],
        ];
      }

      $url_add_event = Url::fromRoute(
        'entity.group_content.create_form',
        ['group' => $group->id(), 'plugin_id' => 'group_node:event']
      );

      if ($group instanceof GroupInterface && $url_add_event->access() && empty($variables['rows'])) {
        $variables['call_to_action'] = [
          'link' => [
            'label' => t('Add event'),
            'path' => $url_add_event->toString(),
          ],
          'extra_classes' => 'ecl-link ecl-link--cta',
          'type' => 'cta',
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

    case 'global_index_group_content-block_user_profile_events':
    case 'global_index_group_content-block_user_profile_my_events':
    case 'groups-block_user_profile_organisations':
    case 'groups-block_user_profile_my_organisations':
      $variables['extra_classes'] = 'ecl-teaser-overview--has-compact-layout';
      break;

    case 'latest_news_and_stories-homepage':
      _eic_community_preprocess_views__latest_news_and_stories__homepage($variables);
      break;

  }

  // Add the read more link if there is one.
  if (
    $view->display_handler->getOption('use_more') == TRUE &&
    !empty($variables['more'])
  ) {
    // $link_display = $view->display_handler->
    $variables['call_to_action'] = [
      'link' => [
        'label' => $variables['more']['#title'],
        'path' => $variables['more']['#url']->toString(),
      ],
    ];
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

/**
 * Implements hook_preprocess_views() for latest_news_and_stories.
 */
function _eic_community_preprocess_views__latest_news_and_stories__homepage(&$variables) {
  $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $nodes = [];

  foreach ($variables['rows'][0]['#rows'] as $item) {
    $nodes[] = $item['#row']->_entity;
  }

  // Process the array of nodes and add it to the theme variables.
  foreach ($nodes as $node) {
    if (!$node instanceof NodeInterface) {
      continue;
    }

    // We need to check the node access otherwise private nodes will always be
    // visible.
    if (!$node->access('view')) {
      continue;
    }

    $node_translation = $node;
    if ($node->hasTranslation($current_langcode)) {
      $node_translation = $node->getTranslation($current_langcode);
    }
    $node_author = eic_community_get_teaser_user_display($node_translation->getOwner());
    $time_ago = \Drupal::service('date.formatter')->formatTimeDiffSince($node_translation->get('published_at')->value, ['granularity' => 1]);

    if (isset($variables['content']['node_stats'][$node->id()])) {
      $node_stats = $variables['content']['node_stats'][$node->id()];
    }

    // Add node statistics.
    $stats = [
      [
        'icon' => [
          'type' => 'custom',
          'name' => 'comment',
        ],
        'label' => t('Comments'),
        'value' => isset($node_stats) ? $node_stats['comments_count']['#value'] : 0,
      ],
      [
        'icon' => [
          'type' => 'custom',
          'name' => 'views',
        ],
        'label' => t('Views'),
        'value' => isset($node_stats) ? $node_stats['page_views']['#value'] : 0,
      ],
      [
        'icon' => [
          'type' => 'custom',
          'name' => 'like',
        ],
        'label' => t('Likes'),
        'value' => isset($node_stats['flag_counts']['#items']['like_content']) ? $node_stats['flag_counts']['#items']['like_content'] : 0,
      ],
    ];

    $item = [
      'title' => $node_translation->label(),
      'description' => Xss::filter($node_translation->get('field_introduction')->value),
      'path' => $node_translation->toUrl()->toString(),
      'type' => [
        'label' => $node_translation->type->entity->label(),
        'icon' => [
          'name' => $node_translation->bundle(),
          'type' => 'custom',
        ],
      ],
      'timestamp' => [
        'label' => t('@time ago', ['@time' => $time_ago]),
      ],
      'author' => $node_author,
      'stats' => $stats,
    ];

    if (!$node_translation->get('field_image')->isEmpty()) {
      /** @var \Drupal\media\Entity\Media $media */
      $media = \Drupal::service('entity.repository')->getTranslationFromContext($node_translation->get('field_image')->entity, $current_langcode);
      $file_entity = $media->get('oe_media_image')->first()->entity;
      $file_uri = $file_entity ? $file_entity->getFileUri() : NULL;
      $item['media'] = [
        'image' => $file_uri ? ImageStyle::load('oe_theme_ratio_3_2_medium')->buildUrl($file_uri) : NULL,
      ];
    }

    $variables['news_stories_items'][] = $item;

    // Add link to the overview.
    $variables['news_stories_overview_url'] = GlobalOverviewPages::getGlobalOverviewPageLink(GlobalOverviewPages::NEWS_STORIES)->getUrl();
  }
}
