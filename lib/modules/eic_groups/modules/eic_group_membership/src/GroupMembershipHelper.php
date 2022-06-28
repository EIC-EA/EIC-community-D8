<?php

namespace Drupal\eic_group_membership;

/**
 * Service providing helper functions for group memberships.
 */
class GroupMembershipHelper {

  /**
   * Checks if from ID corresponds to the group membership leave form.
   *
   * @param string $form_id
   *   The form ID to be checked.
   * @param string $group_type
   *   The group type ID if you want to check against a specific group type.
   *
   * @return bool
   *   Returns TRUE if this is the group membership leave form.
   */
  public static function isGroupMembershipLeaveForm(string $form_id, string $group_type = NULL) {
    if (!empty($group_type)) {
      return $form_id === "group_content_$group_type-group_membership_$group_type-leave_form";
    }

    $regex = '/^group_content_[a-z]+-group_membership_[a-z]+-leave_form/i';
    return (bool) preg_match($regex, $form_id, $matches);
  }

  /**
   * Checks if from ID corresponds to the group membership delete form.
   *
   * @param string $form_id
   *   The form ID to be checked.
   * @param string $group_type
   *   The group type ID if you want to check against a specific group type.
   *
   * @return bool
   *   Returns TRUE if this is the group membership delete form.
   */
  public static function isGroupMembershipDeleteForm(string $form_id, string $group_type = NULL) {
    if (!empty($group_type)) {
      return $form_id === "group_content_$group_type-group_membership_delete_form";
    }

    $regex = '/^group_content_[a-z]+-group_membership_delete_form/i';
    return preg_match($regex, $form_id, $matches);
  }

}
