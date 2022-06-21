<?php

/**
 * @file
 * Prepares variables for group templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Markup;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_media\MediaHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\taxonomy\TermInterface;

/**
 * Implements hook_preprocess_group__group().
 */
function eic_community_preprocess_group__group(array &$variables) {
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
        $media = \Drupal::service('entity.repository')->getTranslationFromContext(
          $group->get('field_hero')->entity,
          $group->language()->getId()
        );
        $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
        $item['image'] = [
          'src' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
      }
      break;

    case 'mail_teaser':
    case 'teaser':
      // Get group description.
      $item['description'] = $group->hasField('field_body') ? Markup::create(Xss::filter($group->get('field_body')->value)) : '';
      // Get group thumbnail.
      if ($group->hasField('field_thumbnail') && !$group->get('field_thumbnail')->isEmpty()) {
        /** @var \Drupal\media\Entity\Media $media */
        $media = \Drupal::service('entity.repository')->getTranslationFromContext(
          $group->get('field_thumbnail')->entity,
          $group->language()->getId()
        );
        $image_item = ImageValueObject::fromStyledImageItem(
          $media->get('oe_media_image')->first(),
          'oe_theme_list_item_2x'
        );
        $item['image'] = [
          'src' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
      }

      // Get group visibility.
      if ($group_visibility_label = \Drupal::service('oec_group_flex.helper')->getGroupVisibilityTagLabel($group)) {
        $item['type']['label'] = $group_visibility_label;
        $item['type']['extra_classes'] = 'ecl-tag--is-' . strtolower($group_visibility_label);
      }

      // Get group tags.
      $item['tags'] = _preprocess_group_tags($group);

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
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];
  $owners = $group->getMembers($group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_OWNER_ROLE);
  $admins = $group->getMembers($group->bundle() . '-' . EICGroupsHelper::GROUP_TYPE_ADMINISTRATOR_ROLE);

  $owners = array_map(function (GroupMembership $owner) {
    $user = $owner->getUser();

    return [
      'title' => realname_load($user),
      'path' => $user->toUrl(),
    ];
  }, $owners);

  $about_items = [
    [
      'title' => t('Group owner'),
      'items' => $owners,
    ],
  ];

  if (!empty($admins)) {
    $admins = array_map(function (GroupMembership $admin) {
      $user = $admin->getUser();

      return [
        'title' => realname_load($user),
        'path' => $user->toUrl(),
      ];
    }, $admins);

    $about_items[] = [
      'title' => t('Administrator'),
      'items' => $admins,
    ];
  }

  $topics = $group->hasField('field_vocab_topics') ?
    $group->get('field_vocab_topics')->referencedEntities() :
    [];
  $geos = $group->hasField('field_vocab_geo') ?
    $group->get('field_vocab_geo')->referencedEntities() :
    [];

  if (!empty($topics)) {
    $topics = array_map(function (TermInterface $topic) {
      return [
        'title' => $topic->label(),
      ];
    }, $topics);

    $about_items[] = [
      'title' => t('Group topics'),
      'items' => $topics,
    ];
  }

  if (!empty($geos)) {
    $geos = array_map(function (TermInterface $topic) {
      return [
        'title' => $topic->label(),
      ];
    }, $geos);

    $about_items[] = [
      'title' => t('Group region and countries'),
      'items' => $geos,
    ];
  }

  $about_items[] = [
    'title' => t('Group description'),
    'content' => $group->hasField('field_body') ?
      $group->get('field_body')->value :
      '',
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
        $restrictions = array_map(
          '_eic_community_get_group_custom_restricted_options_item_list',
          array_keys($visibility['settings']),
          $visibility['settings']
        );

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
        '#description' => t(
          "Below is explained to whom this group is visible in order to understand the private or public character of the group's content."
        ),
        '#items' => $admin_items,
      ],
    ];
  }
}

/**
 * Returns item list array for a given custom restricted visibility option.
 *
 * @param string $restriction_type
 *   The restriction type.
 * @param array $restriction
 *   The restriction options.
 *
 * @return array
 *   The array of restricted options to print in twig template.
 */
function _eic_community_get_group_custom_restricted_options_item_list(string $restriction_type, array $restriction) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $items = [];
  switch ($restriction_type) {
    case 'restricted_email_domains':
      $items = explode(',', $restriction['options']);
      break;

    case 'restricted_users':
      foreach ($restriction['options'] as $option) {
        if ($user = $entity_type_manager->getStorage('user')->load($option['target_id'])) {
          $items[] = $user->getDisplayName();
        }
      }
      break;

    case 'restricted_organisation_types':
      foreach ($restriction['options'] as $option) {
        if ($term = $entity_type_manager->getStorage('taxonomy_term')->load($option['target_id'])) {
          $items[] = $term->label();
        }
      }
      break;

    case 'restricted_organisations':
      foreach ($restriction['options'] as $option) {
        if ($group = $entity_type_manager->getStorage('group')->load($option['target_id'])) {
          $items[] = $group->label();
        }
      }
      break;

  }

  return [
    'title' => $restriction['label'],
    'content' => [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $items,
    ],
  ];
}

function _eic_groups_get_formatted_attachments(ContentEntityInterface $entity, string $field_name, $variables) {
  if (!$entity->hasField($field_name)) {
    return [];
  }

  $medias = $entity->get($field_name)->referencedEntities();
  $list = [];
  foreach ($medias as $media) {
    $file = File::load($media->field_media_file->target_id);

    if (!$file instanceof \Drupal\file\FileInterface) {
      continue;
    }

    $file_type = strstr($file->get('filemime')->getString(), '/', TRUE);
    $download_url = MediaHelper::formatMediaDownloadLink($media)->toString();
    $list[] = [
      'title' => $media->getName(),
      'language' => $media->language()->getName(),
      'timestamp' => eic_community_get_teaser_time_display($media->get('changed')->getString()),
      'filesize' => format_size($file->get('filesize')->getString()),
      'highlight' => FALSE,
      'path' => $download_url,
      'icon_file_path' => $variables['eic_icon_path'],
      'icon' => [
        'type' => in_array($file_type, ['video', 'image']) ? 'general' : 'custom',
        'name' => in_array($file_type, ['video', 'image']) ? $file_type : 'document_' . _eic_community_get_file_type_icon_name(
            pathinfo($file->getFilename(), PATHINFO_EXTENSION)
          ),
      ],
    ];
  }

  return $list;
}

/**
 * Preprocesses group topic tags.
 *
 * @param \Drupal\group\Entity\GroupInterface $group
 *   The group entity.
 *
 * @return array
 *   Array of ecl tags.
 */
function _preprocess_group_tags(GroupInterface $group) {
  // Get the topics.
  $tags = [];
  if ($group->hasField('field_vocab_topics') && !$group->get('field_vocab_topics')->isEmpty()) {
    foreach ($group->get('field_vocab_topics')->referencedEntities() as $term) {
      $tags[] = _eic_community_preprocess_ecl_tag($term)['tag'];
    }
  }
  return $tags;
}
