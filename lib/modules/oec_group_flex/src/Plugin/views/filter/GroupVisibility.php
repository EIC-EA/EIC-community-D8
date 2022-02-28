<?php

namespace Drupal\oec_group_flex\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group_flex\Plugin\GroupVisibilityManager;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a filter for group visibility.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("group_visibility")
 */
class GroupVisibility extends FilterPluginBase {

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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [];
    $default_values = [];

    // Build the checkbox options and default values.
    foreach ($this->groupVisibilityManager->getAllAsArrayForGroup() as $key => $plugin) {
      $options[$key] = $plugin->getLabel();
      if (!empty($this->options['visibility'][$key])) {
        $default_values[] = $key;
      }
    }

    $form['visibility'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Filter by group visibility'),
      '#options' => $options,
      '#default_value' => $default_values,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    // Drupal's FAPI system automatically puts '0' in for any checkbox that
    // was not set, and the key to the checkbox if it is set.
    // Unfortunately, this means that if the key to that checkbox is 0,
    // we are unable to tell if that checkbox was set or not.

    // Luckily, the '#value' on the checkboxes form actually contains
    // *only* a list of checkboxes that were set, and we can use that
    // instead.

    $form_state->setValue(['options', 'value'], $form['value']['#value']);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    // Creates new configuration for views join plugin. This will join the
    // group visibility table with groups field data table in order to filter
    // the query by the selected group visibilities.
    $definition = [
      'table' => 'oec_group_visibility',
      'field' => 'gid',
      'left_table' => 'groups_field_data_group_content_field_data',
      'left_field' => 'id',
    ];
    // Instantiates the join plugin.
    $join = Views::pluginManager('join')->createInstance('standard', $definition);
    // Adds the join relationship to the query.
    $query->addRelationship('group_visibility', $join, 'oec_group_visibility');
    // Grabs the group visibilities from the selected options.
    $options = array_filter($this->options['visibility'], function ($value, $key) {
      return $value != FALSE;
    }, ARRAY_FILTER_USE_BOTH);
    // Filters out the query by the selected group visibilities.
    $query->addWhere($this->options['group'], 'group_visibility.type', array_values($options), 'IN');
  }

}
