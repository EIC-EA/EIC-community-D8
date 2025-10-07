<?php

namespace Drupal\eic_dashboards\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class OverviewAccess {

  public function access(AccountInterface $account) {
    $allowed_roles = [
      'sensitive',
      'content_administrator',
      'site_admin',
      'administrator',
    ];
    $user_roles = $account->getRoles(TRUE);
    return AccessResult::allowedIf(!empty(array_intersect($allowed_roles, $user_roles)));

  }
}
