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
    $items = (int) $this->configuration['items'];
    $timestamp_inactivity = strtotime("-$duration months");

    $dbq = $this->connection->select('flagging', 'f');
    $dbq->join('flagging__field_inactivity_duration', 'inactive', 'inactive.entity_id = f.id AND inactive.field_inactivity_duration_value = :duration', [':duration' => $duration] );
    $dbq->condition('f.flag_id', $flag_id);
    $dbq->addField('f', 'entity_id');

    $flag_gids = $dbq->execute()->fetchAllAssoc('entity_id');
    $flag_gids = array_column($flag_gids, 'entity_id');

    $solr_query = $this->entityTypeManager->getStorage('search_api_index')
      ->load('global')->query();

    // Change the parse mode for the search.
    $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')
      ->createInstance('direct');
    $parse_mode->setConjunction('OR');
    $solr_query->setParseMode($parse_mode);
    $solr_query->addCondition('search_api_datasource', 'entity:group')
      ->addCondition('group_type', 'group')
      ->addCondition('group_id_integer', $flag_gids, 'NOT IN')
      ->addCondition('group_changed', $timestamp_inactivity, '<');
    $solr_query->range(0, $items);
    $solr_query->sort('group_changed', QueryInterface::SORT_DESC);

    if ($this->configuration['check_previous_scenario']) {
      // If this is checked, tell SOLR to search only in groups that were
      // processed in the previous scenario.
      $query = $this->connection->select('flagging', 'f');
      $query->join('flagging__field_inactivity_duration', 'inactive', 'inactive.entity_id = f.id AND inactive.field_inactivity_duration_value = :duration',  [':duration' => $previous_duration]);
      $query->condition('f.flag_id', $flag_id);
      $query->addField('f', 'entity_id');

      $gids = $query->execute()->fetchAllAssoc('entity_id');
      $gids = array_column($gids, 'entity_id');

      $solr_query->addCondition('group_id_integer', $gids, 'IN');
    }

    // Execute the search.
    $results = $solr_query->execute();
    $gids = [];
    foreach ($results as $result) {
      $gids[] = $result->getField('group_id_integer')->getValues()[0];
    }

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
