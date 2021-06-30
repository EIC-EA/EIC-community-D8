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

  foreach ($variables['group_values']['operation_links'] as $link) {
    if(isset($link['items'])) {
      $items = [];

      foreach ($link['items'] as $item) {
        $items[]['link'] = [
          'label' => $item['title'],
          'path' => $item['url'],
        ];
      }

      $actions[] = [
        'label' => $link['label'],
        'items' => $items
      ];
    }else{
      $actions[]['link'] = [
        'label' => $link['title'],
        'path' => $link['url'],
      ];
    }
  }

  $variables['group_values']['actions'] = $actions;

  $flags = [];

  foreach ($variables['group_values']['membership_links'] as $action) {
    if(isset($action['#lazy_builder'])) {
      $flag = ['content' => $action];
    }else {
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
  // @todo Make statistics dynamic.
  $variables['group_values']['stats'] = [
    [
      'icon' => [
        'name' => 'user',
        'type' => 'custom',
      ],
      'label' => 'user',
      'value' => 0,
    ],
    [
      'icon' => [
        'name' => 'comment',
        'type' => 'custom',
      ],
      'label' => 'comment',
      'value' => 0,
    ],
    [
      'icon' => [
        'name' => 'documents',
        'type' => 'custom',
      ],
      'label' => 'documents',
      'value' => 0,
    ],
    [
      'icon' => [
        'name' => 'calendar',
        'type' => 'custom',
      ],
      'label' => 'calendar',
      'value' => 0,
    ],
  ];

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
  $back_url = Url::fromRoute('view.global_overviews.page_1');
  $variables['group_values']['back_url'] = $back_url->toString();

}
