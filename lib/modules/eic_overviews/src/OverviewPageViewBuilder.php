<?php

namespace Drupal\eic_overviews;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for an overview page entity type.
 */
class OverviewPageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The overview page has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
