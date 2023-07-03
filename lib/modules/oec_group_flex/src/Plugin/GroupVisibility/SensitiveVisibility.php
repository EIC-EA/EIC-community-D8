<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides a 'sensitive' group visibility.
 *
 * @GroupVisibility(
 *  id = "sensitive",
 *  label = @Translation("Sensitive members only"),
 *  weight = -89
 * )
 */
class SensitiveVisibility extends PrivateVisibility {

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Sensitive members only');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('This means the group will be visible to users allowed to see sensitive content on the platform.');
  }

}
