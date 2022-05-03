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
   * Route of the members overview.
   *
   * @var string
   */
  const PRIMARY_OVERVIEW_ROUTE = 'eic_overviews.groups.overview_page.team';

  /**
   * {@inheritdoc}
   */
  const ANCHOR_ID = 'members';

}
