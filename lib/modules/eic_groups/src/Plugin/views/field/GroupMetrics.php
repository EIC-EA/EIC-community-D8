<?php

namespace Drupal\eic_groups\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field to display group metrics.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_metrics")
 */
class GroupMetrics extends NumericField {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The metrics info returned by all the invoked modules.
   *
   * @var array
   */
  protected $metricsInfo = [];

  /**
   * Constructs a GroupMetrics object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;

    // Store the metrics info for later use.
    $this->metricsInfo = \Drupal::moduleHandler()->invokeAll('eic_groups_metrics_info');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    // @todo Append selected metric?
    return $this->t('Group metrics');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['metric'] = ['default' => ''];

    // Define options for all metrics.
    foreach ($this->metricsInfo as $metric_id => $info) {
      if (empty($info['options'])) {
        continue;
      }

      foreach ($info['options'] as $form_element_name => $info) {
        // Unfortunately we need to set a default value for the container.
        // Metrics providers should not use this reserved name.
        $options[$metric_id . '_conf']['default'] = NULL;

        $options[$metric_id . '_conf'][$form_element_name] = ['default' => $info['default_value']];
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->metricsInfo as $metric_id => $info) {
      $options[$metric_id] = $info['label'];
    }
    $form['metric'] = [
      '#title' => $this->t('Select the metric to be displayed'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->options['metric'],
      '#required' => TRUE,
    ];

    // Add potential configuration fo each metric.
    // @todo Use ajax to load the metric config form elements to avoid loading
    //   them all.
    foreach ($this->metricsInfo as $metric_id => $info) {
      if (empty($info['options'])) {
        continue;
      }

      // Skip this metric if it doesn't have any configuration.
      if (empty($info['conf_callback']) || !is_callable($info['conf_callback'])) {
        continue;
      }

      // Get the configuration.
      $configuration = call_user_func($info['conf_callback'], $metric_id, $this->options);

      // Create a container for this metric.
      $form[$metric_id . '_conf'] = [
        '#type' => 'fieldset',
        '#title' => $info['label'],
        '#tree' => TRUE,
      ];

      // Add the form elements to the container.
      foreach ($configuration as $form_element_name => $form_element) {
        $form[$metric_id . '_conf'][$form_element_name] = $form_element;
      }

      // Add a condition to show the configuration.
      $form[$metric_id . '_conf']['#states'] = [
        'visible' => [
          ':input[name="options[metric]"]' => [
            ['value' => $metric_id],
          ],
        ],
      ];
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $group = $values->_entity;
    $metric_id = $this->options['metric'];

    // Check if we have a proper value_callback.
    if (empty($this->metricsInfo[$metric_id]['value_callback']) || !is_callable($this->metricsInfo[$metric_id]['value_callback'])) {
      return 0;
    }

    return call_user_func($this->metricsInfo[$metric_id]['value_callback'], $metric_id, $group, $this->options);
  }

}
