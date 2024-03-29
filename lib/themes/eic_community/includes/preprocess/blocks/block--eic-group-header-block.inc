<?php

/**
 * @file
 * Contains implementation for hook_preprocess_block() for eic_group_header.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_search\SearchHelper;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_eic_group_header_block().
 */
function eic_community_preprocess_eic_group_header_block(array &$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];
  $hero_field_id = 'group' === $group->getGroupType()->id() ? 'field_hero' : 'field_header_visual';

  // Adds group banner image.
  if ($group->hasField($hero_field_id) && !$group->get($hero_field_id)->isEmpty()) {
    $media = \Drupal::service('entity.repository')->getTranslationFromContext(
      $group->get($hero_field_id)->entity,
      $group->language()->getId()
    );

    if (!$media->get('oe_media_image')->isEmpty()) {
      $image_item = ImageValueObject::fromStyledImageItem(
        $media->get('oe_media_image')->first(),
        'oe_theme_ratio_3_2_medium'
      );
      $variables['group_values']['image'] = [
        'src' => $image_item->getSource(),
        'alt' => $image_item->getAlt(),
      ];
    }
  }

  // Adds membership links to the group operation links dropdown.
  foreach ($variables['group_values']['membership_links'] as $key => $action) {
    if (isset($action['#lazy_builder'])) {
      continue;
    }

    switch ($key) {
      case 'group-request-archival':
      case 'group-request-delete':
      case 'group-leave':
        $variables['group_values']['group_operation_links'][0]['links'][$key] = $action;
        unset($variables['group_values']['membership_links'][$key]);
        break;

      case 'group-request-block':
        unset($variables['group_values']['group_operation_links'][0]['links'][$key]);
        unset($variables['group_values']['membership_links'][$key]);
        break;

    }
  }

  $actions = [];

  // Adds group operation links to operations links array.
  if (!empty($variables['group_values']['group_operation_links'])) {
    // Adds dropdown label if not presented.
    if (empty($variables['group_values']['group_operation_links'][0]['label'])) {
      $variables['group_values']['group_operation_links'][0]['label'] = t(
        'Manage'
      );
    }
    // Adds dropdown icon.
    $variables['group_values']['group_operation_links'][0]['icon'] = [
      'name' => 'gear',
      'type' => 'custom',
    ];
    // Adds dropdown extra classes.
    $variables['group_values']['group_operation_links'][0]['extra_classes'] = 'ecl-collapsible-options--blue';
    // Adds dropdown icon path.
    $variables['group_values']['group_operation_links'][0]['icon_file_path'] = $variables['eic_icon_path'];
    // Merge operation links.
    $variables['group_values']['operation_links'] = array_merge(
      $variables['group_values']['operation_links'],
      $variables['group_values']['group_operation_links']
    );
  }

  // Manipulate operations links, for example add icons to links.
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
        'icon' => !empty($link['icon']) ? $link['icon'] : NULL,
        'icon_file_path' => !empty($link['icon_file_path']) ? $link['icon_file_path'] : NULL,
        'extra_classes' => !empty($link['extra_classes']) ? $link['extra_classes'] : NULL,
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

  // Adds recommend link.
  if ($recommend_link = \Drupal::service('eic_recommend_content.manager')->getRecommendContentLink($group)) {
    $variables['group_values']['membership_links']['recommend_content'] = $recommend_link;
  }

  foreach ($variables['group_values']['membership_links'] as $name => $action) {
    if (
      isset($action['#lazy_builder']) ||
      $name === 'recommend_content'
    ) {
      $flag = ['content' => $action];
    }
    else {
      $icon_name = '';

      switch ($action['url']->getRouteName()) {
        case 'entity.group.leave':
          $icon_name = 'leave';
          break;

        case 'entity.group_content.add_form':
        case 'ginvite.invitation.bulk':
          // Invite user.
          $icon_name = 'invite';
          break;

        case 'entity.group.new_request':
          $icon_name = 'gear';
          break;
      }

      $flag = [
        'link' => [
          'label' => $action['title'],
          'path' => $action['url'],
        ],
      ];

      switch ($name) {
        case 'group-request-archival':
          $flag['icon'] = [
            'type' => 'general',
            'name' => '_package',
          ];
          break;

        case 'group-request-delete':
          $icon_name = 'bin';
          break;

      }

      if (strlen($icon_name) > 0) {
        $flag['icon'] = [
          'name' => $icon_name,
          'type' => 'custom',
        ];
      }
    }

    // We set the recommend flag in the 2nd position.
    if ($name === 'recommend_content') {
      if (count($flags) > 2) {
        $flags = array_merge(array_slice($flags, 0, 1), [$flag], array_slice($flags, 1));
      }
      else {
        $flags[1] = $flag;
      }

      continue;
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

  $tags = [];
  switch ($group->getGroupType()->id()) {
    case 'group':
    case 'event':
      $visibility = \Drupal::service('oec_group_flex.helper')->getGroupVisibilityTagLabel($group);
      $tags[] = [
        'type' => 'display',
        'label' => $visibility,
        'id' => strtolower($visibility),
      ];
      break;

    case 'organisation':
      $types = $group->get('field_organisation_type')->referencedEntities();
      $tags = array_map(function (Term $type) {
        $filters = [
          'sm_group_organisation_type_string' => $type->label(),
        ];

        $query_options = [
          'query' => SearchHelper::buildSolrQueryParams($filters),
        ];

        $overview_page = GlobalOverviewPages::getGlobalOverviewPageLink(GlobalOverviewPages::ORGANISATIONS);
        $url = $overview_page instanceof Link ? $overview_page->getUrl() : NULL;
        $url = $url instanceof Url ? $url->setOptions($query_options) : NULL;

        return [
          'type' => 'link',
          'label' => $type->label(),
          'path' => $url->toString(),
        ];
      }, $types);
      break;

  }

  if ($group->get('moderation_state')->value !== GroupsModerationHelper::GROUP_PUBLISHED_STATE) {
    $id = 'public';
    switch ($group->get('moderation_state')->value) {
      case GroupsModerationHelper::GROUP_DRAFT_STATE:
      case GroupsModerationHelper::GROUP_BLOCKED_STATE:
        $id = 'private';
        break;

      case GroupsModerationHelper::GROUP_PENDING_STATE:
        $id = 'restricted';
        break;

    }

    $tags[] = [
      'type' => 'display',
      'label' => ucfirst($group->get('moderation_state')->value),
      'id' => $id,
    ];
  }

  if (!empty($tags)) {
    foreach ($tags as $tag) {
      $variables['group_values']['tags'][] = [
        'extra_classes' => isset($tag['id']) ? 'ecl-tag--is-' . strtolower($tag['id']) : '',
        'tag' => $tag,
      ];
    }
  }

  switch ($group->getGroupType()->id()) {
    case 'event':
      $overview_id = GlobalOverviewPages::EVENTS;
      $overview_label = t('All events', [], ['context' => 'eic_community']);
      break;

    case 'organisation':
      $overview_id = GlobalOverviewPages::ORGANISATIONS;
      $overview_label = t('All organisations', [], ['context' => 'eic_community']);
      break;

    default:
      $overview_id = GlobalOverviewPages::GROUPS;
      $overview_label = t('All groups', [], ['context' => 'eic_community']);
      break;

  }

  if (_eic_community_can_use_social_share($group)) {
    $variables['group_values']['social_share'] = _eic_community_get_social_share_block();
  }

  // Adds url to navigate back to the list of groups.
  $back_url = GlobalOverviewPages::getGlobalOverviewPageLink(
    $overview_id
  )->getUrl();
  $variables['group_values']['back_url'] = $back_url ? $back_url->toString() : '';
  $variables['group_values']['back_label'] = $overview_label;
}
