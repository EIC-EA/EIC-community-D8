<?php

namespace Drupal\eic_content\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_content\ContentMetricPluginManager;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field to display content metrics.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("content_metrics")
 */
class ContentMetrics extends NumericField {

  /**
   * The EIC Content metric plugin manager.
   *
   * @var \Drupal\eic_content\ContentMetricPluginManager
   */
  protected $contentMetricPluginManager;

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
   * @param \Drupal\eic_content\ContentMetricPluginManager $content_metric_plugin_manager
   *   The EIC Content metric plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContentMetricPluginManager $content_metric_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contentMetricPluginManager = $content_metric_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;

    // Store the metrics info for later use.
    foreach ($this->contentMetricPluginManager->getDefinitions() as $definition) {
      $this->metricsInfo[$definition['id']] = $this->contentMetricPluginManager->createInstance($definition['id']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.content_metric'),
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
    /** @var \Drupal\eic_group_statistics\GroupMetricInterface $plugin */
    foreach ($this->metricsInfo as $plugin) {
      if (empty($plugin->getConfigDefinition())) {
        continue;
      }

      // Unfortunately we need to set a default value for the container.
      // Metrics providers should not use this reserved name.
      $options[$plugin->id() . '_conf']['default'] = NULL;
      foreach ($plugin->getConfigDefinition() as $form_element_name => $info) {

        $options[$plugin->id() . '_conf'][$form_element_name] = ['default' => $info['default_value']];
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [];
    /** @var \Drupal\eic_group_statistics\GroupMetricInterface $plugin */
    foreach ($this->metricsInfo as $plugin) {
      $options[$plugin->id()] = $plugin->label();
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
    /** @var \Drupal\eic_group_statistics\GroupMetricInterface $plugin */
    foreach ($this->metricsInfo as $plugin) {

      // Get the configuration.
      $values = $this->options[$plugin->id() . '_conf'] ?? [];
      $configuration = $plugin->getConfig($values);

      // Create a container for this metric.
      $form[$plugin->id() . '_conf'] = [
        '#type' => 'fieldset',
        '#title' => $plugin->label(),
        '#tree' => TRUE,
      ];

      // Add the form elements to the container.
      foreach ($configuration as $form_element_name => $form_element) {
        $form[$plugin->id() . '_conf'][$form_element_name] = $form_element;
      }

      if (empty($configuration)) {
        $form[$plugin->id() . '_conf']['empty_configuration'] = [
          '#type' => 'markup',
          '#markup' => $this->t('This metric does not provide configuration.'),
        ];
      }

      // Add a condition to show the configuration.
      $form[$plugin->id() . '_conf']['#states'] = [
        'visible' => [
          ':input[name="options[metric]"]' => [
            ['value' => $plugin->id()],
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

    if (empty($this->metricsInfo[$metric_id])) {
      return 0;
    }

    $plugin = $this->metricsInfo[$metric_id];

    $conf = $this->options[$metric_id . '_conf'] ?? [];
    return $plugin->getValue($group, $conf);
  }

}
