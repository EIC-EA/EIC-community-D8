<?php

/**
 * @file
 * Prepares variables for group templates.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_group().
 */
function eic_community_preprocess_group(array &$variables) {
  /** @var \Drupal\group\Entity\Group $group */
  $group = $variables['group'];

  $item = [
    'owner' => eic_community_get_teaser_user_display($group->getOwner()),
    'stats' => [],
    'icon_file_path' => $variables['eic_icon_path'],
  ];

  if ($group->hasField('field_hero') && !$group->get('field_hero')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_hero')->entity, $group->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $item['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  // Move flags to group to pick them up in the group header.
  foreach ($variables['elements'] as $key => $element) {
    if (strpos($key, 'flag_') !== false) {
      $variables['elements']['#group']->flags[$key] = $element;
    }
  }

  $variables['group_item'] = $item;
  // About page preprocessing
  if (isset($variables['elements']['owners'])) {
    _preprocess_about_page($variables);
  }
}

/**
 * The about page has two tabs with content
 */
function _preprocess_about_page(&$variables) {
  $sections = [];
  // about group tab
  $owners = array_map(function($owner) {
    return [
      'title' => $owner->getText(),
      'path' => $owner->getUrl(),
    ];
  }, $variables['elements']['owners']);

  $about_items = [
    [
      'title' => t('Group owner'),
      'items' => $owners,
    ],
  ];

  if (count($variables['elements']['admins']) > 0) {
    $admins = array_map(function($admin) {
      return [
        'title' => $admin->getText(),
        'path' => $admin->getUrl(),
      ];
    }, $variables['elements']['admins']);

    $about_items[] = [
      'title' => t('Administrator'),
      'items' => $admins,
    ];
  }

  if (isset($variables['elements']['field_vocab_topics']['#theme'])) {
    $render_array_topics = array_filter($variables['elements']['field_vocab_topics'], function($key) {
      return is_int($key);
    }, ARRAY_FILTER_USE_KEY);

    $topics = array_map(function($topic) {
      return [
        'title' => $topic['#title'],
      ];
    }, $render_array_topics);

    $about_items[] = [
      'title' => t('Group topics'),
      'items' => $topics,
    ];
  }

  if (isset($variables['elements']['field_vocab_geo']['#theme'])) {
    $render_array_geo = array_filter($variables['elements']['field_vocab_geo'], function($key) {
      return is_int($key);
    }, ARRAY_FILTER_USE_KEY);

    $geo = array_map(function($place) {
      return [
        'title' => $place['#title'],
      ];
    }, $render_array_geo);

    $about_items[] = [
      'title' => t('Group region and countries'),
      'items' => $geo,
    ];
  }

  $about_items[] = [
    'title' => t('Group description'),
    'content' => $variables['elements']['field_body'],
  ];

  $sections[] = [
    'id' => 'about',
    'title' => t('About this group'),
    'content' => [
      '#theme' => 'eic_community_extended_list',
      '#title' => t('Group details'),
      '#items' => $about_items,
    ]
  ];
  // permissions and visibility tab
  if (isset($variables['elements']['visibility'])) {
    $admin_items = [];

    if ($variables['elements']['visibility']['plugin_id'] === 'custom_restricted') {
      $restrictions = [];

      foreach ($variables['elements']['visibility']['settings'] as $restriction) {
        $restrictions[] = [
          'title' => $restriction['label'],
          'content' => $restriction['options']
        ];
      }

      $admin_items[] = [
        'title' => t('Group access'),
        'content' => [
          '#theme' => 'eic_community_well',
          '#content' => [
            '#theme' => 'eic_community_harmonica',
            '#title' => 'Custom restriction',
            '#icon' => [
              'icon' => [
                'name' => 'lock',
                'type' => 'custom',
              ],
            ],
            '#icon_file_path' => $variables['eic_icon_path'],
            '#items' => $restrictions,
          ],
        ],
      ];
    }


    $group_visibility_title = count($variables['elements']['joining_methods']) > 0 ? $variables['elements']['joining_methods'][0]['label']
      : t(ucfirst($variables['elements']['visibility']['plugin_id']));

    $admin_items[] = [
      'title' => t('Membership Request type'),
      'items' => [
        [
          'title' => $group_visibility_title,
          'content' => $variables['elements']['visibility']['label'],
        ],
      ],
    ];

    $sections[] = [
      'id' => 'admin',
      'title' => t('Permission and visibility'),
      'content' => [
        '#theme' => 'eic_community_extended_list',
        '#title' => t('Group Permissions'),
        '#items' => $admin_items,
      ]
    ];
  }
  // output
  $variables['sections'] = [
    'as_tabs' => true,
    'items' => $sections
  ];
}
