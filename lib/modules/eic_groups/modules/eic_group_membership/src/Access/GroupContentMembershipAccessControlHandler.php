<?php

namespace Drupal\eic_group_membership\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentAccessControlHandler;

/**
 * Overrides group content access control handler for group_membership plugin.
 */
class GroupContentMembershipAccessControlHandler extends GroupContentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function relationAccess(GroupContentInterface $group_content, $operation, AccountInterface $account, $return_as_object = FALSE) {
    // Check if we are dealing with an update of a membership.
    if ($group_content->getContentPlugin()->getPluginId() == 'group_membership') {
      if ($operation == 'update') {
        if ($group_content->getGroup()->hasPermission('edit memberships', $account)) {
          $result = GroupAccessResult::allowed();
          return $return_as_object ? $result : $result->isAllowed();
        }
      }
    }

    // Otherwise let group module handle the access.
    return parent::relationAccess($group_content, $operation, $account, $return_as_object);
  }

}
