<?php

namespace Drupal\eic_groups\Plugin\GroupFeature\GroupAnchorFeature;

use Drupal\eic_groups\Plugin\GroupFeature\GroupNews;

/**
 * Group feature plugin implementation for News anchor.
 *
 * @GroupFeature(
 *   id = "eic_groups_anchor_news",
 *   label = @Translation("News"),
 *   description = @Translation("Group news anchor features.")
 * )
 */
class GroupAnchorNews extends GroupNews {

  /**
   * {@inheritdoc}
   */
  const ANCHOR_ID = 'news';

}
