<?php

/**
 * @file
 * Prepares variables for group type organisation templates.
 */

use Drupal\file\Entity\File;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Url;
use Drupal\address\FieldHelper;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Implements hook_preprocess_group__TYPE().
 */
function eic_community_preprocess_group__organisation(&$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];

  switch ($variables['view_mode']) {
    case 'full':
      _eic_community_render_organisation_detail_page($variables, $group);
      break;

    case 'teaser':
    case 'small_teaser':
      // Add event topics to the theme variables.
      if (!$group->get('field_vocab_topics')->isEmpty()) {
        $topics = $group->get('field_vocab_topics')->referencedEntities();
      }
  }
}

/**
 * Fill informations for organisation detail page full.
 *
 * @param $variables
 * @param $group
 *
 * @throws \Drupal\Core\Entity\EntityMalformedException
 */
function _eic_community_render_organisation_detail_page(&$variables, $group) {
  /** @var \Drupal\eic_group_statistics\GroupStatisticsHelperInterface $group_helper */
  $group_helper = Drupal::service('eic_group_statistics.helper');
  $group_statistics = $group_helper->loadGroupStatistics($group);

  $topics = $group->get('field_vocab_topics')->referencedEntities();
  $markets = $group->get('field_vocab_target_markets')->referencedEntities();
  $products = $group
    ->get('field_vocab_services_products')
    ->referencedEntities();

  $link = reset($group->get('field_organisation_link')->getValue());
  $link_data = $link ?
    [
      'title' => 'Link',
      'items' => [
        [
          'icon' => [
            'name' => 'link',
            'type' => 'custom',
          ],
          'link' => [
            'label' => $link['title'],
            'path' => $link ? Url::fromUri($link['uri'])->toString() : '',
          ],
        ],
      ],
    ] :
    NULL;

  $mailto = $group->get('field_email')->value;
  $mail_label = $group->get('field_contact_label')->value;

  $contact_data = [
    'title' => t('Contact', [], ['context' => 'eic_community']),
    'items' => [
      [
        'name' => $mail_label,
        'link' => [
          'icon' => [
            'name' => 'mail',
            'type' => 'custom',
          ],
          'link' => [
            'label' => t('Send email', [], ['context' => 'eic_community']),
            'path' => "mailto:$mailto",
          ],
        ],
      ],
    ],
  ];

  $locations = $group->get('field_locations')->referencedEntities();
  $locations_data = [];

  if (!empty($locations)) {
    $locations_data = [
      'title' => t('Locations', [], ['context' => 'eic_community']),
      'collapse_label' => t(
        '@count locations',
        ['@count' => count($locations)],
        ['context' => 'eic_community']
      ),
      'items' => array_map(function (\Drupal\taxonomy\Entity\Term $location) {
        return [
          'title' => $location->label(),
        ];
      }, $locations),
    ];
  }

  $addresses = $group->get('field_address')->getValue();
  $address_index = 0;
  $addresses_data = array_map(function ($address) use ($address_index) {
    $address_index++;
    $countries_map = \Drupal\Core\Locale\CountryManager::getStandardList();
    $location_formatted = $address[FieldHelper::getPropertyName(
        AddressField::ADDRESS_LINE1
      )] . ' ' . $address[$address[FieldHelper::getPropertyName(
        AddressField::ADDRESS_LINE2
      )]] . '<br />';
    $location_formatted .= $address[FieldHelper::getPropertyName(
        AddressField::POSTAL_CODE
      )] . ' ' . $address[FieldHelper::getPropertyName(
        AddressField::LOCALITY
      )] . '<br />';
    $location_formatted .= array_key_exists(
      $address['country_code'],
      $countries_map
    ) ?
      $countries_map[$address['country_code']] :
      '';

    return [
      'title' => t(
        'Address @index',
        ['@index' => $address_index],
        ['context' => 'eic_community']
      ),
      'description' => $location_formatted,
    ];
  }, $addresses);

  $variables['contact'] = [
    'link' => $link_data,
    'social' => [],
    'data' => $contact_data,
    'adresses' => [
      'title' => t('Adresses', [], ['context' => 'eic_community']),
      'items' => $addresses_data,
    ],
    'locations' => $locations_data,
  ];

  $needs = $group->get('field_needs')->referencedEntities();
  $offers = $group->get('field_offers')->referencedEntities();

  $variables['announcements'] = [
    'title' => t('Announcements', [], ['context' => 'eic_community']),
    'items' => [
      [
        'title' => t('We are looking for', [], ['context' => 'eic_community']),
        'extra_classes' => 'ecl-featured-list--is-organisation-announcements',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'collapse_label' => t(
          'Show @count more',
          ['@count' => count($needs)],
          ['context' => 'eic_community']
        ),
        'items' => [
          [
            'content' => $variables['content']['field_needs'],
          ],
        ],
      ],
      [
        'title' => t('What we offer', [], ['context' => 'eic_community']),
        'extra_classes' => 'ecl-featured-list--is-organisation-announcements',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'collapse_label' => t(
          'Show @count more',
          ['@count' => count($offers)],
          ['context' => 'eic_community']
        ),
        'items' => [
          [
            'content' => $variables['content']['field_offers'],
          ],
        ],
      ],
    ],
  ];

  $variables['news'] = _eic_community_get_rendered_view(
    'organisation_content',
    'organisation_news'
  );
  $variables['events'] = _eic_community_get_rendered_view(
    'organisation_content',
    'organisation_events'
  );

  $variables['teams'] = [
    'title' => t('Our team', [], ['context' => 'eic_community']),
    'call_to_action' => [
      'link' => [
        'label' => t('See all members', [], ['context' => 'eic_community']),
        'path' => '/test',
      ],
    ],
    'items' => [
      [
        'content' => $variables['content']['field_team_members'],
      ],
    ],
  ];

  $variables['details'] = [
    'title' => t('Organisation details', [], ['context' => 'eic_community']),
    'items' => [
      [
        'title' => t('Organisation', [], ['context' => 'eic_community']),
        'type' => 'stats',
        'items' => [
          [
            'name' => t('Employees', [], ['context' => 'eic_community']),
            'value' => $group->get('field_organisation_employees')->value,
          ],
          [
            'name' => t('Active members', [], ['context' => 'eic_community']),
            'value' => $group_statistics->getMembersCount(),
          ],
          [
            'name' => t('Annual turnover', [], ['context' => 'eic_community']),
            'value' => $group->get('field_organisation_turnover')->value,
          ],
          [
            'name' => t(
              'Date of establishement',
              [],
              ['context' => 'eic_community']
            ),
            'value' => $group->get('field_date_establishement')->value,
          ],
        ],
      ],
      [
        'title' => t(
          'Services and products offered',
          [],
          ['context' => 'eic_community']
        ),
        'type' => 'tags',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'grid' => TRUE,
        'collapse_label' => 'Less',
        'expand_label' => 'More',
        'items' => array_map(function (TermInterface $term) {
          return
            [
              'tag' => [
                'type' => 'link',
                'path' => $term->toUrl()->toString(),
                'label' => $term->getName(),
              ],
            ];
        }, $products),
      ],
      [
        'title' => t('Target markets', [], ['context' => 'eic_community']),
        'type' => 'tags',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'grid' => TRUE,
        'collapse_label' => 'Less',
        'expand_label' => 'More',
        'items' => array_map(function (TermInterface $term) {
          return
            [
              'tag' => [
                'type' => 'link',
                'path' => $term->toUrl()->toString(),
                'label' => $term->getName(),
              ],
            ];
        }, $markets),
      ],
      [
        'title' => t('Topics', [], ['context' => 'eic_community']),
        'type' => 'tags',
        'title_element' => 'h4',
        'icon_file_path' => $variables['eic_icon_path'],
        'is_collapsible' => TRUE,
        'grid' => TRUE,
        'collapse_label' => 'Less',
        'expand_label' => 'More',
        'items' => array_map(function (TermInterface $term) {
          return
            [
              'tag' => [
                'type' => 'link',
                'path' => $term->toUrl()->toString(),
                'label' => $term->getName(),
              ],
            ];
        }, $topics),
      ],
    ],
  ];
}

/**
 * Implement hook_preprocess_paragraph__PARAGRAPH_TYPE.
 */
function eic_community_preprocess_paragraph__organisation_member(&$variables) {
  /** @var ParagraphInterface $paragraph */
  $paragraph = $variables['paragraph'];
  $user_ref = $paragraph->get('field_user_ref')->referencedEntities();
  $user = reset($user_ref);
  /** @var \Drupal\media\MediaInterface|null $media_picture */
  $media_picture = $user->get('field_media')->referencedEntities();
  /** @var File|NULL $file */
  $file = $media_picture ? File::load(
    $media_picture[0]->get('oe_media_image')->target_id
  ) : NULL;
  $file_url = $file ? file_url_transform_relative(
    file_create_url($file->get('uri')->value)
  ) : NULL;
  $last_activity_ago = eic_community_get_teaser_time_display(
    $user->get('changed')->value
  );

  $variables['member_data'] = [
    'icon_file_path' => $variables['eic_icon_path'],
    'image' => [
      'src' => $file_url,
    ],
    'status' => [
      'label' => $paragraph->get('field_job_title')->value,
      'icon' => [
        'type' => 'custom',
        'name' => 'trophy_circle',
      ],
    ],
    'title' => realname_load($user),
    'job_titles' => $paragraph->get('field_job_title')->value,
    'organisations' => [
      'label' => 'Entreprise test',
      'path' => '/test',
    ],
    'location' => _eic_community_get_user_location($user),
    'stats' => [
      [
        'label' => t(
          'Last activity',
          [],
          ['context' => 'eic_community']
        ),
        'value' => t(
          'Last activity @time',
          ['@time' => $last_activity_ago],
          ['context' => 'eic_community']
        ),
        'icon' => [
          'name' => 'time',
          'type' => 'custom',
        ],
      ],
    ],
  ];
}
