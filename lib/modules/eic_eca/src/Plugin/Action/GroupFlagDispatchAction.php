<?php

namespace Drupal\eic_eca\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eic_groups\EICGroupsHelper;

/**
 * Describes the eic_eca group_inactivity_action action.
 *
 * @Action(
 *   id = "eic_eca_flag_dispatch_group_admins",
 *   label = @Translation("Group flag dispatch action"),
 *   description = @Translation("Dispatch notification email for group
 *   admins."), eca_version_introduced = "1.0.0", type = "entity"
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
    $flagging = $this->entityTypeManager->getStorage('flagging')->create([
      'flag_id' => 'group_inactive',
      'entity_type' => 'group',
      'entity_id' => $entity->id(),
      'field_inactivity_duration' => 6,
      'uid' => 1,
    ]);
    $flagging->save();
    /** @var \Drupal\eic_messages\Service\MessageBusInterface $bus */
    $bus = \Drupal::service('eic_messages.message_bus');
    foreach (EICGroupsHelper::getGroupAdmins($entity) as $group_admin) {
      $admin = $group_admin->getUser();
      $bus->dispatch([
        'template' => 'notify_contact_inactive_group_6',
        'uid' => $admin->id(),
      ]);
    }
  }

}
