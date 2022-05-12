<?php

/**
 * @file
 * Functions to support theming.
 */

use CommerceGuys\Addressing\Country\CountryRepository;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\eic_flags\FlagType;
use Drupal\eic_helper\DateTimeHelper;
use Drupal\eic_overviews\GlobalOverviewPages;
use Drupal\eic_private_message\Constants\PrivateMessage;
use Drupal\image\Entity\ImageStyle;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Implements hook_preprocess_user().
 */
function eic_community_preprocess_user(array &$variables) {
  /** @var \Drupal\user\UserInterface $user */
  $user = $variables['elements']['#user'];

  switch ($variables['elements']['#view_mode']) {
    case 'teaser':
      $teaser = [];
      // Get the user profile.
      /** @var \Drupal\media\MediaInterface $media */
      $media = $user->get('field_media')->referencedEntities()[0];
      if ($media && !empty($media->get('oe_media_image')->getValue())) {
        $style_name = 'oe_theme_list_item';
        $image_item = ImageValueObject::fromStyledImageItem($media->get('oe_media_image')->first(), $style_name);
        $teaser['image'] = [
          'src' => $image_item->getSource(),
          'alt' => $image_item->getAlt(),
        ];
      }
      $teaser['location'] = _eic_community_get_user_location($user);
      // Get the user realname.
      $teaser['title'] = \Drupal::service('eic_user.helper')->getFullName($user);
      if ($user->toUrl()->access()) {
        $teaser['path'] = $user->toUrl()->toString();
      }
      $teaser['icon_file_path'] = $variables['eic_icon_path'];
      $teaser['actions'] = array_merge([_eic_community_get_user_contact_link($user)], _eic_community_get_user_social_links($user));
      $variables['user_item'] = $teaser;
      break;

    case 'full':
      $current_user = \Drupal::currentUser();
      $member_profile = \Drupal::service('eic_user.helper')->getUserMemberProfile($user);
      $user_item = [
        'editorial_header' => [
          'actions' => [],
          'icon_file_path' => $variables['eic_icon_path'],
          'parent' => [
            'link' => [
              'label' => t('All members'),
              'path' => GlobalOverviewPages::getGlobalOverviewPageLink(GlobalOverviewPages::MEMBERS)->getUrl(),
            ],
          ],
        ],
        'icon_file_path' => $variables['eic_icon_path'],
        'title' => \Drupal::service('eic_user.helper')->getFullName($user),
        'description' => _eic_community_get_user_profile_body($user, $member_profile),
        'type' => _eic_community_get_user_type($user, $member_profile),
        'job_titles' => _eic_community_get_user_job_titles($user, $member_profile),
        'image' => _get_profile_image_array($user),
        'meta' => [
          [
            'label' => t('Email'),
            'items' => [
              [
                'label' => $user->getEmail(),
                'path' => "mailto:{$user->getEmail()}",
              ],
            ],
          ],
        ],
      ];

      // Gets user operation links.
      if ($user_operation_links = _eic_community_get_user_operation_links($user)) {
        $user_operation_links['icon'] = [
          'name' => 'gear',
          'type' => 'custom',
        ];
        $user_operation_links['icon_file_path'] = $variables['eic_icon_path'];
        $user_operation_links['extra_classes'] = 'ecl-collapsible-options--blue';
        $user_item['editorial_header']['actions'][] = $user_operation_links;
      }

      // We show the contact link and follow flag if current user is not
      // viewing its own profile page.
      if ($current_user->id() !== $user->id()) {
        // Gets user contact link.
        if (
          ($user_contact_link = _eic_community_get_user_contact_link($user)) &&
          $user->get(PrivateMessage::PRIVATE_MESSAGE_USER_ALLOW_CONTACT_ID)->value
        ) {
          $user_contact_link['icon']['name'] = 'send';
          $user_contact_link['link'] = [
            'label' => t('Direct message'),
            'path' => $user_contact_link['path'],
          ];
          $user_item['editorial_header']['actions'][] = $user_contact_link;
        }

        // Gets the follow user flag.
        if (isset($variables['elements']['flag_' . FlagType::FOLLOW_USER])) {
          $user_item['editorial_header']['actions'][] = ['content' => $variables['elements']['flag_' . FlagType::FOLLOW_USER]];
        }
      }

      // Gets user social links.
      if ($social_links = _eic_community_get_user_social_links($user, $member_profile)) {
        // Fix social link icons.
        $social_links = array_map(function ($key, $value) {
          $value['name'] = $key;
          return $value;
        }, array_keys($social_links), array_values($social_links));
        // Adds social links.
        $user_item['social_share'] = [
          'title' => t('Social media'),
          'items' => $social_links,
        ];
      }

      // Gets user location.
      if ($location = _eic_community_get_user_location($user, 'multiline', $member_profile)) {
        $user_item['meta'][] = [
          'label' => t('Location'),
          'items' => [
            [
              'content' => [
                '#type' => 'inline_template',
                '#template' => '{{ description|raw }}',
                '#context' => [
                  'description' => $location['label'],
                ],
              ],
            ],
          ],
        ];
      }

      // Gets user statistics.
      $user_item['stats'] = [
        [
          'label' => t('Joined @date', [
            '@date' => _eic_community_get_user_joining_date($user),
          ]),
          'icon' => [
            'name' => 'time',
            'type' => 'custom',
          ],
        ],
        [
          'label' => t('@user-followers followers', [
            '@user-followers' => \Drupal::service('eic_user.helper')->getUserFollowers($user),
          ]),
          'icon' => [
            'name' => 'group',
            'type' => 'custom',
          ],
        ],
      ];

      $user_item['flagged_interests'] = [
        'title' => $current_user->id() === $user->id() ? t('My interests') : t('Interests'),
        'items' => [],
      ];
      // Gets user topics of interest.
      if ($topics_of_interest = _eic_community_get_user_topics($user, 'field_vocab_topic_interest', $member_profile)) {
        $user_item['flagged_interests']['items'][] = [
          'title' => t('Topics of interests'),
          'items' => $topics_of_interest,
        ];
      }
      // Gets user topics of expertise.
      if ($topics_of_expertise = _eic_community_get_user_topics($user, 'field_vocab_topic_expertise', $member_profile)) {
        $user_item['flagged_interests']['items'][] = [
          'title' => t('Topics of expertise'),
          'items' => $topics_of_expertise,
        ];
      }

      $user_item['flagged_groups'] = [
        'title' => $current_user->id() === $user->id() ? t('My groups') : t('Groups'),
        'content' => ($current_user->id() === $user->id()
          ? _eic_community_get_rendered_view('groups', 'block_user_profile_my_groups', [$user->id()])
          : _eic_community_get_rendered_view('groups', 'block_user_profile_groups', [$user->id()])
        ),
      ];

      $user_item['flagged_events'] = [
        'title' => $current_user->id() === $user->id() ? t('My events') : t('Events'),
        'content' => ($current_user->id() === $user->id()
          ? _eic_community_get_rendered_view('groups', 'block_user_profile_my_events', [$user->id()])
          : _eic_community_get_rendered_view('groups', 'block_user_profile_events', [$user->id()])
        ),
      ];

      $user_item['flagged_projects'] = [
        'title' => $current_user->id() === $user->id() ? t('My organisations') : t('Organisations'),
        'content' => ($current_user->id() === $user->id()
          ? _eic_community_get_rendered_view('groups', 'block_user_profile_my_organisations', [$user->id()])
          : _eic_community_get_rendered_view('groups', 'block_user_profile_organisations', [$user->id()])
        ),
      ];

      $variables['user_item'] = $user_item;
      break;

  }
}

/**
 * Creates image array for twig templates.
 *
 * @param Drupal\Core\Entity\EntityInterface $user
 *   User entity of which you want the image array.
 * @param bool $relative_url
 *   Whether the image url is relative or absolute.
 *
 * @return mixed
 *   Returns array or NULL.
 */
function _get_profile_image_array(EntityInterface $user, $relative_url = TRUE): ?array {
  if ($user->field_media->target_id == NULL) {
    return NULL;
  }

  $user_image_media = Media::load($user->field_media->target_id);
  $user_image_media_fid = $user_image_media->getSource()
    ->getSourceFieldValue($user_image_media);
  $user_image_file = File::load($user_image_media_fid);

  return [
    'src' => $user_image_file->createFileUrl($relative_url),
    'alt' => $user_image_media->oe_media_image->alt,
  ];
}

/**
 * Transform a user entity for a storybook template.
 *
 * @param Drupal\user\UserInterface $user
 *   The user object.
 *
 * @return array
 *   An array with following structure:
 *   - image
 *     - src
 *     - alt
 *
 * @throws \Drupal\Core\Entity\EntityMalformedException
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function eic_community_get_teaser_user_display(UserInterface $user) {
  $author = [
    'name' => $user->getDisplayName(),
  ];

  // We add the user profile page URL if the current user has access to it.
  if ($user->toUrl()->access()) {
    $author['path'] = $user->toUrl()->toString();
  }

  if (!$user->get('field_media')->isEmpty()) {
    /** @var \Drupal\media\Entity\Media $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($user->get('field_media')->entity);
    if ($media->bundle() == 'image') {
      $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
      $author['image'] = [
        'src' => $image_item->getSource(),
        'alt' => $author['name'],
      ];
    }
  }

  return $author;
}

/**
 * Preprocess contributors for the storybook template.
 */
function _eic_community_display_document_contributors(array &$variables) {
  if (!empty($variables['elements']['contributor_ids'])) {
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($variables['elements']['contributor_ids']);
    $contributors = ['items' => []];

    foreach ($users as $user) {
      $contributors['items'][] = eic_community_get_teaser_user_display($user);
    }

    $variables['contributors'] = $contributors;
  }
}

/**
 * Returns the 'render' array to use in templates for the user contact.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the contact link.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_contact_link(UserInterface $account) {
  $current_user = \Drupal::currentUser();

  if (!$current_user->hasPermission('access contact user page')) {
    return [];
  }

  $link_options = [
    'query' => [
      'destination' => \Drupal::request()->getRequestUri(),
    ],
  ];
  $result = [
    'label' => t('Contact @username', ['@username' => $account->realname]),
    'path' => Url::fromRoute('eic_private_message.user_private_message', ['user' => $account->id()], $link_options),
    'icon' => [
      'name' => 'mail',
      'type' => 'custom',
    ],
  ];

  return $result;
}

/**
 * Returns the 'render' array to use in templates for the user social links.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the social links.
 * @param \Drupal\profile\Entity\ProfileInterface $member_profile
 *   (optional) The user profile entity associated with user account.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_social_links(UserInterface $account, ProfileInterface $member_profile = NULL) {
  $member_profile = $member_profile ?? \Drupal::service('eic_user.helper')->getUserMemberProfile($account);

  // If we don't have a member profile for this user, skip.
  if (empty($member_profile)) {
    return [];
  }

  // If the member profile doesn't have social links, skip.
  if ($member_profile->get('field_social_links')->isEmpty()) {
    return [];
  }

  // Get the list of social platforms provided by the social_link_field module.
  $social_platforms = \Drupal::service('plugin.manager.social_link_field.platform')->getPlatforms();

  $links = [];
  foreach ($member_profile->get('field_social_links')->getValue() as $social_item) {
    $links[$social_item['social']] = [
      'label' => $social_platforms[$social_item['social']]['name'],
      'path' => $social_platforms[$social_item['social']]['urlPrefix'] . $social_item['link'],
      'icon' => [
        'name' => $social_item['social'],
        'type' => 'custom',
      ],
    ];
  }

  return $links;
}

/**
 * Returns the 'render' array to use in templates for the user location.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the location.
 * @param string $format
 *   (optional) Either 'inline' or 'multiline'.
 * @param \Drupal\profile\Entity\ProfileInterface $member_profile
 *   (optional) The user profile entity associated with user account.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_location(UserInterface $account, $format = 'inline', ProfileInterface $member_profile = NULL) {
  $member_profile = $member_profile ?? \Drupal::service('eic_user.helper')->getUserMemberProfile($account);

  // If we don't have a member profile for this user, skip.
  if (empty($member_profile)) {
    return [];
  }

  // If the member profile doesn't have the address field, skip.
  if ($member_profile->get('field_location_address')->isEmpty()) {
    return [];
  }

  $address = $member_profile->get('field_location_address')->first();

  /** @var \CommerceGuys\Addressing\Country\CountryRepository $country_repository */
  $country_repository = new CountryRepository();
  /** @var \CommerceGuys\Addressing\Country\Country $country */
  $country = $country_repository->get($address->get('country_code')->getValue());

  $location = '';
  switch ($format) {
    case 'multiline':
      $location = $address->get('locality')->getValue() . '<br>' . $country->getName();
      break;

    default:
      $location = $address->get('locality')->getValue() . ', ' . $country->getName();
      break;
  }

  return [
    'label' => $location,
  ];
}

/**
 * Returns the 'render' array to use in templates for the user profile body.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the profile body.
 * @param \Drupal\profile\Entity\ProfileInterface $member_profile
 *   (optional) The user profile entity associated with user account.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_profile_body(UserInterface $account, ProfileInterface $member_profile = NULL) {
  $member_profile = $member_profile ?? \Drupal::service('eic_user.helper')->getUserMemberProfile($account);

  // If we don't have a member profile for this user, skip.
  if (empty($member_profile)) {
    return [];
  }

  // If the member profile doesn't have the body field, skip.
  if ($member_profile->get('field_body')->isEmpty()) {
    return [];
  }

  return [
    '#type' => 'inline_template',
    '#template' => '{{ description|raw }}',
    '#context' => [
      'description' => $member_profile->get('field_body')->value,
    ],
  ];
}

/**
 * Returns the 'render' array to use in templates for the user job titles.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the job titles.
 * @param \Drupal\profile\Entity\ProfileInterface $member_profile
 *   (optional) The user profile entity associated with user account.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_job_titles(UserInterface $account, ProfileInterface $member_profile = NULL) {
  $member_profile = $member_profile ?? \Drupal::service('eic_user.helper')->getUserMemberProfile($account);

  // If we don't have a member profile for this user, skip.
  if (empty($member_profile)) {
    return [];
  }

  // If the member profile doesn't have job titles, skip.
  if ($member_profile->get('field_vocab_job_title')->isEmpty()) {
    return [];
  }

  $job_titles = [];
  /** @var \Drupal\taxonomy\Entity\Term[] $job_titles */
  $job_titles_terms = $member_profile->get('field_vocab_job_title')->referencedEntities();
  // Adds each fact and figure to the theme variables.
  foreach ($job_titles_terms as $job_title) {
    $job_title_translation = \Drupal::service('entity.repository')->getTranslationFromContext($job_title, $account->language()->getId());
    // @todo Add missing organisations.
    $job_titles[] = [
      'label' => $job_title_translation->getName(),
      'items' => [],
    ];
  }

  return $job_titles;
}

/**
 * Returns the formatted date when the user joined the platform.
 *
 * @param Drupal\user\UserInterface $account
 *   The user account for which we want to get joining date.
 *
 * @return string
 *   The formatted date when the user joined the platform.
 */
function _eic_community_get_user_joining_date(UserInterface $account, $format = DateTimeHelper::DATE_FORMAT_MONTH_FULL_YEAR) {
  $date_formatter = \Drupal::service('date.formatter');
  return $date_formatter->format($account->getCreatedTime(), $format);
}

/**
 * Returns the array of topics terms to use in templates.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the topics.
 * @param string $taxonomy_term_field_name
 *   The taxonomy term field name.
 * @param \Drupal\profile\Entity\ProfileInterface $member_profile
 *   (optional) The user profile entity associated with user account.
 *
 * @return array
 *   The array of topic terms.
 */
function _eic_community_get_user_topics(UserInterface $account, $taxonomy_term_field_name, ProfileInterface $member_profile = NULL) {
  $member_profile = $member_profile ?? \Drupal::service('eic_user.helper')->getUserMemberProfile($account);

  // If we don't have a member profile for this user, skip.
  if (empty($member_profile)) {
    return [];
  }

  // If the member profile doesn't have the field, skip.
  if (!$member_profile->hasField($taxonomy_term_field_name)) {
    return [];
  }

  // If the member profile doesn't have any selected topics, skip.
  if ($member_profile->get($taxonomy_term_field_name)->isEmpty()) {
    return [];
  }

  /** @var \Drupal\taxonomy\TermInterface[] $terms */
  $terms = $member_profile->get($taxonomy_term_field_name)->referencedEntities();

  $topics = [];
  foreach ($terms as $term) {
    $loaded_parents = [];
    /** @var \Drupal\taxonomy\TermInterface[] $loaded_parents */
    $loaded_parents = _eic_community_get_term_parents($term, $loaded_parents);
    $loaded_parents = array_reverse($loaded_parents, TRUE);
    $topics = _eic_community_process_term_parents_tree($term, $topics, $loaded_parents);
  }

  return $topics;
}

/**
 * Returns the 'render' array to use in templates for the user type.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the user type.
 * @param \Drupal\profile\Entity\ProfileInterface $member_profile
 *   (optional) The user profile entity associated with user account.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_type(UserInterface $account, ProfileInterface $member_profile = NULL) {
  $member_profile = $member_profile ?? \Drupal::service('eic_user.helper')->getUserMemberProfile($account);

  // If we don't have a member profile for this user, skip.
  if (empty($member_profile)) {
    return [];
  }

  // If the member profile doesn't have job titles, skip.
  if ($member_profile->get('field_vocab_user_type')->isEmpty()) {
    return [];
  }

  /** @var \Drupal\taxonomy\Entity\TermInterface[] $user_type */
  $user_types = $member_profile->get('field_vocab_user_type')->referencedEntities();
  $user_type = reset($user_types);
  return [
    'label' => $user_type->getName(),
    'icon' => [
      'name' => 'star_circle',
      'type' => 'custom',
    ],
  ];
}

/**
 * Returns the 'render' array to use in templates for the operation links.
 *
 * @param \Drupal\user\UserInterface $account
 *   The user account for which we want to get the user operation links.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_operation_links(UserInterface $account, ProfileInterface $member_profile = NULL) {
  // Get user operation links.
  $user_operation_links = \Drupal::entityTypeManager()->getListBuilder($account->getEntityTypeId())
    ->getOperations($account);

  $processed_user_operation_links = [];
  foreach ($user_operation_links as $key => $user_operation_link) {
    switch ($key) {
      // Override operation link titles.
      case 'edit':
        $user_operation_link['title'] = t('Edit account information');
        break;

      case 'delete':
        $user_operation_link['title'] = t('Delete account');
        break;

    }
    $processed_user_operation_links[] = [
      'link' => [
        'label' => $user_operation_link['title'],
        'path' => $user_operation_link['url']->toString(),
      ],
    ];
  }

  // Loads user member profile.
  $member_profile = $member_profile ?? \Drupal::service('eic_user.helper')->getUserMemberProfile($account);
  // Get user member profile operation links.
  if ($member_profile) {
    $member_profile_operation_links = \Drupal::entityTypeManager()->getListBuilder($member_profile->getEntityTypeId())
      ->getOperations($member_profile);
    foreach ($member_profile_operation_links as $key => $member_profile_operation_link) {
      // Override operation link titles.
      switch ($key) {
        case 'edit':
          $member_profile_operation_link['title'] = t('Edit profile details');
          break;

        case 'delete':
          $member_profile_operation_link['title'] = t('Delete profile');
          break;

      }
      $processed_user_operation_links[] = [
        'link' => [
          'label' => $member_profile_operation_link['title'],
          'path' => $member_profile_operation_link['url']->toString(),
        ],
      ];
    }
  }

  if (empty($processed_user_operation_links)) {
    return [];
  }

  return [
    'label' => 'Manage profile',
    'items' => $processed_user_operation_links,
  ];
}

/**
 * Creates image style array for twig templates.
 *
 * @param \Drupal\user\UserInterface $user
 *   User entity of which you want the image array.
 * @param string $image_style
 *   The image style to use.
 *
 * @return mixed
 *   Returns array or NULL.
 */
function _eic_community_get_user_profile_image_style(UserInterface $user, string $image_style = 'thumbnail'): ?array {
  if ($user->field_media->target_id == NULL) {
    return NULL;
  }

  $user_image_media = Media::load($user->field_media->target_id);
  $user_image_media_fid = $user_image_media->getSource()
    ->getSourceFieldValue($user_image_media);

  $user_image_file = File::load($user_image_media_fid);
  $style = ImageStyle::load($image_style);
  $image_uri = $style->buildUri($user_image_file->get('uri')->value);

  // If the derivative doesn't exist yet (as the image style may have been
  // added post launch), create it.
  if (!file_exists($image_uri)) {
    $style->createDerivative(
      $user_image_file->get('uri')->value,
      $image_uri
    );
  }

  $image_style_url = $style->buildUrl($user_image_file->get('uri')->value);
  return [
    'src' => $image_style_url,
    'alt' => $user_image_media->oe_media_image->alt,
  ];
}
