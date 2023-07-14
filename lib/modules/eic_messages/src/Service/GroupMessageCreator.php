<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_groups\Constants\GroupVisibilityType;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_messages\Util\LogMessageTemplates;
use Drupal\eic_messages\Util\NotificationMessageTemplates;
use Drupal\eic_user\UserHelper;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message creator class for groups.
 */
class GroupMessageCreator implements ContainerInjectionInterface {

  /**
   * The message bus service.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  protected $messageBus;

  /**
   * The EIC user helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs new GroupMessageCreator object.
   *
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The message bus service.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC user helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   * @param \Drupal\eic_groups\EICGroupsHelper $current_user
   *   The current user account.
   */
  public function __construct(
    MessageBusInterface $message_bus,
    UserHelper $user_helper,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user
  ) {
    $this->messageBus = $message_bus;
    $this->userHelper = $user_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_messages.message_bus'),
      $container->get('eic_user.helper'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Implements hook_group_insert().
   *
   * Sends out message notifications upon group creation.
   */
  public function groupInsert(ContentEntityInterface $entity) {
    $author_id = $entity->get('uid')->getValue()[0]['target_id'];

    // Prepare the message to the requester.
    $this->messageBus->dispatch([
      'template' => 'notify_group_requested',
      'uid' => $author_id,
      'field_group_ref' => ['target_id' => $entity->id()],
    ]);

    // We get visibility from request since new group doesnt have yet a visibility record.
    $is_group_sensitive =
      \Drupal::request()->request->get('group_visibility') ===
      GroupVisibilityType::GROUP_VISIBILITY_SENSITIVE;
    $uids = $this->userHelper->getSitePowerUsers();

    $uids = array_filter($uids, function ($uid) use ($is_group_sensitive) {
      $user = User::load($uid);

      return !$is_group_sensitive || $user->hasRole('sensitive');
    });

    // Prepare messages to SA/CA.
    foreach ($uids as $uid) {
      $this->messageBus->dispatch([
        'template' => 'notify_group_request_submitted',
        'uid' => $uid,
        'field_group_ref' => ['target_id' => $entity->id()],
        'field_event_executing_user' => ['target_id' => $author_id],
      ]);
    }
  }

  /**
   * Implements hook_group_update().
   *
   * Sends out message notifications upon group state changes.
   */
  public function groupUpdate(ContentEntityInterface $entity) {
    // Check if state has changed.
    if ($entity->get('moderation_state')->getValue() === $entity->original->get('moderation_state')->getValue()) {
      return;
    }

    $author_id = $entity->get('uid')->getValue()[0]['target_id'];
    // Get the current and original Moderation states.
    $current_state = $entity->get('moderation_state')->getValue()[0]['value'];
    $original_state = $entity->original->get('moderation_state')
      ->getValue()[0]['value'];

    // We never keep a refused state group, put default pending state.
    if (GroupsModerationHelper::GROUP_REFUSED_STATE === $current_state) {
      $entity->set('moderation_state', GroupsModerationHelper::GROUP_PENDING_STATE);

      // If group was pending and has been refused, notify the user.
      if (GroupsModerationHelper::GROUP_PENDING_STATE === $original_state) {
        $message = Message::create([
          'template' => 'notify_group_request_denied',
          'field_group_ref' => ['target_id' => $entity->id()],
          'field_reason' => $entity->getRevisionLogMessage() ?: t(
            'No message left from the moderator.',
            [],
            ['context' => 'eic_messages']
          ),
        ]);

        $message->setOwnerId($author_id);
        $this->messageBus->dispatch($message);
        return;
      }
    }

    // Get the transition.
    $delimiter = '-->';
    $workflow_transition = $original_state . $delimiter . $current_state;

    switch ($workflow_transition) {
      // Group has been approved.
      case GroupsModerationHelper::GROUP_PENDING_STATE . $delimiter . GroupsModerationHelper::GROUP_DRAFT_STATE:
        $message = Message::create([
          'template' => 'notify_group_request_approved',
          'field_group_ref' => ['target_id' => $entity->id()],
        ]);

        $message->setOwnerId($author_id);
        $this->messageBus->dispatch($message);
        break;
    }

    // Create log message about the group state change.
    $this->entityTypeManager->getStorage('message')
      ->create([
        'template' => LogMessageTemplates::GROUP_STATE_CHANGE,
        'field_group_ref' => ['target_id' => $entity->id()],
        'field_previous_moderation_state' => $original_state,
        'field_moderation_state' => $current_state,
        'uid' => $this->currentUser->id(),
      ])
      ->save();
  }

  /**
   * Implements hook_eic_groups_group_predelete().
   *
   * Sends out message notifications upon group deletion.
   */
  public function groupPredelete(array $entities) {
    $power_users = $this->userHelper->getSitePowerUsers();
    foreach ($entities as $group) {
      $send_to = $power_users;
      if ($group_owner = EICGroupsHelper::getGroupOwner($group)) {
        $send_to[] = $group_owner->id();
      }
      foreach ($send_to as $uid) {
        $this->messageBus->dispatch([
          'template' => NotificationMessageTemplates::GROUP_DELETE,
          'field_entity_type' => ['target_id' => $group->getEntityTypeId()],
          'field_referenced_entity_label' => $group->label(),
          'field_event_executing_user' => $this->currentUser->id(),
          'uid' => $uid,
        ]);
      }
    }
  }

}
