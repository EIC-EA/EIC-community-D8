<?php

namespace Drupal\eic_migrate\Plugin\Validation\Constraint;

use Drupal\eic_migrate\Commands\MigrateToolsOverrideCommands;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class MigrationRunningMessagesValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $state = \Drupal::state();

    // Constraint only for Message.
    if ('message' !== $entity->getEntityTypeId()) {
      return;
    }

    // If no migrations running, just ignore that constraint.
    if (!$state->get(MigrateToolsOverrideCommands::STATE_MIGRATIONS_IS_RUNNING)) {
      return;
    }

    // If Message migration is running, ignore that constraint.
    if (
      $state->get(MigrateToolsOverrideCommands::STATE_MIGRATIONS_MESSAGES_IS_RUNNING)
    ) {
      return;
    }

    // Add violation when migrations is running and entity tries to generate Message.
    $this->context->buildViolation($constraint->migrationRunning)->addViolation();
  }

}
