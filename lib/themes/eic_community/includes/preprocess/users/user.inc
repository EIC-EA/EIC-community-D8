<?php

/**
 * @file
 * Functions to support theming.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\eic_community\ValueObject\ImageValueObject;
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
      // Get the user realname.
      $teaser['title'] = \Drupal::service('eic_user.helper')->getFullName($user);
      if ($user->toUrl()->access()) {
        $teaser['path'] = $user->toUrl()->toString();
      }
      $teaser['icon_file_path'] = $variables['eic_icon_path'];
      $teaser['actions'] = array_merge(_eic_community_get_user_contact_link($user), _eic_community_get_user_social_links($user));
      $variables['user_item'] = $teaser;
      break;

  }
}

/**
 * Creates image array for twig templates.
 *
 * @param Drupal\Core\Entity\EntityInterface $user
 *   User entity of which you want the image array.
 *
 * @return mixed
 *   Returns array or NULL.
 */
function _get_profile_image_array(EntityInterface $user): ?array {
  if ($user->field_media->target_id == NULL) {
    return NULL;
  }

  $user_image_media = Media::load($user->field_media->target_id);
  $user_image_media_fid = $user_image_media->getSource()
    ->getSourceFieldValue($user_image_media);
  $user_image_file = File::load($user_image_media_fid);

  return [
    'src' => $user_image_file->createFileUrl(),
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
    $image_item = ImageValueObject::fromImageItem($media->get('oe_media_image')->first());
    $author['image'] = [
      'src' => $image_item->getSource(),
      'alt' => $author['name'],
    ];
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
 * @param UserInterface $account
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
 * @param UserInterface $account
 *   The user account for which we want to get the social links.
 *
 * @return array
 *   The render array for templates.
 */
function _eic_community_get_user_social_links(UserInterface $account) {
  $member_profile = \Drupal::service('eic_user.helper')->getUserMemberProfile($account);

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
