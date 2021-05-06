<?php

namespace Drupal\oec_group_features\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines group_feature annotation object.
 *
 * @Annotation
 */
class GroupFeature extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
