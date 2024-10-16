<?php

namespace Drupal\oec_group_flex;

use Drupal\Core\Session\AccountInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\GroupFlexGroup;
use Drupal\group_flex\Plugin\GroupJoiningMethodManager;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager;

/**
 * OECGroupFlexHelper service that provides module helper functions.
 */
class OECGroupFlexHelper {

  const GROUP_TYPE_OWNER_ROLE = 'owner';

  const GROUP_TYPE_ADMINISTRATOR_ROLE = 'admin';

  const GROUP_TYPE_MEMBER_ROLE = 'member';

  /**
   * The group_flex group service.
   *
   * @var \Drupal\group_flex\GroupFlexGroup
   */
  protected $groupFlexGroup;

  /**
   * The group visibility manager service.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  protected $groupVisibilityManager;

  /**
   * The custom restricted visibility manager service.
   *
   * @var \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager
   */
  protected $customRestrictedVisibilityManager;

  /**
   * The group joining method manager service.
   *
   * @var \Drupal\group_flex\Plugin\GroupJoiningMethodManager
   */
  protected $groupJoiningMethodManager;

  /**
   * The group visibility storage service.
   *
   * @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface
   */
  protected $groupVisibilityStorage;

  /**
   * Constructs a new OECGroupFlexHelper object.
   *
   * @param \Drupal\group_flex\GroupFlexGroup $group_flex_group
   *   The group_flex group service.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $group_visibility_manager
   *   The group visibility manager service.
   * @param \Drupal\group_flex\Plugin\GroupJoiningMethodManager $group_joining_method_manager
   *   The group joining method manager service.
   * @param \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager $custom_restricted_visibility_manager
   *   The group joining method manager service.
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $group_visibility_storage
   *   The group visibility storage service.
   */
  public function __construct(GroupFlexGroup $group_flex_group, GroupVisibilityManager $group_visibility_manager, GroupJoiningMethodManager $group_joining_method_manager, CustomRestrictedVisibilityManager $custom_restricted_visibility_manager, GroupVisibilityDatabaseStorageInterface $group_visibility_storage) {
    $this->groupFlexGroup = $group_flex_group;
    $this->groupVisibilityManager = $group_visibility_manager;
    $this->groupJoiningMethodManager = $group_joining_method_manager;
    $this->customRestrictedVisibilityManager = $custom_restricted_visibility_manager;
    $this->groupVisibilityStorage = $group_visibility_storage;
  }

  /**
   * Returns an array containing the visibility settings for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which we return visibility settings.
   *
   * @return array
   *   An array containing:
   *   - plugin_id: the plugin ID of the selected visibility.
   *   - label: the plugin label.
   *   - settings (optional): object of type
   *     Drupal\oec_group_flex\GroupVisibilityRecord (currently only for
   *     CustomRestrictedVisibility).
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroupVisibilitySettings(GroupInterface $group) {
    $visibility_plugins = $this->groupVisibilityManager->getAllAsArray();
    $visibility_settings = [
      'plugin_id' => '',
      'label' => '',
      'settings' => '',
    ];

    $group_visibility_type = $this->groupFlexGroup->getGroupVisibility($group);
    /** @var \Drupal\group_flex\Plugin\GroupVisibilityInterface $selected_plugin */
    $selected_plugin = $visibility_plugins[$group_visibility_type];
    $visibility_settings['label'] = $selected_plugin->getValueDescription($group->getGroupType());

    $visibility_settings['plugin_id'] = $group_visibility_type;
    switch ($group_visibility_type) {
      case 'custom_restricted':
        $visibility_settings['settings'] = $this->groupVisibilityStorage->load($group->id());
        break;

    }

    return $visibility_settings;
  }

  /**
   * Returns the short label for visibility for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return string
   *   The visibility label.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroupVisibilityTagLabel(GroupInterface $group): string {
    $group_visibility = $this->getGroupVisibilitySettings($group);
    return $this->getVisibilityTagLabel($group_visibility['plugin_id']);
  }

  /**
   * Returns the short label for a visibility plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return string
   *   The visibility label.
   */
  public function getVisibilityTagLabel(string $plugin_id): string {
    $tag = strpos($plugin_id, '_') !== FALSE ? strstr($plugin_id, '_', TRUE) : $plugin_id;

    // Exception for the custom visibility. We need to show the label
    // "Restricted" instead.
    if ($plugin_id === 'custom_restricted') {
      $tag = 'restricted';
    }

    return ucfirst($tag);
  }

  /**
   * Returns a human-readable array for the given group visibility record.
   *
   * @param \Drupal\oec_group_flex\GroupVisibilityRecord $visibility_record
   *   The Group visibility record.
   *
   * @return array
   *   An array containing:
   *   - plugin_id: the plugin ID as key.
   *     - label: Label of the plugin ID.
   *     - options: the options of the plugin. Currently can be any type of
   *       data.
   */
  public function getGroupVisibilityRecordSettings(GroupVisibilityRecord $visibility_record) {
    $restricted_visibility_plugins = $this->customRestrictedVisibilityManager->getAllAsArray();
    $settings = [];

    switch ($visibility_record->getType()) {
      case 'custom_restricted':
        foreach ($visibility_record->getOptions() as $plugin_id => $item) {
          if (empty($restricted_visibility_plugins[$plugin_id])) {
            continue;
          }

          /** @var \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityInterface $selected_plugin */
          $selected_plugin = $restricted_visibility_plugins[$plugin_id];
          $settings[$plugin_id]['label'] = $selected_plugin->getLabel();
          if (isset($item["{$plugin_id}_conf"])) {
            $settings[$plugin_id]['options'] = $item["{$plugin_id}_conf"];
          }

        }
        break;
    }
    return $settings;
  }

  /**
   * Returns an array containing the joining method for the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity for which we return joining method.
   *
   * @return array
   *   An array containing the labels of the enabled joining methods.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getGroupJoiningMethod(GroupInterface $group) {
    $joining_method_plugins = $this->groupJoiningMethodManager->getAllAsArray();
    $joining_methods = $this->groupFlexGroup->getDefaultJoiningMethods($group);
    $settings = [];
    foreach ($joining_methods as $joining_method) {
      if (isset($joining_method_plugins[$joining_method])) {
        $settings[] = [
          'plugin_id' => $joining_method,
          'label' => $joining_method_plugins[$joining_method]->getLabel(),
        ];
      }
    }
    return $settings;
  }

  /**
   * Returns the correct role machine name for the given group type and role.
   *
   * @param string $group_type
   *   The group type ID.
   * @param string $role
   *   The role to check. Can be either "admin", "owner" or "member".
   *
   * @return string|null
   *   The group role machine name or NULL if not found.
   */
  public static function getGroupTypeRole(string $group_type, string $role) {
    return in_array($role, [
      self::GROUP_TYPE_ADMINISTRATOR_ROLE,
      self::GROUP_TYPE_OWNER_ROLE,
      self::GROUP_TYPE_MEMBER_ROLE,
    ]) ? $group_type . '-' . $role : NULL;
  }

  /**
   * Get membership request.
   *
   * @param \Drupal\user\UserInterface $user
   *   User.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Group content.
   */
  public function getMembershipRequest(AccountInterface $user, GroupInterface $group) {
    // If no responsible group content types were found, we return nothing.
    $group_content_type_storage = \Drupal::entityTypeManager()->getStorage('group_content_type');
    $group_content_types = $group_content_type_storage->loadByContentPluginId('group_membership_request');
    if (!empty($group_content_types)) {
      $group_content_storage = \Drupal::entityTypeManager()->getStorage('group_content');
      $group_content_items = $group_content_storage->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $user->id(),
        'gid' => $group->id(),
        GroupMembershipRequest::STATUS_FIELD => [
          GroupMembershipRequest::REQUEST_PENDING,
          GroupMembershipRequest::REQUEST_APPROVED,
        ],
      ]);

      if (!empty($group_content_items)) {
        return reset($group_content_items);
      }
    }

    return NULL;
  }

}
