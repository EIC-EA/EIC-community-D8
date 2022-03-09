<?php

/**
 * @file
 * Contains preprocess implementation for eic_topics_statistics_block.
 */

/**
 * Implements hook_preprocess_block().
 */
function eic_community_preprocess_block__eic_topics_statistics_block(&$variables) {
  $items = [];

  foreach ($variables['content']['#stats'] as $stat_name => $stat_data) {
    $icon_name = '';

    switch ($stat_name) {
      case 'stories':
        $icon_name = 'story';
        break;

      case 'wiki_page':
        $icon_name = 'wiki';
        break;

      case 'discussion':
        $icon_name = 'discussion';
        break;

      case 'news':
        $icon_name = 'news';
        break;

      case 'group':
        $icon_name = 'group';
        break;

      case 'file':
        $icon_name = 'document';
        break;

      case 'event':
        $icon_name = 'calendar';
        break;

      case 'expert':
        $icon_name = 'user';
        break;

      case 'organisation':
        $icon_name = 'company';
        break;

    }

    $item = [
      'title' => $stat_name,
      'data' => $stat_data['stat'],
      'url' => $stat_data['url'],
      'icon' => [
        'type' => 'custom',
        'name' => $icon_name,
      ],
    ];
    $items[] = $item;
  }

  $variables['content']['#stats'] = $items;
  $variables['content']['#icon_file_path'] = $variables['eic_icon_path'];
}
