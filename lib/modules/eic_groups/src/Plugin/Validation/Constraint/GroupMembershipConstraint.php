<?php

namespace Drupal\eic_groups\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a Group membership constraint.
 *
 * @Constraint(
 *   id = "EICGroupMembership",
 *   label = @Translation("Group membership", context = "Validation"),
 * )
 */
class GroupMembershipConstraint extends Constraint {

  /**
   * The message that will be shown if group already has a owner.
   *
   * @var string
   */
  public $multipleOwners = 'A group cannot have multiple owners.';

}
