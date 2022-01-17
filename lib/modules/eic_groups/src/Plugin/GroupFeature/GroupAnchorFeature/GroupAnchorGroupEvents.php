<?php

namespace Drupal\eic_groups\Plugin\GroupFeature\GroupAnchorFeature;

use Drupal\eic_groups\Plugin\GroupFeature\GroupGroupEvents;

/**
 * Group feature plugin implementation for Discussions.
 *
 * @GroupFeature(
 *   id = "eic_groups_anchor_group_events",
 *   label = @Translation("Events"),
 *   description = @Translation("Group events features.")
 * )
 */
class GroupAnchorGroupEvents extends GroupGroupEvents {

  /**
   * {@inheritdoc}
   */
  const ANCHOR_ID = 'events';

}
