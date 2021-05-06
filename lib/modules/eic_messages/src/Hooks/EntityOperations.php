<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_user\UserHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EIC Message helper service.
   *
   * @var \Drupal\eic_messages\MessageHelper
   */
  protected $eicMessagesHelper;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $eicUserHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_messages\MessageHelper $eic_messages_helper
   *   The EIC Message helper service.
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessageHelper $eic_messages_helper, UserHelper $eic_user_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eicMessagesHelper = $eic_messages_helper;
    $this->eicUserHelper = $eic_user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_messages.helper'),
      $container->get('eic_user.helper')
    );
  }

  /**
   * Implements hook_group_insert().
   *
   * Sends out message notifications upon group creation.
   */
  public function groupInsert(EntityInterface $entity) {
    $messages = [];
    // Prepare the message to the requester.
    $message = $this->entityTypeManager->getStorage('message')->create([
      'template' => 'notify_group_requested',
      'uid' => $entity->get('uid')->getValue()[0]['target_id'],
    ]);
    $message->set('field_group_ref', $entity->id());
    $messages[] = $message;

    // Prepare messages to SA/CA.
    foreach ($this->eicUserHelper->getSitePowerUsers() as $uid) {
      $message = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'notify_group_request_submitted',
        'uid' => $uid,
      ]);
      $messages[] = $message;
    }

    foreach ($messages as $message) {
      try {
        // Create the message notify queue item.
        // @todo check if this type of message should live/stay in the DB.
        $this->eicMessagesHelper->queueMessageNotification($message);
      }
      catch (\Exception $e) {
        $logger = $this->getLogger('eic_messages');
        $logger->error($e->getMessage());
      }
    }
  }

  /**
   * Implements hook_group_update().
   *
   * Sends out message notifications upon group state changes.
   */
  public function groupUpdate(EntityInterface $entity) {
    // Check if state has changed.
    if ($entity->get('moderation_state')->getValue() == $entity->original->get('moderation_state')->getValue()) {
      return;
    }

    // Get the current and original Moderation states.
    $current_state = $entity->get('moderation_state')->getValue()[0]['value'];
    $original_state = $entity->original->get('moderation_state')->getValue()[0]['value'];

    // Get the transition.
    $delimiter = '-->';
    $workflow_transition = $original_state . $delimiter . $current_state;

    $message = NULL;
    switch ($workflow_transition) {
      // Group has been approved.
      case GroupsModerationHelper::GROUP_PENDING_STATE . $delimiter . GroupsModerationHelper::GROUP_DRAFT_STATE:
        $message = $this->entityTypeManager->getStorage('message')->create([
          'template' => 'notify_group_request_approved',
          'uid' => $entity->get('uid')->getValue()[0]['target_id'],
        ]);
        $message->set('field_group_ref', $entity->id());
        break;

    }

    if ($message) {
      try {
        // Save the message and create the message notify queue item.
        // @todo check if this type of message should live/stay in the DB.
        $message->save();
        $this->eicMessagesHelper->queueMessageNotification($message);
      }
      catch (\Exception $e) {
        $logger = $this->getLogger('eic_messages');
        $logger->error($e->getMessage());
      }
    }
  }

}
