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
 *   id = "eic_eca_user_inactivity_action",
 *   label = @Translation("User inactivity action"),
 *   description = @Translation("Add to token the users inactive for a given period of time."),
 *   eca_version_introduced = "1.0.0",
 *   type = "entity"
 *   )
 */
class UserNotLoggedInAction extends ConfigurableActionBase {

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
    $items = (int) $this->configuration['items'];
    $timestamp_inactivity = strtotime("-$duration months");

    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('created', $timestamp_inactivity, '<=')
      ->condition('access', 0)
      ->range(length: $items)
      ->accessCheck(FALSE)
      ->execute();

    $this->tokenService->addTokenData(
      $this->configuration['object'], $this->entityTypeManager
        ->getStorage('user')->loadMultiple($uids)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'inactivity_duration' => 1,
      'items' => 50,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['inactivity_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Inactivity duration'),
      '#description' => $this->t('Enter how many months a user must exist in the system for this condition to be TRUE.'),
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
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['inactivity_duration'] = $form_state->getValue('inactivity_duration');
    $this->configuration['items'] = $form_state->getValue('items');
    parent::submitConfigurationForm($form, $form_state);
  }

}
