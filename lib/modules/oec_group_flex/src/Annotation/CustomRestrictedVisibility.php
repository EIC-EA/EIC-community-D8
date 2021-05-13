<?php

namespace Drupal\oec_group_flex\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Custom restricted visibility item annotation object.
 *
 * @see \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager
 * @see plugin_api
 *
 * @Annotation
 */
class CustomRestrictedVisibility extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight = 0;

}
