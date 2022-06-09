<?php

/**
 * @file
 * Contains Events (node and group) related functions.
 */

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_media\MediaHelper;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Generate render array for the event detail page (group type and node type).
 *
 * @param array $variables
 *   The variables array.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The event entity.
 */
function _eic_community_render_event_detail_page(array &$variables, EntityInterface $entity) {
  $fields_map = [
    'node' => [
      'languages' => 'field_language',
      'documents' => 'field_document_media',
    ],
    'group' => [
      'languages' => 'field_vocab_language',
      'documents' => 'field_documents',
    ],
  ];

  $entity_type = $entity->getEntityTypeId();

  _eic_community_display_topics($variables);

  // We show flags only for node, group already have it in the group header.
  if (!$entity instanceof GroupInterface)
  {
    _eic_community_display_flags($variables);
  }

  $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
  if ($group instanceof GroupInterface) {
    if ('node' === $entity_type) {
      $variables['editorial_actions']['items'][]['content'] = _eic_community_get_share_group_content_link(
        $group,
        $entity
      );

      $existing_tags = $variables['#cache']['tags'] ?? [];
      $variables['#cache']['tags'] = array_merge($existing_tags, $group->getCacheTags());
      $visibility = \Drupal::service('oec_group_flex.group_visibility.storage')->load($group->id());
      if (in_array($group->bundle(), ['event', 'group']) && !GroupsModerationHelper::isBlocked($group) && $visibility instanceof GroupVisibilityRecordInterface
        && $visibility->getType() === GroupVisibilityType::GROUP_VISIBILITY_PUBLIC) {
        $variables['editorial_actions']['items'][]['content'] = _eic_community_get_social_share_block();
      }
    }

    $existing_tags = $variables['#cache']['tags'] ?? [];
    $variables['#cache']['tags'] = array_merge($existing_tags, $group->getCacheTags());
  }

  if ($entity->bundle() !== 'event') {
    return;
  }

  $location = $entity->hasField('field_location') ?
    $entity->get('field_location')->getValue() :
    NULL;
  $location_formatted = '';

  if (!empty($location)) {
    $location = reset($location);
    $location_formatted = EICGroupsHelper::formatAddress($location);
  }

  $allowed_values_location_type = eic_field_location_type_allowed_values();
  $location_types = [];

  if ($entity->hasField('field_location_type')) {
    $location_types = array_map(function ($item) use ($allowed_values_location_type) {
      return array_key_exists($item['value'], $allowed_values_location_type) ?
        strtolower($allowed_values_location_type[$item['value']]) :
        '';
    }, $entity->get('field_location_type')->getValue());
  }

  $dates = $entity->get('field_date_range')->getValue();
  $from_date = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $dates[0]['value']);
  $end_date = DrupalDateTime::createFromFormat('Y-m-d\TH:i:s', $dates[0]['end_value']);

  $action = [];

  // We only show signup in global event (group type: event).
  if ('group' === $entity_type && $signup_link = $entity->get('field_link')->getString()) {
    $action = [
      'title' => t('Sign up to this event', [], ['context' => 'eic_community']),
      'text' => t('Get notified of the latest changes and take part in the discussion linked to this event.', [],
        ['context' => 'eic_community']),
      'action' => [
        'label' => t('Sign up now', [], ['context' => 'eic_community']),
        'url' => $signup_link,
      ],
    ];
  }

  // Event detail items.
  $items = [
    [
      'content' => ucfirst(implode(' & ', $location_types)),
      'icon' => [
        'type' => 'custom',
        'name' => 'tag',
      ],
    ],
    [
      'content' => 'From ' . $from_date->format('d/m/Y H\hi') . ' to ' . $end_date->format('d/m/Y H\hi'),
      'icon' => [
        'type' => 'custom',
        'name' => 'calendar',
      ],
    ],
    [
      'content' => implode(', ', array_map(function (Term $term) {
        return $term->label();
      }, $entity->get($fields_map[$entity_type]['languages'])->referencedEntities())),
      'icon' => [
        'type' => 'custom',
        'name' => 'glob',
      ],
    ],
  ];

  // Adds location address in the beginning of events detail items.
  if ($location_formatted) {
    array_unshift(
      $items,
      [
        'content' => $location_formatted,
        'icon' => [
          'type' => 'custom',
          'name' => 'map-marker',
        ],
      ]
    );
  }

  $event_infos = [
    'icon_file_path' => $variables['eic_icon_path'],
    'title' => t('Event details', [], ['eic_community']),
    'items' => $items,
  ];

  if (!empty($action)) {
    $event_infos['action'] = $action;
  }

  if ($social_links = $entity->get('field_social_links')->getValue()) {
    $socials_platform_mapping = [
      'facebook' => [
        'url' => 'https://www.facebook.com/',
        'icon' => 'facebook-current',
      ],
      'twitter' => [
        'url' => 'https://www.twitter.com/',
        'icon' => 'twitter',
      ],
      'linkedin' => [
        'url' => 'https://www.linkedin.com/in/',
        'icon' => 'linkedin',
      ],
    ];

    foreach ($social_links as $social_link) {
      $event_infos['items'][] = [
        'content' => ucfirst($social_link['social']),
        'url' => $socials_platform_mapping[$social_link['social']]['url'] . $social_link['link'],
        'icon' => [
          'type' => 'custom',
          'name' => $socials_platform_mapping[$social_link['social']]['icon'],
        ],
      ];
    }
  }

  if ($website_url = $entity->get('field_website_url')->getString()) {
    $event_infos['items'][] = [
      'content' => $website_url,
      'url' => $website_url,
      'icon' => [
        'type' => 'custom',
        'name' => 'link',
      ],
    ];
  }

  if ($organized_by = $entity->get('field_organised_by')->value) {
    $variables['additional_sidebar_infos'] = [
      'title' => t('Organized by', [], ['context' => 'eic_community']),
      'content' => $organized_by,
    ];
  }

  $variables['event_infos'] = $event_infos;

  $files = $entity->get($fields_map[$entity_type]['documents'])->referencedEntities();
  $files_list = [];
  foreach ($files as $file) {
    $download = File::load($file->field_media_file->getValue()[0]['target_id']);
    $download_url = MediaHelper::formatMediaDownloadLink($file)
      ->toString();
    $files_list[] = [
      'name' => $file->getName(),
      'stats' => [
        [
          'hide_label' => TRUE,
          'icon' => [
            'name' => 'download',
            'type' => 'custom',
          ],
          'label' => t('downloads'),
          // @todo Add statistics value
          'value' => \Drupal::service('eic_media_statistics.entity_file_download_count')
            ->getFileDownloads($file),
        ],
        [
          'hide_label' => TRUE,
          'value' => format_size($download->get('filesize')->getString()),
        ],
      ],
      'mime_type' => _eic_community_get_file_type_icon_name(
        pathinfo($download->getFilename(), PATHINFO_EXTENSION)
      ),
      'path' => $download_url,
    ];
  }

  if (!empty($files_list)) {
    $variables['file_list'] = [
      'title' => t('Event files', ['%count' => count($files)]),
      'body' => '',
      'files' => $files_list,
      'icon_file_path' => $variables['eic_icon_path'],
      'download' => t('Download'),
    ];
  }

  /** @var \Drupal\taxonomy\TermInterface $event_type_term */
  $event_type_term = $entity->get('field_vocab_event_type')->entity;

  // Try to load translation if exists.
  if (
    $event_type_term &&
    $event_type_term->language()->getId() !== $entity->language()->getId() &&
    $event_type_term->hasTranslation($entity->language()->getId())
  ) {
    $event_type_term = $event_type_term->getTranslation($entity->language()->getId());
  }
  // Send event type to the theme variables.
  $variables['event_type'] = $event_type_term ? $event_type_term->getName() : NULL;
}
