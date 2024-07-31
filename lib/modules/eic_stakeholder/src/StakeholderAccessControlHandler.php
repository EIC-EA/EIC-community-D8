<?php

declare(strict_types=1);

namespace Drupal\eic_stakeholder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the stakeholder entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class StakeholderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view stakeholder'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit stakeholder'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete stakeholder'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete stakeholder revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view stakeholder revision', 'view stakeholder']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert stakeholder revision', 'edit stakeholder']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create stakeholder', 'administer stakeholder'], 'OR');
  }

}
