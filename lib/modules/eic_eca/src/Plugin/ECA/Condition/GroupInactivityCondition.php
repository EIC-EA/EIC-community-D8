<?php

namespace Drupal\eic_eca\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;

/**
 * Plugin implementation of the ECA condition "Checks group inactivity".
 *
 * @EcaCondition(
 *   id = "eic_group_inactivity",
 *   label = @Translation("Group inactivity"),
 *   description = @Translation(""),
 *   eca_version_introduced = "1.0.0",
 *   context_definitions = {
 * *     "user" = @ContextDefinition("entity:group", label = @Translation("User"))
 * *   }
 *
 * )
 */
class GroupInactivityCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $group = $this->getValueFromContext('group');
    $result = TRUE;
    return $this->negationCheck($result);
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
