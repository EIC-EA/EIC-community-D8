<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides a 'sensitive' group visibility.
 *
 * @GroupVisibility(
 *  id = "sensitive",
 *  label = @Translation("Sensitive (the group contains sensitive data and can be accessed only by group members and users with 'sensitive' access rights)"),
 *  weight = -89
 * )
 */
class SensitiveVisibility extends PrivateVisibility {

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t("Sensitive (The group contains sensitive data and can be accessed only by group members and users with 'sensitive' access rights)");
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('This means the group will be visible to users allowed to see sensitive content on the platform.');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    $permissions = parent::getGroupPermissions($group);

    $group_ids_to_remove = [
      'group-a416e6833',
      'group-bf4b46c3a',
      'group-eca6128ca',
      'group-outsider',
    ];

    foreach ($group_ids_to_remove as $group_id) {
      if (!array_key_exists($group_id, $permissions)) {
        continue;
      }

      $permissions[$group_id] = [];
    }
    $mappedPerm = [];

    foreach ($group_ids_to_remove as $group_id) {
      foreach ($permissions as $permission) {
        $permission = array_merge($permission, ['view any unpublished group']);
        foreach ($permission as $perm) {
          $mappedPerm[$group_id][$perm] = FALSE;
        }
      }
    }

    $this->saveMappedPerm($mappedPerm, $group->getGroupType());

    return $permissions;
  }

}
