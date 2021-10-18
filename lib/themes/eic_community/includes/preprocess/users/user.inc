<?php

/**
 * @file
 * Functions to support theming.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\eic_community\ValueObject\ImageValueObject;
use Drupal\user\Entity\User;

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
 */
function eic_community_get_teaser_user_display(User $user) {
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
