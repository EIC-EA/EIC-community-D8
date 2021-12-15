<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides metrics around group entities.
 */
class GroupMetrics implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The EIC Groups helper service.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(
    EICGroupsHelper $eic_groups_helper,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->groupsHelper = $eic_groups_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_groups.helper'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Implements hook_eic_groups_metrics_info().
   */
  public function groupMetricsInfo():array {
    return [
      'eic_groups_group_members' => [
        'label' => $this->t('Group members'),
        'value_callback' => 'eic_groups_eic_groups_metrics_value',
        'conf_callback' => 'eic_groups_eic_groups_metrics_configuration',
        'options' => [
          'roles' => [
            'default_value' => [],
          ],
        ],
      ],
      'eic_groups_content' => [
        'label' => $this->t('Group content'),
        'value_callback' => 'eic_groups_eic_groups_metrics_value',
        'conf_callback' => 'eic_groups_eic_groups_metrics_configuration',
        'options' => [
          'content_types' => [
            'default_value' => [],
          ],
        ],
      ],
    ];
  }

  /**
   * Returns the metric value for the given group.
   *
   * @param string $metric_id
   *   The ID of the metric.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which we get the metric.
   * @param array $configuration
   *   The views plugin configuration.
   *
   * @return int|null
   *   The value for the metric or NULL if not applicable.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function groupMetricsValue(string $metric_id, GroupInterface $group, array $configuration = []) {
    switch ($metric_id) {
      case 'eic_groups_group_members':
        $selected_roles = $this->getSelectedOptions($configuration[$metric_id . '_conf']['roles']);
        $selected_roles = empty($selected_roles) ? NULL : $selected_roles;
        return count($group->getMembers($selected_roles));

      case 'eic_groups_content':
        $selected_node_types = $this->getSelectedOptions($configuration[$metric_id . '_conf']['node_types']);
        $count = 0;
        foreach ($selected_node_types as $node_type) {
          if ($this->groupsHelper->isGroupTypePluginEnabled($group->getGroupType(), 'group_node', $node_type)) {
            $count += count($group->getContentEntities("group_node:$node_type"));
          }
        }
        return $count;

    }

    return NULL;
  }

  /**
   * Returns the configuration for the given metric.
   *
   * @param string $metric_id
   *   Machine name of the metric.
   * @param array $configuration
   *   The views plugin configuration.
   *
   * @return array
   *   An array of form elements.
   */
  public function groupMetricsConf(string $metric_id, array $configuration = []):array {
    $conf = [];
    switch ($metric_id) {
      case 'eic_groups_group_members':
        // Get roles.
        $roles = [];
        foreach ($this->groupsHelper->getGroupRoles() as $info) {
          foreach ($info['roles'] as $role_id => $role_label) {
            $roles[$role_id] = $info['label'] . ' - ' . $role_label;
          }
        }
        $conf = [
          'roles' => [
            '#title' => $this->t('Select the role(s) to filter on'),
            '#description' => $this->t('If none selected, all roles will be returned.'),
            '#type' => 'checkboxes',
            '#options' => $roles,
            '#default_value' => $configuration[$metric_id . '_conf']['roles'] ?? [],
          ],
        ];
        break;

      case 'eic_groups_content':
        // Get the existing node types.
        $node_types = [];
        /** @var \Drupal\node\Entity\NodeType $node_type */
        foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $node_type) {
          $node_types[$node_type->id()] = $node_type->label();
        }
        $conf = [
          'node_types' => [
            '#title' => $this->t('Select the content type(s) to filter on'),
            '#description' => $this->t('If none selected, all content types will be returned.'),
            '#type' => 'checkboxes',
            '#options' => $node_types,
            '#default_value' => $configuration[$metric_id . '_conf']['node_types'] ?? [],
          ],
        ];
        break;

    }

    return $conf;
  }

  /**
   * Returns the selected options based on the form element submitted values.
   *
   * @param array $selection
   *   The submitted values returned by the form element.
   *
   * @return array
   *   An array of selected options.
   */
  protected function getSelectedOptions(array $selection) {
    $selected_options = [];
    foreach ($selection as $key => $value) {
      if ($key === $value) {
        $selected_options[] = $key;
      }
    }
    return $selected_options;
  }

}
