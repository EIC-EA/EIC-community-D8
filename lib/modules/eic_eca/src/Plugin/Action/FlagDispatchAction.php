<?php

namespace Drupal\eic_eca\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Describes the eic_eca group_inactivity_action action.
 *
 * @Action(
 *   id = "eic_eca_flag_dispatch_usre",
 *   label = @Translation("Flag dispatch action"),
 *   description = @Translation("Dispatch notification email for user."),
 *   eca_version_introduced = "1.0.0",
 *   type = "entity"
 *   )
 */
class FlagDispatchAction extends ConfigurableActionBase {

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
      'flag_id' => 'user_inactive_1_month',
      'entity_type' => 'user',
      'entity_id' => $entity->id(),
      'uid' => 1,
    ]);
    $flagging->save();

    /** @var \Drupal\eic_messages\Service\MessageBusInterface $bus */
    $bus = \Drupal::service('eic_messages.message_bus');

    $bus->dispatch([
      'template' => 'notify_contact_inactive_user',
      'uid' => $entity->id(),
    ]);
  }

}
