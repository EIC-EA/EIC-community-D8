<?php

/**
 * @file
 * Prepares variables for group templates.
 */

use Drupal\Core\Render\Markup;
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
    if (strpos($key, 'flag_') !== FALSE) {
      $variables['elements']['#group']->flags[$key] = $element;
    }
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
  // About group tab.
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
  // Permissions and visibility tab.
  if (isset($variables['elements']['visibility'])) {
    $admin_items = [];
    $visibility = $variables['elements']['visibility'];
    $visibility_id = $visibility['plugin_id'];

    $access_title = '';
    $access_description = '';
    $access_items = [];

    switch ($visibility_id) {
      case 'custom_restricted':
        $restrictions = [];

        foreach ($visibility['settings'] as $restriction) {
          $restrictions[] = [
            'title' => $restriction['label'],
            'content' => $restriction['options'],
          ];
        }

        $access_title = t('Restricted group');
        $access_description = t('This group is visible to every person that has joined the EIC community that also complies with the following restrictions. You can see this group because the organisation you work for is allowed to see this content or the group owners and administrators have chosen to specifically grant you access to this group. If you want to participate, please become a group member.');
        $access_items = $restrictions;
        break;

      case 'public':
        $access_title = t('Public group');
        $access_description = t("This group is visible to everyone visiting the group. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");
        break;

      case 'private':
        $access_title = t('Private group');
        $access_description = t('A private group is only visible to people who received an invitation via email and accepted it. No one else can see this group.');
        break;

      case 'restricted_community_members':
        $access_title = t('Community members only');
        $access_description = t("This group is visible to every person that is a member of the EIC Community and has joined this platform. You're welcome to scroll through the group's content. If you want to participate, please become a group member.");
        break;

    }

    $admin_items[] = [
      'title' => t('Group access'),
      'content' => [
        '#theme' => 'eic_community_well',
        '#content' => [
          '#theme' => 'eic_community_harmonica',
          '#title' => $access_title,
          '#description' => $access_description,
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
      $is_open = $variables['elements']['joining_methods'][0]['plugin_id'] === 'open';
      $request_title = $is_open ? t('Open') : t('Moderated');
      $request_content = $is_open ? t('This means that EIC Community members can join this group immediately by clicking "join group".')
        : t('This means that EIC Community members can request to join this group. This request needs to be validated by the group owner or administrator.');

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
  // Output.
  $variables['sections'] = [
    'as_tabs' => TRUE,
    'items' => $sections,
  ];
}
