<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Provides an access checker for highlighting group content.
 *
 * @package Drupal\eic_groups\Access
 */
class HighlightGroupContentAccessCheck implements AccessInterface {

  const SUPPORTED_PLUGIN_IDS = [
    'group_node:document',
    'group_node:discussion',
    'group_node:video',
    'group_node:gallery',
  ];

  /**
   * Constructs a new HighlightGroupContentAccessCheck object.
   *
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The current user account.
   * @param \Drupal\group\Entity\GroupInterface|null $group
   *   The group entity.
   * @param \Drupal\node\NodeInterface|null $node
   *   The group content node.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(
    AccountProxy $account,
    GroupInterface $group = NULL,
    NodeInterface $node = NULL
  ) {
    if (!$group || !$node) {
      return AccessResult::forbidden();
    }

    $account = $account->getAccount();
    if (!$group->hasPermission('highlight group content', $account)) {
      return AccessResult::forbidden('highlight group content permission is required')
        ->setCacheMaxAge(0);
    }

    $supported_plugins = [];
    foreach (self::SUPPORTED_PLUGIN_IDS as $plugin_id) {
      if (!$group->getGroupType()->hasContentPlugin($plugin_id)) {
        continue;
      }
      $supported_plugins[] = $group->getGroupType()
        ->getContentPlugin($plugin_id)
        ->getContentTypeConfigId();
    }

    if (empty($supported_plugins)) {
      return AccessResult::forbidden('Content not supported')
        ->setCacheMaxAge(0);
    }

    $group_content = $group->getContentEntities(NULL, [
      'type' => $supported_plugins,
      'entity_id' => $node->id(),
    ]);

    if (empty($group_content)) {
      return AccessResult::forbidden('Invalid group content argument')
        ->setCacheMaxAge(0);
    }

    return AccessResult::allowed()
      ->setCacheMaxAge(0);
  }

}
