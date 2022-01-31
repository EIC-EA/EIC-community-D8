<?php

namespace Drupal\oec_group_features;

use Drupal\group\Entity\GroupInterface;

/**
 * Interface for group_feature plugins.
 */
interface GroupFeatureInterface {

  /**
   * Defines the overview url behind an anchor feature item.
   */
  const QUERY_PARAMETER_OVERVIEW_URL = 'overview-url';

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Performs needed actions when feature is being enabled.
   */
  public function enable(GroupInterface $group);

  /**
   * Performs needed actions when feature is being disabled.
   */
  public function disable(GroupInterface $group);

}
