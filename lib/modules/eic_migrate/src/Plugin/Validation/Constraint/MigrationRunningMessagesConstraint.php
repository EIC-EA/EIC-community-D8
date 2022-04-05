<?php

namespace Drupal\eic_groups\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a Message constraint during migration.
 *
 * @Constraint(
 *   id = "migration_running_messages",
 *   label = @Translation("Migration running messages", context = "Validation"),
 * )
 */
class MigrationRunningMessagesConstraint extends Constraint {

  /**
   * The message will be shown if migrations is running and Message is created by an event.
   *
   * @var string
   */
  public $migrationRunning = 'Migrations for messages is running.';

}
