<?php

namespace Drupal\eic_migrate;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\eic_migrate\Commands\MigrateToolsOverrideCommands;
use Symfony\Component\DependencyInjection\Reference;

class EicMigrateServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('migrate_tools.commands')) {
      $definition = $container->getDefinition('migrate_tools.commands');
      $definition->setClass(MigrateToolsOverrideCommands::class)
        ->addArgument(new Reference('state'));
    }
  }
}
