<?php

namespace Drupal\eic_group_membership\EventSubscriber;

use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_messages\Service\MessageBusInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MembershipTransitionSubscriber
 *
 * @package Drupal\eic_group_membership\EventSubscriber
 */
class MembershipTransitionSubscriber implements EventSubscriberInterface {

  private MessageBusInterface $messageBus;

  /**
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   */
  public function __construct(MessageBusInterface $message_bus) {
    $this->messageBus = $message_bus;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'group_membership_request.approve.post_transition' => ['onMembershipApprove'],
    ];
  }

  /**
   * @param WorkflowTransitionEvent $event
   */
  public function onMembershipApprove(WorkflowTransitionEvent $event) {
    /** @var \Drupal\group\Entity\GroupContentInterface $membership */
    $membership = $event->getEntity();
    $group = $membership->getGroup();
    $member = $membership->getEntity();

    $this->messageBus->dispatch([
      'template' => 'notify_welcome_user_to_group',
      'uid' => $member->id(),
      'field_group_ref' => $group->id(),
      'field_event_executing_user' => $member->id(),
    ]);

    $admins = EICGroupsHelper::getGroupAdmins($group);
    if (!$admins) {
      return;
    }

    foreach ($admins as $membership) {
      $user = $membership->getUser();
      if (!$user instanceof UserInterface) {
        continue;
      }

      $this->messageBus->dispatch([
        'template' => 'notify_admin_group_new_member',
        'uid' => $user->id(),
        'field_group_ref' => $group->id(),
        'field_related_user' => $user->id(),
      ]);
    }
  }

}
