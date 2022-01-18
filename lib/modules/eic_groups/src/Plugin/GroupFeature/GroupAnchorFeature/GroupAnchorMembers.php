<?php

namespace Drupal\eic_groups\Plugin\GroupFeature\GroupAnchorFeature;

use Drupal\eic_groups\Plugin\GroupFeature\GroupMembers;

/**
 * Group feature plugin implementation for Members.
 *
 * @GroupFeature(
 *   id = "eic_groups_anchor_members",
 *   label = @Translation("Team"),
 *   description = @Translation("Group members features.")
 * )
 */
class GroupAnchorMembers extends GroupMembers {

  /**
   * {@inheritdoc}
   */
  const ANCHOR_ID = 'members';

}
