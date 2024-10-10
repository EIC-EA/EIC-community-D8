<?php

namespace Drupal\eic_groups\Plugin\GroupFeature\GroupAnchorFeature;

use Drupal\eic_groups\Plugin\GroupFeature\GroupNews;

/**
 * Group feature plugin implementation for Horizon platform result anchor.
 *
 * @GroupFeature(
 *   id = "eic_groups_anchor_horizon_platform_result",
 *   label = @Translation("Horizon platform result"),
 *   description = @Translation("Group horizon platform result anchor features.")
 * )
 */
class GroupAnchorHorizonResult extends GroupNews {

  /**
   * {@inheritdoc}
   */
  const ANCHOR_ID = 'horizon-results';

}
