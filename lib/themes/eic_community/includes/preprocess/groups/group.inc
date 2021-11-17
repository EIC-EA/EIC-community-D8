<?php

/**
 * @file
 * Prepares variables for group templates.
 */

use Drupal\Core\Render\Markup;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_helper\DateTimeHelper;

/**
 * Implements hook_preprocess_group__TYPE.
 */
function eic_community_preprocess_group__event(&$variables) {
  $current_time = \Drupal::time()->getCurrentTime();
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_render_event_detail_page($variables, $group);
      break;

    case 'teaser':
    case 'small_teaser':
      $teaser = [
        'owner' => eic_community_get_teaser_user_display($group->getOwner()),
        'stats' => [],
        'icon_file_path' => $variables['eic_icon_path'],
      ];

      // Get the thumbnail.
      /** @var \Drupal\media\MediaInterface $media */
      $media = $group->get('field_image')->referencedEntities()[0];
      if ($media && !empty($media->get('oe_media_image')->getValue())) {
        $style_name = 'oe_theme_ratio_3_2_medium';
        $image_item = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), $style_name);
        $teaser['image'] = [
          'src' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
      }

      $signup_link = NULL;
      // Get the registration date.
      if (!$group->get('field_date_range')->isEmpty()) {
        $registration_start_date = strtotime($group->get('field_event_registration_date')->value);
        $registration_end_date = strtotime($group->get('field_event_registration_date')->end_value);
        $registration_status = \Drupal::service('eic_helper.datetime')->getDateRangeStatus($group, 'field_event_registration_date');

        switch ($registration_status) {
          case DateTimeHelper::DATE_RANGE_UPCOMING:
            $arguments = [
              '@date' => \Drupal::service('date.formatter')->format($registration_start_date, 'eu_short_date_hour'),
            ];
            $registration_message = t('Registrations will start on @date', $arguments);
            break;

          case DateTimeHelper::DATE_RANGE_ONGOING:
            $arguments = [
              '@time' => \Drupal::service('date.formatter')->formatInterval($current_time - $registration_end_date),
            ];
            $registration_message = t('@time left to signup', $arguments);
            if (!$group->get('field_link')->isEmpty()) {
              $signup_link = $group->get('field_link')->uri;
            }
            break;

          case DateTimeHelper::DATE_RANGE_PAST:
            $registration_message = t('Registrations are now closed');
            break;
        }

        if (!empty($registration_message)) {
          $teaser['timestamp']['label'] = $registration_message;
        }
      }
      elseif (!$group->get('field_link')->isEmpty()) {
        $signup_link = $group->get('field_link')->uri;
      }

      // Get the start date of the event.
      $start_date = 0;
      if (!$group->field_date_range->isEmpty()) {
        $start_date = strtotime($group->get('field_date_range')->value);
      }
      $teaser['date'] = _eic_community_preprocess_ecl_date_block($start_date);
      //$teaser['timestamp']['label'] = eic_community_get_teaser_time_display($group->get('created')->value);

      // Get the event state.
      $teaser['date']['variant'] = _eic_community_get_ecl_date_block_variant($group, 'field_date_range');
      $variables['event_item'] = $teaser;
      break;

  }
}

/**
 * Implements hook_preprocess_group().
 */
function eic_community_preprocess_group(array &$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  $item = [
    'owner' => eic_community_get_teaser_user_display($group->getOwner()),
    'stats' => [],
    'icon_file_path' => $variables['eic_icon_path'],
  ];

  // Move flags to group to pick them up in the group header.
  foreach ($variables['elements'] as $key => $element) {
    if (strpos($key, 'flag_') !== FALSE) {
      $variables['elements']['#group']->flags[$key] = $element;
    }
  }

  switch ($variables['view_mode']) {
    case 'full':
      if ($group->hasField('field_hero') && !$group->get('field_hero')->isEmpty()) {
        /** @var \Drupal\media\Entity\Media $media */
        $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_hero')->entity, $group->language()->getId());
        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $item['image'] = [
          'src' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
      }
      break;

    case 'teaser':
      // Get group thumbnail.
      if ($group->hasField('field_thumbnail') && !$group->get('field_thumbnail')->isEmpty()) {
        /** @var \Drupal\media\Entity\Media $media */
        $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_thumbnail')->entity, $group->language()->getId());
        $image_item = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), 'oe_theme_list_item_2x');
        $item['image'] = [
          'src' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
      }

      // Get group visibility.
      if (!empty($group_visibility_label = \Drupal::service('eic_groups.helper')->getGroupVisibilityLabel($group, 'short'))) {
        $item['type']['label'] = $group_visibility_label;
      }

      // Get group last activity.
      // @todo Get the real last activity.
      $item['timestamp']['label'] = eic_community_get_teaser_time_display($group->get('changed')->value);

      // Get group statistics.
      $group_statistics = \Drupal::service('eic_group_statistics.helper')->loadGroupStatistics($group);
      $item['stats'] = [
        'members' => [
          'label' => t('Members'),
          'value' => $group_statistics->getMembersCount(),
          'icon' => [
            'type' => 'custom',
            'name' => 'user',
          ],
        ],
        'comments' => [
          'label' => t('Comments'),
          'value' => $group_statistics->getCommentsCount(),
          'icon' => [
            'type' => 'custom',
            'name' => 'comment',
          ],
        ],
        'files' => [
          'label' => t('Files'),
          'value' => $group_statistics->getFilesCount(),
          'icon' => [
            'type' => 'custom',
            'name' => 'documents',
          ],
        ],
        'events' => [
          'label' => t('Events'),
          'value' => $group_statistics->getEventsCount(),
          'icon' => [
            'type' => 'custom',
            'name' => 'calendar',
          ],
        ],
      ];
      break;
  }

  $variables['group_item'] = $item;
  // About page preprocessing.
  if (isset($variables['elements']['owners'])) {
    _preprocess_about_page($variables);
  }
}

/**
 * The about page has two tabs with content.
 */
function _preprocess_about_page(&$variables) {
  $sections = [];

  _group_about_group_tab($variables, $sections);
  _group_about_permissions_tab($variables, $sections);

  $variables['sections'] = [
    'as_tabs' => TRUE,
    'items' => $sections,
  ];
}

/**
 * About group tab.
 */
function _group_about_group_tab($variables, &$sections) {
  $owners = array_map(function ($owner) {
    if ($owner instanceof Markup) {
      return [
        'title' => $owner,
      ];
    }
    else {
      return [
        'title' => $owner->getText(),
        'path' => $owner->getUrl(),
      ];
    }
  }, $variables['elements']['owners']);

  $about_items = [
    [
      'title' => t('Group owner'),
      'items' => $owners,
    ],
  ];

  if (count($variables['elements']['admins']) > 0) {
    $admins = array_map(function ($admin) {
      if ($admin instanceof Markup) {
        return [
          'title' => $admin,
        ];
      }
      else {
        return [
          'title' => $admin->getText(),
          'path' => $admin->getUrl(),
        ];
      }
    }, $variables['elements']['admins']);

    $about_items[] = [
      'title' => t('Administrator'),
      'items' => $admins,
    ];
  }

  if (isset($variables['elements']['field_vocab_topics']['#theme'])) {
    $render_array_topics = array_filter($variables['elements']['field_vocab_topics'], function ($key) {
      return is_int($key);
    }, ARRAY_FILTER_USE_KEY);

    $topics = array_map(function ($topic) {
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
    $render_array_geo = array_filter($variables['elements']['field_vocab_geo'], function ($key) {
      return is_int($key);
    }, ARRAY_FILTER_USE_KEY);

    $geo = array_map(function ($place) {
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
    ],
  ];
}

/**
 * About permissions tab.
 */
function _group_about_permissions_tab($variables, &$sections) {
  if (isset($variables['elements']['visibility'])) {
    $admin_items = [];
    $visibility = $variables['elements']['visibility'];
    $visibility_id = $visibility['plugin_id'];
    $access_items = [];

    switch ($visibility_id) {
      case 'custom_restricted':
        $restrictions = array_map(function ($restriction) {
          return [
            'title' => $restriction['label'],
            'content' => $restriction['options'],
          ];
        }, $visibility['settings']);

        $access_items = $restrictions;
        break;

    }

    $admin_items[] = [
      'title' => t('Group access'),
      'content' => [
        '#theme' => 'eic_community_well',
        '#content' => [
          '#theme' => 'eic_community_harmonica',
          '#title' => $visibility['title'],
          '#description' => $visibility['description'],
          '#icon' => [
            'icon' => [
              'name' => 'lock',
              'type' => 'custom',
            ],
          ],
          '#icon_file_path' => $variables['eic_icon_path'],
          '#items' => $access_items,
        ],
      ],
    ];

    if (count($variables['elements']['joining_methods']) > 0) {
      $request_title = $variables['elements']['joining_methods'][0]['title'];
      $request_content = $variables['elements']['joining_methods'][0]['description'];

      $admin_items[] = [
        'title' => t('Membership Request type'),
        'items' => [
          [
            'title' => $request_title,
            'content' => $request_content,
          ],
        ],
      ];
    }

    $sections[] = [
      'id' => 'admin',
      'title' => t('Permission and visibility'),
      'content' => [
        '#theme' => 'eic_community_extended_list',
        '#title' => t('Group Permissions'),
        '#description' => t("Below is explained to whom this group is visible in order to understand the private or public character of the group's content."),
        '#items' => $admin_items,
      ],
    ];
  }
}
