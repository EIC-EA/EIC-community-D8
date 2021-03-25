<?php

/**
 * @file
 * Functions to support theming.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

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
