<?php

namespace Drupal\oec_group_flex;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\GroupFlexGroup;
use Drupal\group_flex\GroupFlexGroupType;
use Drupal\group_flex\Plugin\GroupVisibilityInterface;
use Drupal\group_permissions\GroupPermissionsManager;

/**
 * Get the group flex settings from a group.
 */
class OECGroupFlexGroupDecorator extends GroupFlexGroup {

  use DependencySerializationTrait;

  /**
   * The flex group inner service.
   *
   * @var \Drupal\group_flex\GroupFlexGroup
   */
  protected $groupFlexGroup;

  /**
   * The OEC module configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $oecGroupFlexConfigSettings;

  /**
   * The group visibility storage service.
   *
   * @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface
   */
  protected $groupVisibilityStorage;

  /**
   * Constructs a new OECGroupFlexGroupDecorator.
   *
   * @param \Drupal\group_flex\GroupFlexGroup $groupFlexGroup
   *   The flex group inner service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage
   *   The group visibility storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManager $groupPermManager
   *   The group permissions manager.
   * @param \Drupal\group_flex\GroupFlexGroupType $flexGroupType
   *   The group type flex.
   */
  public function __construct(GroupFlexGroup $groupFlexGroup, ConfigFactoryInterface $configFactory, GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage, EntityTypeManagerInterface $entityTypeManager, GroupPermissionsManager $groupPermManager, GroupFlexGroupType $flexGroupType) {
    parent::__construct($entityTypeManager, $groupPermManager, $flexGroupType);
    $this->groupFlexGroup = $groupFlexGroup;
    $this->oecGroupFlexConfigSettings = $configFactory->get('oec_group_flex.settings');
    $this->groupVisibilityStorage = $groupVisibilityStorage;
  }

  /**
   * Get the group visibility for a given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to return the default value for.
   *
   * @return string
   *   The group visibility.
   */
  public function getGroupVisibility(GroupInterface $group): string {
    if (!($group_visibility_record = $this->groupVisibilityStorage->load($group->id()))) {
      return GroupVisibilityInterface::GROUP_FLEX_TYPE_VIS_PUBLIC;
    }
    return $group_visibility_record->getType();
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array([$this->groupFlexGroup, $method], $args);
  }

}
