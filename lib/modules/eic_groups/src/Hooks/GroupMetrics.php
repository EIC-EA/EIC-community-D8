<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagHelper;
use Drupal\eic_group_statistics\GroupStatisticsHelperInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_media_statistics\EntityFileDownloadCount;
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
   * The EIC Flags helper service.
   *
   * @var \Drupal\eic_flags\FlagHelper
   */
  protected $flagHelper;

  /**
   * The EIC group statistics helper service.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsHelperInterface
   */
  protected $groupStatisticsHelper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC entity file download count service.
   *
   * @var \Drupal\eic_media_statistics\EntityFileDownloadCount
   */
  protected $entityFileDownloadCount;

  /**
   * Constructs a new GroupTokens object.
   *
   * @param \Drupal\eic_groups\EICGroupsHelper $eic_groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\eic_flags\FlagHelper $eic_flag_helper
   *   The EIC Flags helper service.
   * @param \Drupal\eic_group_statistics\GroupStatisticsHelperInterface $group_statistics_helper
   *   The EIC group statistics helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\eic_media_statistics\EntityFileDownloadCount $entity_file_download_count
   *   The EIC entity file download count service.
   */
  public function __construct(
    EICGroupsHelper $eic_groups_helper,
    FlagHelper $eic_flag_helper,
    GroupStatisticsHelperInterface $group_statistics_helper,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFileDownloadCount $entity_file_download_count
  ) {
    $this->groupsHelper = $eic_groups_helper;
    $this->flagHelper = $eic_flag_helper;
    $this->groupStatisticsHelper = $group_statistics_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFileDownloadCount = $entity_file_download_count;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_groups.helper'),
      $container->get('eic_flags.helper'),
      $container->get('eic_group_statistics.helper'),
      $container->get('entity_type.manager'),
      $container->get('eic_media_statistics.entity_file_download_count')
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
      'eic_groups_comments' => [
        'label' => $this->t('Group comments'),
        'value_callback' => 'eic_groups_eic_groups_metrics_value',
      ],
      'eic_groups_flags' => [
        'label' => $this->t('Group flags'),
        'value_callback' => 'eic_groups_eic_groups_metrics_value',
        'conf_callback' => 'eic_groups_eic_groups_metrics_configuration',
        'options' => [
          'flag_ids' => [
            'default_value' => [],
          ],
        ],
      ],
      'eic_groups_downloads' => [
        'label' => $this->t('Group downloads'),
        'value_callback' => 'eic_groups_eic_groups_metrics_value',
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
        $selected_roles = $this->getSelectedOptions($configuration['roles']);
        $selected_roles = empty($selected_roles) ? NULL : $selected_roles;
        return count($group->getMembers($selected_roles));

      case 'eic_groups_content':
        $selected_node_types = $this->getSelectedOptions($configuration['node_types']);
        $count = 0;
        foreach ($selected_node_types as $node_type) {
          if ($this->groupsHelper->isGroupTypePluginEnabled($group->getGroupType(), 'group_node', $node_type)) {
            $count += count($group->getContentEntities("group_node:$node_type"));
          }
        }
        return $count;

      case 'eic_groups_comments':
        return $this->groupStatisticsHelper->loadGroupStatistics($group)->getCommentsCount();

      case 'eic_groups_flags':
        $count = 0;
        $selected_flags = $this->getSelectedOptions($configuration['flags']);
        $group_flag_counts = $this->flagHelper->getFlaggingsCountPerGroup($group, TRUE);
        foreach ($selected_flags as $flag_id) {
          foreach ($group_flag_counts as $results) {
            if (!empty($results[$flag_id])) {
              $count += $results[$flag_id];
            }
          }
        }
        return $count;

      case 'eic_groups_downloads':
        $count = 0;
        foreach ($this->groupsHelper->getGroupNodes($group) as $node) {
          $count += $this->entityFileDownloadCount->getFileDownloads($node);
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

      case 'eic_groups_flags':
        // Get the existing flags.
        $flags = [];
        /** @var \Drupal\flag\Entity\Flag $flag */
        foreach ($this->entityTypeManager->getStorage('flag')->loadMultiple() as $flag) {
          $flags[$flag->id()] = $flag->label();
        }
        $conf = [
          'flags' => [
            '#title' => $this->t('Select the flag(s) to filter on'),
            '#description' => $this->t('If none selected, all flags will be returned.'),
            '#type' => 'checkboxes',
            '#options' => $flags,
            '#default_value' => $configuration[$metric_id . '_conf']['flags'] ?? [],
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
