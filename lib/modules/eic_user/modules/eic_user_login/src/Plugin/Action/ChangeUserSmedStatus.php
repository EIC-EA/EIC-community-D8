<?php

namespace Drupal\eic_user_login\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_user_login\Constants\SmedUserStatuses;

/**
 * Changes SMED status of users.
 *
 * @Action(
 *   id = "eic_change_user_smed_status",
 *   label = @Translation("Change user SMED status"),
 *   type = "user"
 * )
 */
class ChangeUserSmedStatus extends ConfigurableActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\user\UserInterface $entity */
    $entity->field_user_status->value = $this->configuration['smed_status'];
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfiguration() {
    return [
      'smed_status' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['smed_status'] = [
      '#type' => 'select',
      '#title' => $this->t('SMED status'),
      '#description' => $this->t('The SMED status to assign to selected users.'),
      '#default_value' => '',
      '#options' => ['' => $this->t('- None selected -')] + SmedUserStatuses::getUserStatuses(),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['smed_status'] = $form_state->getValue('smed_status');
  }

}
