<?php

namespace Drupal\oec_group_feature;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for a group feature entity type.
 */
class GroupFeatureViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The group feature has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
