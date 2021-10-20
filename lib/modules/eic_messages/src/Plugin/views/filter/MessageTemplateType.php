<?php

namespace Drupal\eic_messages\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by message template type.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("message_template_type")
 */
class MessageTemplateType extends FilterPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TaxonomyIndexTid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
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
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#title' => $this->t('Message template type'),
      '#type' => 'checkboxes',
      '#options' => MessageTemplateTypes::getOptionsArray(),
      '#default_value' => is_array($this->value) ? $this->value : [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // We can't query config through a standard SQL query, this is why we need
    // to filter on message template ID.
    // Get the selected message types.
    $types = [];
    $message_templates = [];
    if (is_array($this->value)) {
      $types = $this->getSelectedValues();
    }
    if (!empty($types)) {
      $message_templates = $this->getMessageTemplates($types);
    }

    // Only add condition if we have message templates to filter on.
    if (!empty($message_templates)) {
      $this->query->addWhere(0, 'message_field_data.template', $message_templates, 'IN');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Helper function to returns operators.
   *
   * @return array[]
   *   And array of possible operators as follows:
   *   - key: operator key
   *     - title: The title of the operator.
   *     - short: The short version of the title.
   *     - short_single: The symbol repsrensting the operator.
   */
  public function operators() {
    $operators = [
      'in' => [
        'title' => $this->t('Is one of'),
        'short' => $this->t('in'),
        'short_single' => $this->t('='),
      ],
      'not in' => [
        'title' => $this->t('Is not one of'),
        'short' => $this->t('not in'),
        'short_single' => $this->t('<>'),
      ],
    ];

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (is_array($this->value)) {
      // Get human-readable label for each item.
      $values = $this->getSelectedValues();
      foreach ($values as $id => $machine_name) {
        $values[$id] = MessageTemplateTypes::getOptionsArray()[$machine_name];
      }
      return $this->operators()[$this->operator]['short_single'] . ' ' . implode(', ', $values);
    }

    if (isset($this->operators()[$this->operator])) {
      return $this->operators()[$this->operator]['short_single'] . ' ' . $this->value;
    }
    return $this->value;
  }

  /**
   * Returns the list of message templates of the given types.
   *
   * @param array $message_template_types
   *   The list of message template types for which we want to get the message
   *   templates.
   *
   * @return array|int
   *   The list of message templates by key.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMessageTemplates(array $message_template_types = []) {
    $query = $this->entityTypeManager->getStorage('message_template')->getQuery();

    $operator = NULL;
    if (!empty($message_template_types)) {
      switch ($this->operator) {
        case 'in':
          $operator = 'IN';
          break;

        case 'not in':
          $operator = 'NOT IN';
          break;
      }
      if ($operator) {
        $query->condition('third_party_settings.eic_messages.message_template_type', $message_template_types, $operator);
      }
    }

    return $query->execute();
  }

  /**
   * Returns the selected message template types based on the value property.
   *
   * @return array
   *   And array of message template ids.
   */
  protected function getSelectedValues() {
    $result = [];
    if (is_array($this->value)) {
      foreach ($this->value as $key => $value) {
        if ($key === $value) {
          $result[] = $key;
        }
      }
      return $result;
    }
    return $result;
  }

}
