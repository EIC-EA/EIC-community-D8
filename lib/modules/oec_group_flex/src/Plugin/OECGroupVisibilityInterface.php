<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\group_flex\Plugin\GroupVisibilityInterface;

/**
 * Extends GroupVisibilityInterface interface for Group visibility plugins.
 */
interface OECGroupVisibilityInterface extends GroupVisibilityInterface {

  /**
   * The string to use for group flex type 'restricted' visibility.
   */
  const GROUP_FLEX_TYPE_VIS_RESTRICTED = 'restricted';

}
