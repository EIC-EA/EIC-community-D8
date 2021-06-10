<?php

namespace Drupal\eic_groups;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_flex\GroupFlexGroup;
use Drupal\group_flex\Plugin\GroupJoiningMethodManager;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\oec_group_flex\GroupVisibilityRecord;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager;
use Drupal\node\NodeInterface;

/**
 * EICGroupsHelper service that provides helper functions for groups.
 */
class EICGroupsHelper implements EICGroupsHelperInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * Constructs a new EventsHelperService object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\group_flex\GroupFlexGroup $group_flex_group
   *   The group_flex group service.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $group_visibility_manager
   *   The group visibility manager service.
   * @param \Drupal\group_flex\Plugin\GroupJoiningMethodManager $group_joining_method_manager
   *   The group joining method manager service.
   * @param \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager $custom_restricted_visibility_manager
   *   The group joining method manager service.
   */
  public function __construct(RouteMatchInterface $route_match, ModuleHandlerInterface $module_handler, GroupFlexGroup $group_flex_group, GroupVisibilityManager $group_visibility_manager, GroupJoiningMethodManager $group_joining_method_manager, CustomRestrictedVisibilityManager $custom_restricted_visibility_manager) {
    $this->routeMatch = $route_match;
    $this->moduleHandler = $module_handler;
    $this->groupFlexGroup = $group_flex_group;
    $this->groupVisibilityManager = $group_visibility_manager;
    $this->groupJoiningMethodManager = $group_joining_method_manager;
    $this->customRestrictedVisibilityManager = $custom_restricted_visibility_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupFromRoute() {
    $entity = FALSE;
    $parameters = $this->routeMatch->getParameters()->all();
    if (!empty($parameters['group']) && is_numeric($parameters['group'])) {
      $group = Group::load($parameters['group']);
      return $group;
    }
    if (!empty($parameters)) {
      foreach ($parameters as $parameter) {
        if ($parameter instanceof EntityInterface) {
          $entity = $parameter;
          break;
        }
      }
    }
    if ($entity) {
      return $this->getGroupByEntity($entity);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupByEntity(EntityInterface $entity) {
    $group = FALSE;
    if ($entity instanceof GroupInterface) {
      return $entity;
    }
    elseif ($entity instanceof NodeInterface) {
      // Load all the group content for this entity.
      $group_content = GroupContent::loadByEntity($entity);
      // Assuming that the content can be related only to 1 group.
      $group_content = reset($group_content);
      if (!empty($group_content)) {
        $group = $group_content->getGroup();
      }
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperationLinks(GroupInterface $group, $limit_entities = [], CacheableMetadata $cacheable_metadata = NULL) {
    $operation_links = [];

    if (!is_null($cacheable_metadata)) {
      // Retrieve the operations from the installed content plugins and merges
      // cacheable metadata.
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        if (!empty($limit_entities) && !in_array($plugin->getEntityTypeId(), $limit_entities)) {
          continue;
        }

        $operation_links += $plugin->getGroupOperations($group);
        $cacheable_metadata = $cacheable_metadata->merge($plugin->getGroupOperationsCacheableMetadata());
      }
    }
    else {
      // Retrieve the operations from the installed content plugins without
      // merging cacheable metadata.
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        if (!empty($limit_entities) && !in_array($plugin->getEntityTypeId(), $limit_entities)) {
          continue;
        }

        $operation_links += $plugin->getGroupOperations($group);
      }
    }

    if ($operation_links) {
      // Allow modules to alter the collection of gathered links.
      $this->moduleHandler->alter('group_operations', $operation_links, $group);

      // Sort the operations by weight.
      uasort($operation_links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    }

    return $operation_links;
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

    $group_visibility = $this->groupFlexGroup->getGroupVisibility($group);
    /** @var \Drupal\group_flex\Plugin\GroupVisibilityInterface $selected_plugin */
    $selected_plugin = $visibility_plugins[$group_visibility];
    $visibility_settings['label'] = $selected_plugin->getLabel();

    $visibility_settings['plugin_id'] = $group_visibility;
    switch ($group_visibility) {
      case 'custom_restricted':
        $visibility_settings['settings'] = $selected_plugin->getVisibilitySettings($group);
        break;

    }

    return $visibility_settings;
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
        $settings[] = $joining_method_plugins[$joining_method]->getLabel();
      }
    }
    return $settings;
  }

}
