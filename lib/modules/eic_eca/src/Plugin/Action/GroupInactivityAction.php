<?php

namespace Drupal\eic_eca\Plugin\Action;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Describes the eic_eca group_inactivity_action action.
 *
 * @Action(
 *   id = "eic_eca_group_inactivity_action",
 *   label = @Translation("group inactivity action"),
 *   description = @Translation(""),
 *   eca_version_introduced = "1.0.0",
 *   type = "entity"
 *   )
 */
class GroupInactivityAction extends ConfigurableActionBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConnection($container->get('database'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($group = NULL): void {
    $flag_id = 'group_inactive';
    $duration = (int) $this->configuration['inactivity_duration'];
    $previous_duration = (int) $this->configuration['previous_duration'];
    if (!$this->configuration['check_previous_scenario']) {
      $items = (int) $this->configuration['items'];
      $timestamp_inactivity = strtotime("-$duration months");

      $query = $this->entityTypeManager->getStorage('search_api_index')
        ->load('global')->query();

      // Change the parse mode for the search.
      $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')
        ->createInstance('direct');
      $parse_mode->setConjunction('OR');
      $query->setParseMode($parse_mode);
      $query->addCondition('search_api_datasource', 'entity:group')
        ->addCondition('group_type', 'group')
        ->addCondition('group_changed', $timestamp_inactivity, '<');
      $query->range(0, $items);
      $query->sort('group_changed', QueryInterface::SORT_DESC);

      // Execute the search.
      $results = $query->execute();
      $solr_gids = [];
      foreach ($results as $result) {
        $solr_gids[] = $result->getField('group_id_integer')->getValues()[0];
      }

      $query = $this->connection->select('groups_field_data', 'gfd');
      $query->addField('gfd', 'id');
      $query->condition('gfd.id', $solr_gids, 'IN');
      $subquery = $this->connection->select('flagging', 'f')
        ->condition('f.flag_id', $flag_id);
      $subquery->join('flagging__field_inactivity_duration', 'inactive');
      $subquery->addField('inactive', 'field_inactivity_duration_value');
      $subquery->condition('inactive.field_inactivity_duration_value', $duration);
      $subquery->addField('f', 'entity_id');
      $subquery->where('[f].[entity_id] = [gfd].[id]');

      // @see \Drupal\KernelTests\Core\Database\SelectSubqueryTest::testNotExistsSubquerySelect
      $query->notExists($subquery);

      $dbKey = 'id';
    }
    else {
      $query = $this->connection->select('flagging', 'f')
        ->condition('f.flag_id', $flag_id);
      $query->join('flagging__field_inactivity_duration', 'inactive');
      $query->addField('inactive', 'field_inactivity_duration_value');
      $query->condition('inactive.field_inactivity_duration_value', $previous_duration);
      $query->addField('f', 'entity_id');

      $dbKey = 'entity_id';
    }

    $gids = $query->execute()->fetchAllAssoc($dbKey);
    $gids = array_column($gids, $dbKey);

    $this->tokenService->addTokenData(
      $this->configuration['object'], $this->entityTypeManager
      ->getStorage('group')->loadMultiple($gids)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'inactivity_duration' => 1,
        'items' => 50,
        'check_previous_scenario' => FALSE,
        'previous_duration' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['inactivity_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Inactivity duration'),
      '#description' => $this->t('Enter how many months a group must be inactive for this condition to be TRUE.'),
      '#default_value' => $this->configuration['inactivity_duration'],
      '#field_suffix' => $this->t('month(s)'),
    ];

    $form['items'] = [
      '#type' => 'number',
      '#title' => $this->t('Items to load'),
      '#description' => $this->t('Enter how many items it should load. Max # is 50'),
      '#default_value' => $this->configuration['items'],
      '#max' => 50,
    ];

    $form['check_previous_scenario'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check previous scenario.'),
      '#description' => $this->t('Check if entity has been processed in a previous scenario.'),
      '#default_value' => $this->configuration['check_previous_scenario'],
    ];

    $form['previous_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Previous inactivity duration'),
      '#description' => $this->t('Enter the months of the previous scenario.'),
      '#default_value' => $this->configuration['inactivity_duration'],
      '#field_suffix' => $this->t('month(s)'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['inactivity_duration'] = $form_state->getValue('inactivity_duration');
    $this->configuration['items'] = $form_state->getValue('items');
    $this->configuration['check_previous_scenario'] = $form_state->getValue('check_previous_scenario');
    $this->configuration['previous_duration'] = $form_state->getValue('previous_duration');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Set the database service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database service.
   */
  public function setConnection(Connection $connection): void {
    $this->connection = $connection;
  }

}
