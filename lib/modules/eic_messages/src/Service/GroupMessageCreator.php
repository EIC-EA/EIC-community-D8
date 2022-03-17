<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_messages\Util\LogMessageTemplates;
use Drupal\eic_user\UserHelper;
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
   * Constructs new GroupMessageCreator object.
   *
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The message bus service.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC user helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    MessageBusInterface $message_bus,
    UserHelper $user_helper,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->messageBus = $message_bus;
    $this->userHelper = $user_helper;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_messages.message_bus'),
      $container->get('eic_user.helper'),
      $container->get('entity_type.manager')
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

    // Prepare messages to SA/CA.
    foreach ($this->userHelper->getSitePowerUsers() as $uid) {
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

    // Get the transition.
    $delimiter = '-->';
    $workflow_transition = $original_state . $delimiter . $current_state;

    switch ($workflow_transition) {
      // Group has been approved.
      case GroupsModerationHelper::GROUP_PENDING_STATE . $delimiter . GroupsModerationHelper::GROUP_DRAFT_STATE:
        $this->messageBus->dispatch([
          'template' => 'notify_group_request_approved',
          'uid' => $author_id,
          'field_group_ref' => ['target_id' => $entity->id()],
        ]);
        break;
    }

    // Create log message about the group state change.
    $this->entityTypeManager->getStorage('message')
      ->create([
        'template' => LogMessageTemplates::GROUP_STATE_CHANGE,
        'field_group_ref' => ['target_id' => $entity->id()],
        'field_previous_moderation_state' => $original_state,
        'field_moderation_state' => $current_state,
        'uid' => $author_id,
      ])
      ->save();
  }

}
