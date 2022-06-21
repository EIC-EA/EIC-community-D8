<?php

/**
 * @file
 * Prepares variables for group type organisation templates.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_overviews\GroupOverviewPages;
use Drupal\eic_search\SearchHelper;
use Drupal\eic_topics\Constants\Topics;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_features\GroupFeatureHelper;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\taxonomy\TermInterface;

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
      _eic_community_render_organisation_teaser($variables, $group);
      break;

  }

  // About page preprocessing.
  if (isset($variables['elements']['owners'])) {
    _preprocess_about_page($variables);
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
  /** @var TranslationInterface $string_translation */
  $string_translation = \Drupal::service('string_translation');

  $topics = $group->get('field_vocab_topics')->referencedEntities();
  $markets = $group->get('field_vocab_target_markets')->referencedEntities();
  $products = $group
    ->get('field_vocab_services_products')
    ->referencedEntities();

  $features = $group->get(GroupFeatureHelper::FEATURES_FIELD_NAME)->getValue();
  $features = array_map(function ($item) {
    return $item['value'];
  }, $features);

  $variables['block_enabled'] = [
    'news' => in_array('eic_groups_anchor_news', $features),
    'events' => in_array('eic_groups_anchor_group_events', $features),
    'members' => in_array('eic_groups_anchor_members', $features),
    'announcements' => in_array('eic_groups_announcements', $features),
  ];

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
      'collapse_label' => $string_translation->formatPlural(
        count($locations),
        '@count location',
        '@count locations',
        ['@count' => count($locations)],
        ['context' => 'eic_community']
      ),
      'items' => array_map(function (ParagraphInterface $location) {
        $city = $location->get('field_city')->value;
        $country = $location->get('field_country')->referencedEntities();
        $country = reset($country);
        return [
          'title' => $country->label() . ' (' . $city . ')',
        ];
      }, $locations),
    ];
  }

  $addresses = $group->get('field_address')->getValue();
  $addresses_data = array_map(function ($address) {
    $location_formatted = EICGroupsHelper::formatAddress($address);

    return [
      'title' => !empty($address['organization']) ? $address['organization'] : $address['locality'],
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
      'title' => t('Social media', [], ['context' => 'eic_community']),
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

  $url_add_announcement = Url::fromRoute(
    'entity.group.edit_form',
    ['group' => $group->id()],
    ['fragment' => 'edit-group-announcements']
  );

  $variables['empty_announcements'] = [
    'link' => [
      'label' => t('Add announcement', [], ['context' => 'eic_community']),
      'path' => $url_add_announcement->toString(),
      'extra_classes' => 'ecl-link--button ecl-link--button-primary',
      'type' => 'cta',
    ],
  ];

  $variables['can_add_announcement'] = $url_add_announcement->access();

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
            'Show more',
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
            'Show more',
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

  $url_add_news = Url::fromRoute(
    'entity.group_content.create_form',
    ['group' => $group->id(), 'plugin_id' => 'group_node:news']
  );

  $url_add_event = Url::fromRoute(
    'entity.group_content.create_form',
    ['group' => $group->id(), 'plugin_id' => 'group_node:event']
  );

  $render_news = _eic_community_get_rendered_view(
    'organisation_content',
    'organisation_news'
  );

  $render_events = _eic_community_get_rendered_view(
    'organisation_content',
    'organisation_events'
  );

  // Display the render view for some roles even if there are 0 results (so CTA to add content is shown)
  $variables['news'] = !$url_add_news->access() && empty($render_news['#rows']) ? [] : $render_news;
  $variables['events'] = !$url_add_event->access() && empty($render_events['#rows']) ? [] : $render_events;

  $view_builder = Drupal::entityTypeManager()->getViewBuilder('paragraph');
  $variables['teams'] = [
    'title' => t('Our team', [], ['context' => 'eic_community']),
    'call_to_action' => [
      'link' => [
        'label' => t('See all members', [], ['context' => 'eic_community']),
        'path' => Url::fromRoute(
          GroupOverviewPages::ORGANISATIONS_TEAM,
          ['group' => $group instanceof GroupInterface ? $group->id() : 0]
        )->toString(),
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
            'value' => count($group->getMembers()),
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
                'path' => _generate_overview_prefilter_url(
                  'sm_organisation_services_products',
                  $term->label()
                ),
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
                'path' => _generate_overview_prefilter_url(
                  'sm_organisation_target_market_name',
                  $term->label()
                ),
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
                'path' => _generate_overview_prefilter_url(
                  Topics::TERM_TOPICS_ID_FIELD_CONTENT_SOLR,
                  $term->label()
                ),
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
  $user_ref = $paragraph->get('field_user_ref')->referencedEntities();
  /** @var \Drupal\user\UserInterface $user */
  $user = reset($user_ref);
  /** @var \Drupal\media\MediaInterface|null $media_picture */
  $media_picture = $user->get('field_media')->referencedEntities();
  /** @var File|NULL $file */
  $file = $media_picture ? File::load(
    $media_picture[0]->get('oe_media_image')->target_id
  ) : NULL;
  $file_url = $file ? \Drupal::service('file_url_generator')->transformRelative(
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
    'organisations' => array_map(function(array $organisation) {
      return [
        'label' => $organisation['value'],
      ];
    }, $paragraph->get('field_organisations')->getValue()),
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

/**
 * Implement hook_preprocess_paragraph__PARAGRAPH_TYPE.
 */
function eic_community_preprocess_paragraph__organisation_member_external(&$variables) {
  /** @var ParagraphInterface $paragraph */
  $paragraph = $variables['paragraph'];

  $media_picture = $paragraph->get('field_media')->referencedEntities();
  $file = $media_picture ? File::load(
    $media_picture[0]->get('oe_media_image')->target_id
  ) : NULL;
  $file_url = $file ? \Drupal::service('file_url_generator')->transformRelative(
    file_create_url($file->get('uri')->value)
  ) : NULL;

  $location = $paragraph->get('field_location')->referencedEntities();

  $variables['member_data'] = [
    'icon_file_path' => $variables['eic_icon_path'],
    'image' => [
      'src' => $file_url,
    ],
    'title' => $paragraph->get('field_full_name')->value,
    'job_titles' => $paragraph->get('field_job_title')->value,
    'status' => [
      'label' => $paragraph->get('field_role')->value,
      'icon' => [
        'type' => 'custom',
        'name' => 'trophy_circle',
      ],
    ],
    'organisations' => array_map(function($value) {
      return [
        'label' => $value['value'],
      ];
    }, $paragraph->get('field_organisations')->getValue()),
    'location' => $location ? ['label' => reset($location)->label()] : '',
  ];
}

/**
 * @param string $filter
 * @param string $value
 *
 * @return string
 * @throws \Drupal\Core\Entity\EntityMalformedException
 */
function _generate_overview_prefilter_url(string $filter, string $value): string {
  $filters = [
    $filter => $value,
  ];

  $query_options = [
    'query' => SearchHelper::buildSolrQueryParams($filters),
  ];

  return GlobalOverviewPages::getGlobalOverviewPageLink(
    GlobalOverviewPages::ORGANISATIONS,
    $query_options
  )->getUrl()->toString();
}

/**
 * Fill variables for organisation teaser.
 */
function _eic_community_render_organisation_teaser(&$variables, GroupInterface $group) {
  // Get group description.
  $teaser['description'] = $group->hasField('field_body') ? Markup::create(Xss::filter($group->get('field_body')->value)) : '';
  // Get organisation thumbnail.
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
    $teaser['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $image_item->getAlt(),
    ];
  }

  // Add organisation types to the theme variables.
  if (!$group->get('field_organisation_type')->isEmpty()) {
    /** @var \Drupal\taxonomy\TermInterface $organisation_types */
    $organisation_types = $group->get('field_organisation_type')->referencedEntities();

    $teaser['tags'] = [];
    foreach ($organisation_types as $organisation_type) {
      // Build solr query parameters for the events overview page to filter
      // by topic.
      $params = SearchHelper::buildSolrQueryParams(
        [
          'sm_group_organisation_type_string' => $organisation_type ? $organisation_type->label() : '',
        ]
      );
      $tag = [
        'label' => $organisation_type ? $organisation_type->getName() : '',
      ];

      $url = GlobalOverviewPages::getGlobalOverviewPageLink(GlobalOverviewPages::ORGANISATIONS)->getUrl();

      if ($url) {
        $url->setOption('query', $params)
          ->toString();
        $tag['path'] = $url;
      }

      $teaser['tags'][] = $tag;
    }
  }

  // Get organisation location
  $locations = $group->get('field_locations')->referencedEntities();
  if (!empty($locations)) {
    $locations_data = [
      'items' => array_map(function (ParagraphInterface $location) {
        $city = $location->get('field_city')->value;
        $country = $location->get('field_country')->referencedEntities();
        $country = reset($country);
        return [
          'label' => "$city, {$country->label()}",
        ];
      }, $locations),
    ];
    $teaser['location'] = $locations_data['items'][0];
  }

  // Get organisation size
  if (!$group->get('field_organisation_employees')->isEmpty()) {
    $teaser['size'] = [
      'label' => t('@size employee(s)', ['@size' => $group->get('field_organisation_employees')->value]),
    ];
  }

  // Get organisation statistics.
  $organisation_statistics = \Drupal::service('eic_group_statistics.helper')->loadGroupStatistics($group);
  $teaser['stats'] = [
    'members' => [
      'label' => t('Members'),
      'value' => $organisation_statistics->getMembersCount(),
      'icon' => [
        'type' => 'custom',
        'name' => 'user',
      ],
    ],
    'comments' => [
      'label' => t('Comments'),
      'value' => $organisation_statistics->getCommentsCount(),
      'icon' => [
        'type' => 'custom',
        'name' => 'comment',
      ],
    ],
    'files' => [
      'label' => t('Files'),
      'value' => $organisation_statistics->getFilesCount(),
      'icon' => [
        'type' => 'custom',
        'name' => 'documents',
      ],
    ],
    'events' => [
      'label' => t('Events'),
      'value' => $organisation_statistics->getEventsCount(),
      'icon' => [
        'type' => 'custom',
        'name' => 'calendar',
      ],
    ],
  ];

  $variables['organisation_item'] = $teaser;
}
