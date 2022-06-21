<?php

namespace Drupal\eic_migrate\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MigrationRunningMessagesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    // Constraint only for Message.
    if ('message' !== $entity->getEntityTypeId()) {
      return;
    }

    // If no migrations running, just ignore that constraint.
    if (!eic_migrate_is_migration_running()) {
      return;
    }

    // If Message migration is running, ignore that constraint.
    if (eic_migrate_is_migration_messages_running()) {
      return;
    }

    // Add violation when migrations is running and entity tries to generate
    // Message.
    $this->context->buildViolation($constraint->migrationRunning)->addViolation();
  }

}
