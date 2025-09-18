<?php

namespace Drupal\eic_eca\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eic_groups\EICGroupsHelper;

/**
 * Describes the eic_eca group_inactivity_action action.
 *
 * @Action(
 *   id = "eic_eca_flag_dispatch_group_admins",
 *   label = @Translation("Group flag dispatch action"),
 *   description = @Translation("Dispatch notification email for group admins."),
 *   eca_version_introduced = "1.0.0",
 *   type = "entity"
 *   )
 */
class GroupFlagDispatchAction extends ConfigurableActionBase {

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
    // Check if there's a flag entity already.
    $existing = $this->entityTypeManager->getStorage('flagging')
      ->loadByProperties([
        'entity_type' => 'group',
        'flag_id' => $this->configuration['flag_id'],
        'entity_id' => $entity->id(),
      ]);
    if (!empty($existing)) {
      /** @var \Drupal\flag\Entity\Flagging $flagging */
      $flagging = reset($existing);
      $flagging->set('field_inactivity_duration', $this->configuration['inactivity_duration']);
    }
    else {
      $flagging = $this->entityTypeManager->getStorage('flagging')->create([
        'flag_id' => $this->configuration['flag_id'],
        'entity_type' => 'group',
        'entity_id' => $entity->id(),
        'field_inactivity_duration' => $this->configuration['inactivity_duration'],
        'uid' => 1,
      ]);
    }
    $flagging->save();
    /** @var \Drupal\eic_messages\Service\MessageBusInterface $bus */
    $bus = \Drupal::service('eic_messages.message_bus');
    foreach (EICGroupsHelper::getGroupAdmins($entity) as $group_admin) {
      $admin = $group_admin->getUser();
      $bus->dispatch([
        'template' => $this->configuration['message_template'],
        'uid' => $admin->id(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'message_template' => 'notify_contact_inactive_group_6',
        'flag_id' => 'group_inactive',
        'inactivity_duration' => 6,
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
        'group_inactive' => $this->t('Group inactive'),
      ]
    ];

    $form['message_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Message template to use'),
      '#default_value' => $this->configuration['message_template'],
      '#options' => [
        'notify_contact_inactive_group_6' => $this->t('Notify contact inactive 6 months.'),
        'notify_contact_inactive_group_8' => $this->t('Notify contact inactive 8 months.'),
      ]
    ];

    $form['inactivity_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Inactivity duration'),
      '#description' => $this->t('Enter the same value as in the previous action!'),
      '#default_value' => $this->configuration['inactivity_duration'],
      '#field_suffix' => $this->t('month(s)'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['message_template'] = $form_state->getValue('message_template');
    $this->configuration['flag_id'] = $form_state->getValue('flag_id');
    $this->configuration['inactivity_duration'] = $form_state->getValue('inactivity_duration');
    parent::submitConfigurationForm($form, $form_state);
  }

}
