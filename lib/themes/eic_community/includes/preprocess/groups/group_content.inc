<?php

/**
 * @file
 * Prepares variables for group content templates.
 */

use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_group_content().
 */
function eic_community_preprocess_group_content__group_membership__teaser(array &$variables) {
  /** @var \Drupal\group\Entity\GroupContentInterface $membership */
  $membership = $variables['group_content'];
  // Get the user.
  /** @var \Drupal\user\UserInterface $user */
  $user = $membership->getEntity();

  // Get the image URL.
  /** @var \Drupal\media\MediaInterface|null $media_picture */
  $media_picture = $user->get('field_media')->referencedEntities();
  /** @var \Drupal\file\Entity\File|null $file */
  $file = $media_picture ? File::load(
    $media_picture[0]->get('oe_media_image')->target_id
  ) : NULL;
  $image_item = $file ? ImageValueObject::fromStyledImageItem(
    $media_picture[0]->get('oe_media_image')->first(),
    'crop_80x80'
  ) : NULL;
  $file_url = $image_item ? $image_item->getSource() : NULL;

  // Last activity.
  $last_activity_ago = eic_community_get_teaser_time_display(
    $user->get('changed')->value
  );

  // User roles.
  $user_role = NULL;
  /** @var \Drupal\group\Entity\GroupRoleInterface $role */
  foreach ($membership->get('group_roles')->referencedEntities() as $role) {
    // Strip out the group type from the role ID.
    // E.g. organisation-owner => owner.
    $role_name = substr($role->id(), strlen($role->getGroupTypeId()) + 1);

    // We want to show only one role, starting from the most important.
    if ($role_name == 'owner') {
      $user_role = $role->label();
    }
    elseif ($role_name == 'admin') {
      $user_role = $role->label();
    }
  }

  // Job titles.
  $job_titles = [];
  /** @var \Drupal\taxonomy\TermInterface $term */
  foreach ($membership->get('field_vocab_job_title')->referencedEntities() as $term) {
    $job_titles[] = $term->label();
  }

  $variables['member_data'] = [
    'icon_file_path' => $variables['eic_icon_path'],
    'image' => [
      'src' => $file_url,
    ],
    'path' => $user->toUrl(),
    'title' => $user->getDisplayName(),
    'job_titles' => implode(', ', $job_titles),
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

  // If we have a specific role, show it here.
  if (!empty($user_role)) {
    $variables['member_data']['status'] = [
      'label' => $user_role,
      'icon' => [
        'type' => 'custom',
        'name' => 'star_circle',
      ],
    ];
  }
}
