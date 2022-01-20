<?php

namespace Drupal\eic_group_statistics\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines group_metric annotation object.
 *
 * @Annotation
 */
class GroupMetric extends Plugin {

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
