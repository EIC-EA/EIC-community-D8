<?php

namespace Drupal\oec_group_features\Hooks;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\Group;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
use Drupal\oec_group_features\GroupFeatureHelper;
use Drupal\oec_group_features\GroupFeaturePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupFeatures.
 *
 * Implementations for group features related hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The OEC group feature helper service.
   *
   * @var \Drupal\oec_group_features\GroupFeatureHelper
   */
  protected $groupFeatureHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Group feature plugin manager.
   *
   * @var \Drupal\oec_group_features\GroupFeaturePluginManager
   */
  protected $groupFeaturePluginManager;

  /**
   * Constructs a new GroupFeatures object.
   *
   * @param \Drupal\oec_group_features\GroupFeatureHelper $oec_group_features_helper
   *   The OEC group feature helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permissions_manager
   *   The group permissions manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\oec_group_features\GroupFeaturePluginManager $group_feature_plugin_manager
   *   The current route match service.
   */
  public function __construct(
    GroupFeatureHelper $oec_group_features_helper,
    EntityTypeManagerInterface $entity_type_manager,
    GroupPermissionsManagerInterface $group_permissions_manager,
    RouteMatchInterface $route_match,
    GroupFeaturePluginManager $group_feature_plugin_manager
  ) {
    $this->groupFeatureHelper = $oec_group_features_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupPermissionsManager = $group_permissions_manager;
    $this->routeMatch = $route_match;
    $this->groupFeaturePluginManager = $group_feature_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oec_group_features.helper'),
      $container->get('entity_type.manager'),
      $container->get('group_permission.group_permissions_manager'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.group_feature')
    );
  }

  /**
   * Reacts on Group entity insert.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity being created.
   */
  public function groupInsert(Group $group) {
    $this->manageFeatures($group);

    // Make sure group permission entity is created.
    $groupPermission = $this->groupFeatureHelper->getGroupPermissionObject($group);
    if ($groupPermission->isNew()) {
      $groupPermission->setValidationRequired(FALSE);
      $groupPermission->save();
    }
  }

  /**
   * Reacts on Group entity update.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity being updated.
   */
  public function groupUpdate(Group $group) {
    $this->manageFeatures($group);
  }

  /**
   * Enables and disables group features based on the selection.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity.
   */
  protected function manageFeatures(Group $group) {
    // Check if feature values have been changed to avoid performing unnecessary
    // actions.
    if (!empty($group->original) && $group->get(GroupFeatureHelper::FEATURES_FIELD_NAME)->getValue() == $group->original->get(GroupFeatureHelper::FEATURES_FIELD_NAME)->getValue()) {
      return;
    }

    // Get all available features for this group type.
    $available_features = [];
    foreach ($this->groupFeatureHelper->getGroupTypeAvailableFeatures($group->getGroupType()->id()) as $plugin_id => $label) {
      try {
        $available_features[$plugin_id] = $this->groupFeaturePluginManager->createInstance($plugin_id);
      }
      catch (PluginException $e) {
        $logger = $this->getLogger('oec_group_features');
        $logger->error($e->getMessage());
      }
    }

    // Get group enabled features.
    $enabled_features = [];
    foreach ($group->get(GroupFeatureHelper::FEATURES_FIELD_NAME)->getValue() as $feature) {
      $enabled_features[$feature['value']] = $feature['value'];
    }

    // Enable features that are selected for this group.
    foreach (array_intersect_key($available_features, $enabled_features) as $feature_key => $enabled_feature) {
      $available_features[$feature_key]->enable($group);
    }

    // Disable features that are not selected for this group.
    foreach (array_diff_key($available_features, $enabled_features) as $feature_key => $disabled_feature) {
      $available_features[$feature_key]->disable($group);
    }

  }

  /**
   * Returns whether the current PHP process runs on CLI.
   *
   * @return bool
   *   TRUE if CLI, else FALSE.
   */
  protected function isCli(): bool {
    return PHP_SAPI === 'cli';
  }

}
