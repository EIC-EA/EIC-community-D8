<?php

namespace Drupal\eic_groups\Plugin\GroupFeature\GroupAnchorFeature;

use Drupal\eic_groups\Plugin\GroupFeature\GroupNews;

/**
 * Group feature plugin implementation for Innovations anchor.
 *
 * @GroupFeature(
 *   id = "eic_groups_anchor_innovations",
 *   label = @Translation("Innovations"),
 *   description = @Translation("Group innovations anchor features.")
 * )
 */
class GroupAnchorInnovations extends GroupNews {

  /**
   * {@inheritdoc}
   */
  const ANCHOR_ID = 'innovations';

}
