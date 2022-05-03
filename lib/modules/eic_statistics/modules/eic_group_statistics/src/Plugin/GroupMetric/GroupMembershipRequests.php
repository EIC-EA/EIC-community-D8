<?php

namespace Drupal\eic_group_statistics\Plugin\GroupMetric;

use Drupal\eic_group_statistics\GroupMetricPluginBase;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;

/**
 * Group metric plugin implementation for group membership requests.
 *
 * @GroupMetric(
 *   id = "eic_groups_membership_requests",
 *   label = @Translation("Group membership requests"),
 *   description = @Translation("Provides a counter for group membership requests.")
 * )
 */
class GroupMembershipRequests extends GroupMetricPluginBase {

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
      GroupMembershipRequest::REQUEST_NEW => $this->t('New'),
      GroupMembershipRequest::REQUEST_PENDING => $this->t('Pending'),
      GroupMembershipRequest::REQUEST_APPROVED => $this->t('Approved'),
      GroupMembershipRequest::REQUEST_REJECTED => $this->t('Rejected'),
    ];

    return [
      'status' => [
        '#title' => $this->t('Request status'),
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
    // Check first if membership requests are enabled for this group type.
    if (!$this->groupsHelper->isGroupTypePluginEnabled($group->getGroupType(), 'group_membership_request')) {
      return NULL;
    }

    $selected_statuses = $this->getSelectedOptions($configuration['status']);
    $filters = [];
    if (!empty($selected_statuses)) {
      $filters['grequest_status'] = $selected_statuses;
    }
    return count($group->getContentEntities("group_membership_request", $filters));
  }

}
