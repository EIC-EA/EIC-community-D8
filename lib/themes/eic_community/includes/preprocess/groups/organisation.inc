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
use Drupal\group\Entity\GroupInterface;
use Drupal\eic_overviews\GroupOverviewPages;

/**
 * Implements hook_preprocess_group__TYPE().
 */
function eic_community_preprocess_group__organisation(&$variables) {
  /** @var \Drupal\group\Entity\GroupInterface $group */
  $group = $variables['group'];
  _eic_community_render_organisation_detail_page($variables, $group);
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

  $organisation_link = $group->get('field_organisation_link')->getValue();
  $link = reset($organisation_link);
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


  $mail_data = [];
  $mailto = $group->get('field_email')->value;
  $mail_label = $group->get('field_contact_label')->value;

  if ($mailto && $mail_label) {
    $mail_data = [
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
    ];
  }

  if ($mail_data) {
    $contact_data = [
      'title' => t('Contact', [], ['context' => 'eic_community']),
      'items' => $mail_data,
    ];
  }

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
    $location_formatted = \Drupal\eic_groups\EICGroupsHelper::formatAddress($address);

    return [
      'title' => t(
        'Address @index',
        ['@index' => $address_index],
        ['context' => 'eic_community']
      ),
      'description' => $location_formatted,
    ];
  }, $addresses);

  $social_data = [];

  if ($social_links = $group->get('field_social_links')->getValue()) {
    $socials_platform_mapping = [
      'facebook' => [
        'url' => 'https://www.facebook.com/',
        'icon' => 'facebook',
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

    $social_data = [
      'title' => '',
      'icon_file_path' => $variables['eic_icon_path'],
      'size' => 's',
    ];

    foreach ($social_links as $social_link) {
      $social_data['items'][] = [
        'label' => ucfirst($social_link['social']),
        'path' => $socials_platform_mapping[$social_link['social']]['url'] . $social_link['link'],
        'name' => $socials_platform_mapping[$social_link['social']]['icon'],
      ];
    }
  }

  $variables['contact'] = [
    'link' => $link_data,
    'social' => $social_data,
    'data' => $contact_data,
    'adresses' => [
      'title' => t('Adresses', [], ['context' => 'eic_community']),
      'items' => $addresses_data,
    ],
    'locations' => $locations_data,
    'icon_file_path' => $variables['eic_icon_path'],
  ];

  $needs = $group->get('field_needs')->referencedEntities();
  $offers = $group->get('field_offers')->referencedEntities();

  if (!empty($needs) || !empty($offers)) {
    $variables['announcements'] = [
      'title' => t('Announcements', [], ['context' => 'eic_community']),
      'items' => [
        [
          'title' => t('What we offer ', [], ['context' => 'eic_community']),
          'extra_classes' => 'ecl-featured-list--is-organisation-announcements',
          'title_element' => 'h4',
          'icon_file_path' => $variables['eic_icon_path'],
          'is_collapsible' => TRUE,
          'collapse_label' => t(
            'Show 2 more',
            [],
            ['context' => 'eic_community']
          ),
          'items' => array_map(function (ParagraphInterface $paragraph) {
            $link = $paragraph->get('field_cta_link')->getValue();
            $link = reset($link);

            return [
              'title' => $paragraph->get('field_title')->value,
              'description' => $paragraph->get('field_description')->value,
              'cta' => [
                'label' => $link['title'],
                'link' => $link ? Url::fromUri($link['uri'])->toString() : '',
              ],
            ];
          }, $offers),
        ],
        [
          'title' => t('We are looking for', [], ['context' => 'eic_community']
          )->render(),
          'extra_classes' => 'ecl-featured-list--is-organisation-announcements',
          'title_element' => 'h4',
          'icon_file_path' => $variables['eic_icon_path'],
          'is_collapsible' => TRUE,
          'collapse_label' => t(
            'Show 2 more',
            [],
            ['context' => 'eic_community']
          ),
          'items' => array_map(function (ParagraphInterface $paragraph) {
            $link = $paragraph->get('field_cta_link')->getValue();
            $link = reset($link);

            return [
              'title' => $paragraph->get('field_title')->value,
              'description' => $paragraph->get('field_description')->value,
              'cta' => [
                'label' => $link['title'],
                'link' => $link ? Url::fromUri($link['uri'])->toString() : '',
              ],
            ];
          }, $needs),
        ],
      ],
    ];
  }

  $variables['news'] = _eic_community_get_rendered_view(
    'organisation_content',
    'organisation_news'
  );
  $variables['events'] = _eic_community_get_rendered_view(
    'organisation_content',
    'organisation_events'
  );

  $view_builder = Drupal::entityTypeManager()->getViewBuilder('paragraph');
  $variables['teams'] = [
    'title' => t('Our team', [], ['context' => 'eic_community']),
    'call_to_action' => [
      'link' => [
        'label' => t('See all members', [], ['context' => 'eic_community']),
        'path' => Url::fromRoute(
          GroupOverviewPages::MEMBERS,
          ['group' => $group instanceof GroupInterface ? $group->id() : 0]
        ),
      ],
    ],
    'items' => array_map(
      function (ParagraphInterface $paragraph) use ($view_builder) {
        return ['content' => $view_builder->view($paragraph)];
      },
      $group->get('field_team_members')->referencedEntities()
    ),
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
        'collapse_label' => t('Less'),
        'expand_label' => t('More'),
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

  $variables['icon_file_path'] = $variables['eic_icon_path'];
}

/**
 * Implement hook_preprocess_paragraph__PARAGRAPH_TYPE.
 */
function eic_community_preprocess_paragraph__organisation_member(&$variables) {
  /** @var ParagraphInterface $paragraph */
  $paragraph = $variables['paragraph'];
  /** @var GroupInterface $group */
  $group = \Drupal::service('eic_groups.helper')->getGroupFromRoute();
  $user_ref = $paragraph->get('field_user_ref')->referencedEntities();
  /** @var \Drupal\user\UserInterface $user */
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

  /** @var \Drupal\eic_organisations\OrganisationsHelper $organisations_helper */
  $organisations_helper = \Drupal::service('eic_organisations.helper');
  $organisations = $organisations_helper->getUserOrganisations($user);
  $organisations = array_filter(
    $organisations,
    function ($organisation) use ($group) {
      return $group instanceof GroupInterface && $organisation->id() !== $group->id();
    }
  );

  $variables['member_data'] = [
    'icon_file_path' => $variables['eic_icon_path'],
    'image' => [
      'src' => $file_url,
    ],
    'path' => $user->toUrl(),
    'status' => [
      'label' => $paragraph->get('field_role')->value,
      'icon' => [
        'type' => 'custom',
        'name' => 'trophy_circle',
      ],
    ],
    'title' => realname_load($user),
    'job_titles' => $paragraph->get('field_job_title')->value,
    'organisations' => array_map(
      function (GroupInterface $group) use ($organisations_helper) {
        return [
          'label' => $group->label(),
          'path' => $group->toUrl()->toString(),
        ];
      },
      $organisations
    ),
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
