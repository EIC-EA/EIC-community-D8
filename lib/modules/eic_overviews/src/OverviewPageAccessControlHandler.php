<?php

namespace Drupal\eic_overviews;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the overview page entity type.
 */
class OverviewPageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // Deny access if page is disabled.
        if (!$entity->isEnabled()) {
          return AccessResult::forbidden();
        }

        return AccessResult::allowedIfHasPermission($account, 'view overview pages');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, [
          'edit overview pages',
          'administer overview pages',
        ], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, [
          'delete overview pages',
          'administer overview pages',
        ], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(
    AccountInterface $account,
    array $context,
    $entity_bundle = NULL
  ) {
    return AccessResult::allowedIfHasPermissions($account, [
      'create overview pages',
      'administer overview pages',
    ], 'OR');
  }

}
