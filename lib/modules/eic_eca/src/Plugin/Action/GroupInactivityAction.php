<?php

namespace Drupal\eic_eca\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

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
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    $access_result = AccessResult::allowed();
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($group = NULL): void {
    $duration = (int) $this->configuration['inactivity_duration'];
    $timestamp_inactivity = strtotime("-$duration months");

    $index = \Drupal\search_api\Entity\Index::load('global');
    $query = $index->query();
    $query->addCondition('search_api_datasource', 'entity:group')
      ->addCondition('changed', 1738928674, '<');

    // Execute the search.
    $results = $query->execute();

    // @todo implement the required action.
    $entities = [];
    $this->tokenService->addTokenData('inactive_entities', $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'inactivity_duration' => '',
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
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['inactivity_duration'] = $form_state->getValue('inactivity_duration');
    parent::submitConfigurationForm($form, $form_state);
  }

}
