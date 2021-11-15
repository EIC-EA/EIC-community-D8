<?php

/**
 * @file
 * Contains common helper functions.
 */

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Returns the build of the social share block.
 *
 * @return array|bool
 *   The build array of the block or FALSE if not found.
 */
function _eic_community_get_social_share_block() {
  $share_block = \Drupal::service('plugin.manager.block')
    ->createInstance('social_share', []);

  if (!$share_block instanceof BlockPluginInterface) {
    return FALSE;
  }

  // Return the build array.
  $build = $share_block->build();
  $build['#title'] = t('Share');
  return $build;
}

/**
 * @param \Drupal\group\Entity\GroupInterface $group
 * @param \Drupal\node\NodeInterface $node
 *
 * @return array
 */
function _eic_community_get_share_group_content_link(
  GroupInterface $group,
  NodeInterface $node
) {
  static $formatted_groups;
  if (empty($formatted_groups)) {
    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = \Drupal::service('eic_groups.helper')
      ->getGroupsByVisibility(GroupVisibilityType::GROUP_VISIBILITY_PUBLIC);
    foreach ($groups as $group) {
      $formatted_groups[] = [
        'id' => $group->id(),
        'name' => $group->label(),
      ];
    }
  }

  return [
    '#theme' => 'eic_share_content_link',
    '#groups' => $formatted_groups,
    '#endpoint' => Url::fromRoute('eic_share_content.share', [
      'group' => $group->id(),
      'node' => $node->id(),
    ], ['absolute' => TRUE])->toString(),
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
 * @return bool|\Drupal\Core\Access\AccessResult
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
