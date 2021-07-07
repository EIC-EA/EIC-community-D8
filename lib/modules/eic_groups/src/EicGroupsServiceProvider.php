<?php

namespace Drupal\eic_groups;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the EIC Groups module.
 */
class EicGroupsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Modifies access_check.group_content.create_entity service class so that
    // it uses our custom GroupContentCreateEntityAccessCheck class for extra
    // access conditions.
    $container->getDefinition('access_check.group_content.create_entity')
      ->setClass('Drupal\eic_groups\Access\GroupContentCreateEntityAccessCheck');
  }

}
