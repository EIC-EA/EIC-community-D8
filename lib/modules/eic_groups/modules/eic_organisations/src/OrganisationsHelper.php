<?php

namespace Drupal\eic_organisations;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_organisations\Constants\Organisations;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoader;

/**
 * OrganisationsHelper service that provides helper functions for organisations.
 */
class OrganisationsHelper {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoader
   */
  protected $groupMembershipLoader;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new OrganisationsHelper.
   *
   * @param \Drupal\group\GroupMembershipLoader $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(GroupMembershipLoader $group_membership_loader, AccountInterface $account) {
    $this->groupMembershipLoader = $group_membership_loader;
    $this->currentUser = $account;
  }

  /**
   * Returns the organisations a user belongs to.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check. If null, defaults to the current user.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   An array of group objects.
   */
  public function getUserOrganisations($account = NULL) {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    $organisations = [];

    // Get all user group memberships and check for organisations only.
    foreach ($this->groupMembershipLoader->loadByUser($account) as $membership) {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $membership->getGroup();

      // We only check organisations, if this is another bundle, just skip it.
      if ($group->bundle() == Organisations::GROUP_ORGANISATION_BUNDLE) {
        $organisations[] = $group;
      }
    }

    return $organisations;
  }

  /**
   * Returns the types of organisations a user belongs to.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check. If null, defaults to the current user.
   *
   * @return array
   *   An array of term IDs.
   */
  public function getUserOrganisationTypes(AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    $user_organisation_types = [];

    // Search for a matching organisation the user belongs to.
    foreach ($this->getUserOrganisations($account) as $group) {

      // We need to have proper values to check, otherwise skip this
      // organisation.
      if (!$group->hasField(Organisations::FIELD_ORGANISATION_TYPE)) {
        continue;
      }

      $organisation_types = array_column($group->get(Organisations::FIELD_ORGANISATION_TYPE)->getValue(), 'target_id');
      $user_organisation_types = array_merge($user_organisation_types, $organisation_types);
    }

    return array_unique($user_organisation_types);
  }

  public static function setRequiredFieldsDefaultValues(GroupInterface &$group) {
    $fields = [
      'field_body' => [
        'value' => ' ',
        //'format' => 'filtered_html',  // @todo Check if mandatory and why it doesn't work.
      ],
    ];

    foreach ($fields as $field_name => $values) {
      // Check if field exists.
      if (!$group->hasField($field_name)) {
        continue;
      }

      // Check if field is empty.
      if (!$group->get($field_name)->isEmpty()) {
        continue;
      }

      // Set the values for this field.
      foreach ($values as $key => $value) {
        $group->{$field_name}->{$key} = $value;
      }
    }
  }

}
