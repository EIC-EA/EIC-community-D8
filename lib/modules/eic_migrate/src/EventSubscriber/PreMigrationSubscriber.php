<?php

namespace Drupal\eic_migrate\EventSubscriber;

use Drupal\eic_projects\MigrationCordisProject;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Perform post migration tasks.
 *
 * @package Drupal\eic_migrate
 */
class PreMigrationSubscriber implements EventSubscriberInterface {

  /**
   *
   * @var \Drupal\eic_projects\MigrationCordisProject
   */
  protected $migrationCordisProject;

  /**
   * Constructs a new MessageCreatorBase object.
   *
   * @param \Drupal\eic_projects\MigrationCordisProject $migrationCordisProject
   */
  public function __construct(
    MigrationCordisProject     $migrationCordisProject,
  ) {
    $this->migrationCordisProject = $migrationCordisProject;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [];
  }

}
