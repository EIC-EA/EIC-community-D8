<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for eic_group_header.
 */

use Drupal\Core\Url;
use Drupal\eic_community\ValueObject\ImageValueObject;

/**
 * Implements hook_preprocess_eic_group_header_block() for eic_group_header block.
 */
function eic_community_preprocess_eic_group_header_block(array &$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  // Adds group banner image.
  if (!$group->get('field_hero')->isEmpty()) {
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($group->get('field_hero')->entity, $group->language()->getId());
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $variables['group_values']['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  $actions = [];
  // Manipulate operations links, for example add icons to links.
  if (isset($variables['group_values']['operation_links']['edit'])) {
    $variables['group_values']['operation_links']['edit']['action'] = [
      'is_compact' => TRUE,
      'icon' => [
        'name' => 'gear',
        'type' => 'custom',
      ],
      'icon_file_path' => $variables['eic_icon_path'],
    ];
  }

  foreach ($variables['group_values']['operation_links'] as $link) {
    if (isset($link['links'])) {
      $items = [];

      foreach ($link['links'] as $item) {
        $tmp = [
          'link' => [
            'label' => $item['title'],
            'path' => $item['url'],
          ],
        ];

        if (isset($item['action'])) {
          $tmp = array_merge($tmp, $item['action']);
        }

        $items[] = $tmp;
      }

      $actions[] = [
        'label' => $link['label'],
        'items' => $items,
      ];
    }
    else {
      $tmp = [
        'link' => [
          'label' => $link['title'],
          'path' => $link['url'],
        ],
      ];

      if (isset($link['action'])) {
        $tmp = array_merge($tmp, $link['action']);
      }

      $actions[] = $tmp;
    }
  }

  $variables['group_values']['actions'] = $actions;

  $flags = [];

  foreach ($variables['group_values']['membership_links'] as $action) {
    if (isset($action['#lazy_builder'])) {
      $flag = ['content' => $action];
    }
    else {
      $icon_name = '';

      switch ($action['url']->getRouteName()) {
        case 'entity.group.request_delete_form':
          $icon_name = '';
          break;

        case 'entity.group.leave':
          $icon_name = 'leave';
          break;

        case 'entity.group_content.add_form':
          // Invite user.
          $icon_name = 'invite';
          break;

      }

      $flag = [
        'link' => [
          'label' => $action['title'],
          'path' => $action['url'],
        ],
      ];

      if (strlen($icon_name) > 0) {
        $flag['icon'] = [
          'name' => $icon_name,
          'type' => 'custom',
        ];
      }
    }

    $flags[] = $flag;
  }

  $variables['group_values']['flags'] = $flags;

  // Prepare group statistics values to send to the template.
  $stats = [];
  foreach ($variables['group_values']['stats'] as $stat_type => $statistic) {
    switch ($stat_type) {
      case 'members':
        $icon_name = 'user';
        $label = t('Users');
        break;

      case 'comments':
        $icon_name = 'comment';
        $label = t('Comments');
        break;

      case 'files':
        $icon_name = 'documents';
        $label = t('Documents');
        break;

      case 'events':
        $icon_name = 'calendar';
        $label = t('Calendar');
        break;

      default:
        $icon_name = '';
        $label = '';
        break;

    }

    $stats[] = [
      'icon' => [
        'name' => $icon_name,
        'type' => 'custom',
      ],
      'label' => $label,
      'value' => $statistic,
    ];
  }

  $variables['group_values']['stats'] = $stats;

  $group_visibility = \Drupal::service('oec_group_flex.helper')->getGroupVisibilitySettings($group);
  $tag = strpos($group_visibility['plugin_id'], '_') !== FALSE ? strstr($group_visibility['plugin_id'], '_', TRUE) : $group_visibility['plugin_id'];

  $variables['group_values']['tags'][] = [
    'extra_classes' => 'ecl-tag--is-' . $tag,
    'tag' => [
      'type' => 'button',
      'label' => $tag,
    ],
  ];

  // Adds url to navigate back to the list of groups.
  $back_url = Url::fromRoute('eic_groups.groups');
  $variables['group_values']['back_url'] = $back_url->toString();

}
