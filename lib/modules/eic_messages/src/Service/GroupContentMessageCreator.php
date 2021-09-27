<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eic_message_subscriptions\MessageSubscriptionType;
use Drupal\eic_message_subscriptions\SubscriptionOperationType;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\group\Entity\GroupInterface;
use Drupal\message\Entity\Message;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message creator class for group content.
 *
 * @package Drupal\eic_messages
 */
class GroupContentMessageCreator extends MessageCreatorBase {

  /**
   * The Flag sevice.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $service */
    $service = parent::create($container);
    $service->flagService = $container->get('flag');
    return $service;
  }

  /**
   * Implements hook_group_content_insert().
   *
   * Sends out message notifications upon group content creation.
   */
  public function groupContentInsert(EntityInterface $entity) {
    $messages = [];

    /** @var \Drupal\group\Entity\GroupContent $entity */
    $group_content_type = $entity->getGroupContentType();

    // New member joined notification.
    if ($group_content_type->get('content_plugin') === 'group_membership') {
      $relatedUser = $entity->getEntity();
      $relatedGroup = $entity->getGroup();

      // Prepare the message to the group owner.
      $message = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'notify_new_member_joined',
        'uid' => $relatedGroup->getOwnerId(),
        'field_group_ref' => ['target_id' => $relatedGroup->id()],
        'field_group_membership' => ['target_id' => $entity->id()],
        'field_related_user' => ['target_id' => $relatedUser->id()],
      ]);
      $messages[] = $message;
    }

    // User requested membership notification.
    if ($group_content_type->get('content_plugin') === 'group_membership_request') {
      $relatedUser = $entity->getEntity();
      $relatedGroup = $entity->getGroup();

      // Prepare the message to the group owner.
      $message = $this->entityTypeManager->getStorage('message')->create([
        'template' => 'notify_new_membership_request',
        'uid' => $relatedGroup->getOwnerId(),
        'field_group_ref' => ['target_id' => $relatedGroup->id()],
        'field_group_membership' => ['target_id' => $entity->id()],
        'field_related_user' => ['target_id' => $relatedUser->id()],
      ]);
      $messages[] = $message;
    }

    $this->processMessages($messages);
  }

  /**
   * Creates an activity stream message for an entity inside a group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group having this content.
   * @param string $operation
   *   The type of the operation. See ActivityStreamOperationTypes.
   */
  public function createGroupContentActivity(
    ContentEntityInterface $entity,
    GroupInterface $group,
    string $operation
  ) {
    $message = NULL;
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $message = $this->entityTypeManager->getStorage('message')->create([
          'template' => ActivityStreamMessageTemplates::getTemplate($entity),
          'field_referenced_node' => $entity,
          'field_operation_type' => $operation,
          'field_entity_type' => $entity->bundle(),
          'field_group_ref' => $group,
        ]);
        break;
    }

    try {
      $message->save();
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('eic_messages');
      $logger->error($e->getMessage());
    }
  }

  /**
   * Creates an subscription message for an entity inside a group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group having this content.
   * @param \Drupal\user\UserInterface $user
   *   The user entity that is subscribed to the content.
   * @param string $operation
   *   The type of the operations. See SubscriptionOperationType.
   */
  public function createGroupContentSubscription(
    ContentEntityInterface $entity,
    GroupInterface $group,
    UserInterface $user,
    string $operation
  ) {
    $message = NULL;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        $message_type = $operation === SubscriptionOperationType::UPDATED_ENTITY
          ? MessageSubscriptionType::GROUP_CONTENT_UPDATED
          : MessageSubscriptionType::NEW_GROUP_CONTENT_PUBLISHED;

        $message = Message::create([
          'template' => $message_type,
          'uid' => $user->id(),
        ]);

        // Adds the reference to the user who created/updated the entity.
        if ($message->hasField('field_event_executing_user')) {
          $executing_user_id = $entity->getOwnerId();

          $vid = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
            ->getLatestRevisionId($entity->id());

          if ($vid) {
            $latest_revision = $this->entityTypeManager->getStorage($entity->getEntityTypeId())
              ->loadRevision($vid);
            $executing_user_id = $latest_revision->getOwnerId();
          }

          $message->set('field_event_executing_user', $executing_user_id);
        }

        // @todo Set values for the missing fields.
        break;

    }

    try {
      $message->save();
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('eic_messages');
      $logger->error($e->getMessage());
    }

    return $message;
  }

}
