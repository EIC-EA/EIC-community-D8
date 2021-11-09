<?php

/**
 * @file
 * Contains common helper functions.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Markup;

/**
 * Returns the build of the social share block.
 *
 * @return array|bool
 *   The build array of the block or FALSE if not found.
 */
function _eic_community_get_social_share_block() {
  $social_share_json = [
    'service' => 'share',
    'popup' => TRUE,
    'selection' => TRUE,
    'to' => [
      'more',
      'twitter',
      'facebook',
      'linkedin',
      'e-mail',
    ],
    'stats' => TRUE,
  ];

  return [
    '#theme' => 'oe_webtools_social_share',
    '#title' => NULL,
    '#icons_json' => Markup::create(Json::encode($social_share_json)),
    '#attached' => ['library' => ['oe_webtools/drupal.webtools-smartloader']],
  ];
}

/**
 * Gets the access to flag an entity.
 *
 * @param string $flag_id
 *   The flag ID.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The flaggable entity.
 *
 * @return bool
 *   TRUE if the user can flag the entity.
 */
function _eic_community_get_flag_access($flag_id, EntityInterface $entity) {
  /** @var \Drupal\flag\FlagService $flag_service */
  $flag_service = \Drupal::service('flag');

  // Load flag by ID.
  $flag = $flag_service->getFlagById($flag_id);

  // If flag does not exist, we skip it.
  if (!$flag) {
    return TRUE;
  }

  // Check if we have a flagging for this user and entity. If we have one we
  // check if the user can unflag, otherwise we check if the user can flag.
  $user_flag = $flag_service->getFlagging($flag, $entity);
  $action = $user_flag ? 'unflag' : 'flag';

  return $flag->actionAccess($action);
}
