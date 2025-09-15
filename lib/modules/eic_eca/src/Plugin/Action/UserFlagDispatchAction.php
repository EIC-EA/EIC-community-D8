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
 *   id = "eic_eca_flag_dispatch_user",
 *   label = @Translation("User flag dispatch action"),
 *   description = @Translation("Dispatch notification email for user."),
 *   eca_version_introduced = "1.0.0",
 *   type = "entity"
 *   )
 */
class UserFlagDispatchAction extends ConfigurableActionBase {

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
  public function execute($entity = NULL): void {
    $flagging = $this->entityTypeManager->getStorage('flagging')->create([
      'flag_id' => $this->configuration['flag_id'],
      'entity_type' => 'user',
      'entity_id' => $entity->id(),
      'uid' => 1,
    ]);
    $flagging->save();

    /** @var \Drupal\eic_messages\Service\MessageBusInterface $bus */
    $bus = \Drupal::service('eic_messages.message_bus');

    $bus->dispatch([
      'template' => $this->configuration['message_template'],
      'uid' => $entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'message_template' => 'notify_contact_inactive_user',
        'flag_id' => 'user_inactive_1_month',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['flag_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Flag ID to record the transaction.'),
      '#default_value' => $this->configuration['flag_id'],
      '#options' => [
        'user_inactive_1_month' => $this->t('User inactive 1 month'),
      ]
    ];

    $form['message_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Message template to use'),
      '#default_value' => $this->configuration['message_template'],
      '#options' => [
        'notify_contact_inactive_user' => $this->t('Notify user inactive 1 month.'),
      ]
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['message_template'] = $form_state->getValue('message_template');
    $this->configuration['flag_id'] = $form_state->getValue('flag_id');
    parent::submitConfigurationForm($form, $form_state);
  }

}
