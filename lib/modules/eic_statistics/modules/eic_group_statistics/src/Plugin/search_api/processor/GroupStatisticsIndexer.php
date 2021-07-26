<?php

namespace Drupal\eic_group_statistics\Plugin\search_api\processor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_group_statistics\GroupStatisticsStorageInterface;
use Drupal\eic_group_statistics\GroupStatisticTypes;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Item\ItemInterface;

/**
 * Search API Processor for indexing group statistics.
 *
 * @SearchApiProcessor(
 *   id = "group_statistics_indexer",
 *   label = @Translation("Group statistics indexing"),
 *   description = @Translation("Switching on will enable indexing group
 *   statistics"), stages = {
 *     "add_properties" = 1,
 *     "pre_index_save" = -10
 *   }
 * )
 */
class GroupStatisticsIndexer extends ProcessorPluginBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Group statistics storage.
   *
   * @var \Drupal\eic_group_statistics\GroupStatisticsStorageInterface
   */
  protected $groupStatisticsStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_group_statistics.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, GroupStatisticsStorageInterface $group_statistics_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupStatisticsStorage = $group_statistics_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'group_statistics_index' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() === 'group') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get group statistic types.
   *
   * @return array
   *   Array containing the type of group statistics group.
   */
  public function getGroupStatisticTypes() {
    return GroupStatisticTypes::getOptionsArray();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];

    $statistic_types = $this->getGroupStatisticTypes();
    foreach ($statistic_types as $statistic_type => $statistic_label) {
      $options[$statistic_type] = $statistic_label;
    }

    $form['group_statistics_index'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable these group statistics on this index'),
      '#description' => $this->t('This will index group statistics for every group'),
      '#options' => $options,
      '#default_value' => isset($this->configuration['group_statistics_index']) ? $this->configuration['group_statistics_index'] : [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValues()['group_statistics_index']);
    if ($fields) {
      $fields = array_keys($fields);
    }
    $form_state->setValue('group_statistics_index', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      // Ensure that our fields are defined.
      $fields = $this->getFieldsDefinition();

      foreach ($fields as $field_id => $field_definition) {
        $properties[$field_id] = new ProcessorProperty($field_definition);
      }
    }
    return $properties;
  }

  /**
   * Helper function for defining our group statistic fields.
   */
  protected function getFieldsDefinition() {
    $config = $this->configuration['group_statistics_index'];
    $fields = [];
    foreach ($config as $statistic_type) {
      $label = $this->getGroupStatisticTypes()[$statistic_type];
      $fields['group_statistic_' . $statistic_type] = [
        'label' => $label,
        'description' => $label,
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $config = $this->configuration['group_statistics_index'];
    $group_statistics = $this->getGroupStatisticTypes();

    try {
      $entity = $item->getOriginalObject()->getValue();

      if ($entity->getEntityTypeId() !== 'group') {
        return;
      }

      $group_statistics = $this->groupStatisticsStorage->load($entity);

      foreach ($config as $statistic_type) {
        $fields = $this
          ->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'group_statistic_' . $statistic_type);

        foreach ($fields as $group_statistic_field) {
          switch (str_replace('group_statistic_', '', $group_statistic_field->getFieldIdentifier())) {
            case GroupStatisticTypes::STAT_TYPE_MEMBERS:
              // Adds number of group members to the indexed field.
              $group_statistic_field->addValue($group_statistics->getMembersCount());
              break;

            case GroupStatisticTypes::STAT_TYPE_COMMENTS:
              // Adds number of group comments to the indexed field.
              $group_statistic_field->addValue($group_statistics->getCommentsCount());
              break;

            case GroupStatisticTypes::STAT_TYPE_FILES:
              // Adds number of group files to the indexed field.
              $group_statistic_field->addValue($group_statistics->getFilesCount());
              break;

            case GroupStatisticTypes::STAT_TYPE_EVENTS:
              // Adds number of group events to the indexed field.
              $group_statistic_field->addValue($group_statistics->getEventsCount());
              break;

          }
        }
      }
    }
    catch (SearchApiException $exception) {
      $this->logger->error($exception->getMessage());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    foreach ($this->getFieldsDefinition() as $field_id => $field_definition) {
      try {
        $this->ensureField(NULL, $field_id, $field_definition['type']);
      }
      catch (SearchApiException $exception) {
        $this->logger->error($exception->getMessage());
      }
    }
  }

}
