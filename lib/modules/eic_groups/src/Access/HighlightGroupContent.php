<?php

namespace Drupal\eic_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Class FlagGroupContentAccessCheck
 *
 * @package Drupal\eic_groups\Access
 */
class HighlightGroupContent implements AccessInterface {

  const SUPPORTED_PLUGIN_IDS = [
    'group-group_node-document',
    'group-group_node-discussion',
  ];

  /**
   * @param \Drupal\Core\Session\AccountProxy $account
   * @param \Drupal\group\Entity\GroupInterface|null $group
   * @param \Drupal\node\NodeInterface|null $node
   *
   * @return \Drupal\Core\Access\AccessResultInterface
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
      return AccessResult::forbidden('highlight group content permission is required');
    }

    $group_content = $group->getContentEntities(NULL, [
      'type' => self::SUPPORTED_PLUGIN_IDS,
      'entity_id' => $node->id(),
    ]);

    if (empty($group_content)) {
      return AccessResult::forbidden('Invalid group content argument');
    }

    return AccessResult::allowed();
  }

}
