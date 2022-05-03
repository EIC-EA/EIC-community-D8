<?php

namespace Drupal\eic_groups\Plugin\Validation\Constraint;

use Drupal\eic_groups\EICGroupsHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Group membership constraint.
 */
class GroupMembershipConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    // We only validate group_content entities.
    if ($entity->getEntityTypeId() !== 'group_content') {
      return;
    }

    // We skip group_content entities that are not group_membership.
    if ($entity->getGroupContentType()->getContentPluginId() !== 'group_membership') {
      return;
    }

    $membership_roles = array_column($entity->group_roles->getValue(), 'target_id');

    if (!in_array(EICGroupsHelper::GROUP_OWNER_ROLE, $membership_roles)) {
      return;
    }

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $entity->getGroup();

    $group_owners = $group->getMembers([EICGroupsHelper::GROUP_OWNER_ROLE]);

    // The group doesn't have any owner and therefore this group membership is
    // valid.
    if (empty($group_owners)) {
      return;
    }

    foreach ($group_owners as $group_owner) {
      // It's the same owner so we can exit.
      if ($group_owner->getGroupContent()->id() === $entity->id()) {
        break;
      }

      // Group cannot have multiple owners an so we add a violation.
      $this->context->buildViolation($constraint->multipleOwners)
        ->atPath('group_roles')
        ->addViolation();
      return;
    }
  }

}
