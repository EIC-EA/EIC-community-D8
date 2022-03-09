<?php

namespace Drupal\eic_group_statistics\Plugin\GroupMetric;

use Drupal\eic_group_statistics\GroupMetricPluginBase;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group invitations.
 *
 * @GroupMetric(
 *   id = "eic_groups_invitations",
 *   label = @Translation("Group invitations"),
 *   description = @Translation("Provides a counter for group invitations.")
 * )
 */
class GroupInvitations extends GroupMetricPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getConfigDefinition(): array {
    return [
      'status' => [
        'default_value' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(array $values = []): array {
    $statuses = [
      $this->mapAlphabeticalInvitationStatus(GroupInvitation::INVITATION_PENDING) => $this->t('Pending'),
      $this->mapAlphabeticalInvitationStatus(GroupInvitation::INVITATION_ACCEPTED) => $this->t('Accepted'),
      $this->mapAlphabeticalInvitationStatus(GroupInvitation::INVITATION_REJECTED) => $this->t('Rejected'),
    ];

    return [
      'status' => [
        '#title' => $this->t('Invitation status'),
        '#description' => $this->t('If none selected, all statuses will be returned.'),
        '#type' => 'checkboxes',
        '#options' => $statuses,
        '#default_value' => $values['status'] ?? [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(GroupInterface $group, array $configuration = []) {
    // Check first if invitations are enabled for this group type.
    // @todo Should we check against the group itself instead of group type?
    if (!$this->groupsHelper->isGroupTypePluginEnabled($group->getGroupType(), 'group_invitation')) {
      return NULL;
    }

    $selected_statuses = $this->getSelectedOptions($configuration['status']);
    $filters = [];
    if (!empty($selected_statuses)) {
      foreach ($selected_statuses as $selected_status) {
        $filters['invitation_status'][] = $this->mapAlphabeticalInvitationStatus($selected_status, FALSE);
      }
    }

    return count($group->getContentEntities("group_invitation", $filters));
  }

  /**
   * Map the given invitation status between numeric key and alphabetical key.
   *
   * This is to avoid conflict with the 'pending' status which has 0 as a value.
   * It doesn't play well with select boxes which defines 0 as non-selected.
   *
   * @param string $status
   *   The status to checked.
   * @param bool $is_original_value
   *   Whether the status to check is the original value.
   *
   * @return false|int|string
   *   The mapped status of FALSE if not found.
   */
  protected function mapAlphabeticalInvitationStatus(string $status, bool $is_original_value = TRUE) {
    $mapping = [
      GroupInvitation::INVITATION_PENDING => 'pending',
      GroupInvitation::INVITATION_ACCEPTED => 'accepted',
      GroupInvitation::INVITATION_REJECTED => 'rejected',
    ];

    // If we are looking for the original value.
    if ($is_original_value) {
      return ($mapping[$status] ?? FALSE);
    }

    // Otherwise we return the alphabetical value.
    return array_search($status, $mapping);
  }

}
