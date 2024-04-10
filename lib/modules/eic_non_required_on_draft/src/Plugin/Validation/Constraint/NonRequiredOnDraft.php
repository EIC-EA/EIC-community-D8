<?php

namespace Drupal\eic_non_required_on_draft\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks fields that are non required on draft.
 *
 * Throws an error if a user tries to publish a non draft entity WITHOUT
 * filling in a required field.
 *
 * @Constraint(
 *   id = "non_required_on_draft",
 *   label = @Translation("Non required on Draft", context = "Validation")
 * )
 */
class NonRequiredOnDraft extends Constraint {

  /**
   * Required when in non draft state UI string.
   *
   * @var string
   */
  public string $message = '@field_label field is required in non draft state.';

}
