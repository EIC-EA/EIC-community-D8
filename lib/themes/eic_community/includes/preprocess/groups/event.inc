<?php

/**
 * @file
 * Prepares variables for group type event templates.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_helper\DateTimeHelper;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_search\SearchHelper;

/**
 * Implements hook_preprocess_group__TYPE().
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
      if (!empty($medias = $group->get('field_image')->referencedEntities())) {
        $media = $medias[0];
        if ($media && !empty($media->get('oe_media_image')->getValue())) {
          $style_name = 'oe_theme_ratio_3_2_medium';
          $image_item = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), $style_name);
          $teaser['image'] = [
            'src' => $image_item->getSource(),
            'alt' => $image_item->getAlt(),
          ];
        }
      }

      // Add event location types to the theme variables.
      if (!$group->get('field_location_type')->isEmpty()) {
        $teaser['type'] = [
          'label' => NULL,
          'icon' => [
            'type' => 'custom',
            'name' => 'mappin',
            'size' => 's',
          ],
          'extra_classes' => 'ecl-icon--currentColor',
        ];
        $event_location_types = $group->get('field_location_type')->getValue();
        $location_type_allowed_values = eic_field_location_type_allowed_values();
        foreach ($event_location_types as $location_type) {
          $value = $location_type['value'];
          if (!$teaser['type']['label']) {
            $teaser['type']['label'] = $location_type_allowed_values[$value];
            continue;
          }
          $value = strtolower($location_type_allowed_values[$value]);
          $teaser['type']['label'] .= " & {$value}";
        }
      }

      // Add event topics to the theme variables.
      if (!$group->get('field_vocab_topics')->isEmpty()) {
        $event_topics = $group->get('field_vocab_topics')->referencedEntities();

        $teaser['tags'] = [];
        foreach ($event_topics as $event_topic) {
          // Build solr query parameters for the events overview page to filter
          // by topic.
          $params = SearchHelper::buildSolrQueryParams(
            [
              'itm_aggregated_field_vocab_topics' => $event_topic->id(),
            ]
          );
          $tag = [
            'label' => $event_topic->getName(),
          ];

          $url = GlobalOverviewPages::getGlobalOverviewPageLink(GlobalOverviewPages::EVENTS)->getUrl();

          if ($url) {
            $url->setOption('query', $params)
              ->toString();
            $tag['path'] = $url;
          }

          $teaser['tags'][] = $tag;
        }
      }

      // Adds number of group members to the theme variables.
      $teaser['members']['value'] = count($group->getMembers());

      $has_registration_link = FALSE;
      $event_start_date = 0;
      $event_end_date = 0;
      // Get the registration date.
      if (!$group->get('field_date_range')->isEmpty()) {
        $event_start_date = strtotime($group->get('field_date_range')->value);
        $event_end_date = strtotime($group->get('field_date_range')->end_value);
        $event_date_status = \Drupal::service('eic_helper.datetime')->getDateRangeStatus($group, 'field_date_range');
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
            if ($registration_end_date <= $current_time) {
              break;
            }

            $arguments = [
              '@time' => \Drupal::service('date.formatter')->formatInterval($registration_end_date - $current_time),
            ];
            $registration_message = t('@time left to register', $arguments);
            $has_registration_link = TRUE;
            break;

          case DateTimeHelper::DATE_RANGE_PAST:
          case DateTimeHelper::DATE_RANGE_UNDEFINED:
            $registration_message = t('Event registration closed');
            break;

        }

        if (!empty($registration_message)) {
          // Change closed registration message if event is from the past.
          if (in_array(
            $event_date_status,
            [
              DateTimeHelper::DATE_RANGE_PAST,
              DateTimeHelper::DATE_RANGE_UNDEFINED,
            ]
          )) {
            $registration_message = t('Event registration closed');
          }

          $teaser['timestamp']['label'] = $registration_message;
        }
      }

      // Adds sign up link to the teaser variables.
      if ($has_registration_link) {
        $teaser['actions'] = [
          [
            'label' => t('Sign up'),
            'path' => $group->toUrl()->toString(),
          ],
        ];
      }

      $teaser['date']['start'] = _eic_community_preprocess_ecl_date_block($event_start_date, NULL, DateTimeHelper::DATE_FORMAT_SHORT);
      $teaser['date']['end'] = _eic_community_preprocess_ecl_date_block($event_end_date, NULL, DateTimeHelper::DATE_FORMAT_SHORT);

      // Get the event state.
      $teaser['date']['variant'] = _eic_community_get_ecl_date_block_variant($group, 'field_date_range');
      $variables['event_item'] = $teaser;
      break;

  }

  // About page preprocessing.
  if (isset($variables['elements']['owners'])) {
    _preprocess_event_about_page($variables);
  }
}

/**
 * The about page has two tabs with content.
 */
function _preprocess_event_about_page(&$variables) {
  $sections = [];

  _event_about_permissions_tab($variables, $sections);

  $variables['sections'] = [
    'as_tabs' => TRUE,
    'items' => $sections,
  ];
}

/**
 * About permissions tab.
 */
function _event_about_permissions_tab($variables, &$sections) {
  if (isset($variables['elements']['visibility'])) {
    $admin_items = [];
    $visibility = $variables['elements']['visibility'];
    $visibility_id = $visibility['plugin_id'];
    $access_items = [];

    switch ($visibility_id) {
      case 'custom_restricted':
        $restrictions = array_map(
          '_eic_community_get_group_custom_restricted_options_item_list',
          array_keys($visibility['settings']),
          $visibility['settings']
        );

        $access_items = $restrictions;
        break;

    }

    $admin_items[] = [
      'title' => t('Event access'),
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
        '#title' => t('Event Permissions'),
        '#description' => t("Below is explained to whom this event is visible in order to understand the private or public character of the event's content."),
        '#items' => $admin_items,
      ],
    ];
  }
}
