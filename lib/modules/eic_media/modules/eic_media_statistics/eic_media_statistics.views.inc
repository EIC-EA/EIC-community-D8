<?php

/**
 * @file
 * Provide views data for statistics.module.
 */

/**
 * Implements hook_views_data().
 */
function eic_media_statistics_views_data() {
  $data['file_counter']['table']['group'] = t('Content statistics');

  $data['file_counter']['table']['join'] = [
    'file_managed' => [
      'left_field' => 'fid',
      'field' => 'fid',
    ],
  ];

  $data['file_counter']['totalcount'] = [
    'title' => t('Total downloads'),
    'help' => t('The total number of times the file has been downloaded.'),
    'field' => [
      'id' => 'statistics_numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['file_counter']['daycount'] = [
    'title' => t('Downloads today'),
    'help' => t('The total number of times the file has been downloaded today.'),
    'field' => [
      'id' => 'statistics_numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['file_counter']['timestamp'] = [
    'title' => t('Most recent download'),
    'help' => t('The most recent time the file has been downloaded.'),
    'field' => [
      'id' => 'node_counter_timestamp',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}
