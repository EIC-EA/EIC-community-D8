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

    if (
      !$state->get(MigrateToolsOverrideCommands::STATE_MIGRATIONS_MESSAGES_IS_RUNNING) &&
      $state->get(MigrateToolsOverrideCommands::STATE_MIGRATIONS_IS_RUNNING)
    ) {
      return;
    }

    // If we are running migrations we do not allow Messages creations except for the Message migration itself.
    $this->context->buildViolation($constraint->migrationRunning)->addViolation();
  }

}
