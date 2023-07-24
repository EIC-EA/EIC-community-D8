<?php

namespace Drupal\oec_group_flex\Plugin\views\filter;

use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a filter for group visibility.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("group_visibility")
 */
class GroupVisibility extends InOperator {

  /**
   * The Group visibility manager.
   *
   * @var \Drupal\group_flex\Plugin\GroupVisibilityManager
   */
  protected $groupVisibilityManager;

  /**
   * Constructs a GroupVisibility object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group_flex\Plugin\GroupVisibilityManager $group_visibility_manager
   *   The Group visibility manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GroupVisibilityManager $group_visibility_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupVisibilityManager = $group_visibility_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.group_visibility')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = $this->t('Visibility');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['visibility'] = [];

    // By default all visibility options are disabled.
    foreach ($this->groupVisibilityManager->getAllAsArrayForGroup() as $key => $plugin) {
      $options['visibility'][$key] = FALSE;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    // Build the checkbox options and default values.
    foreach ($this->groupVisibilityManager->getAllAsArrayForGroup() as $key => $plugin) {
      $this->valueOptions[$key] = $plugin->getLabel();
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    // Creates new configuration for views join plugin. This will join the
    // group visibility table with groups_field_data table in order to filter
    // the query by the selected group visibilities.
    $definition = [
      'table' => 'oec_group_visibility',
      'field' => 'gid',
      'left_table' => 'groups_field_data',
      'left_field' => 'id',
    ];
    // Instantiates the join plugin.
    $join = Views::pluginManager('join')->createInstance('standard', $definition);
    // Adds the join relationship to the query.
    $query->addRelationship('oec_group_visibility', $join, 'oec_group_visibility');
    // Filters out the query by the selected group visibilities.
    $query->addWhere($this->options['group'], 'oec_group_visibility.type', array_values($this->value), 'IN');
  }

}
