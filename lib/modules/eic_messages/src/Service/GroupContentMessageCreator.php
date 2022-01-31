<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message creator class for group content.
 *
 * @package Drupal\eic_messages
 */
class GroupContentMessageCreator implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   */
  public function __construct(
    AccountProxyInterface $current_user,
    MessageBusInterface $message_bus
  ) {
    $this->currentUser = $current_user;
    $this->messageBus = $message_bus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('eic_messages.message_bus')
    );
  }

  /**
   * Implements hook_group_content_insert().
   *
   * Sends out message notifications upon group content creation.
   */
  public function groupContentInsert(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupContent $entity */
    $group_content_type = $entity->getGroupContentType();

    // New member joined notification.
    if ($group_content_type->get('content_plugin') === 'group_membership') {
      $relatedUser = $entity->getEntity();
      $relatedGroup = $entity->getGroup();

      $this->messageBus->dispatch([
        'template' => 'notify_new_member_joined',
        'uid' => $relatedGroup->getOwnerId(),
        'field_group_ref' => ['target_id' => $relatedGroup->id()],
        'field_group_membership' => ['target_id' => $entity->id()],
        'field_related_user' => ['target_id' => $relatedUser->id()],
      ]);
    }

    // User requested membership notification.
    if ($group_content_type->get('content_plugin') === 'group_membership_request') {
      $relatedUser = $entity->getEntity();
      $relatedGroup = $entity->getGroup();

      // Prepare the message to the group owner.
      $this->messageBus->dispatch([
        'template' => 'notify_new_membership_request',
        'uid' => $relatedGroup->getOwnerId(),
        'field_group_ref' => ['target_id' => $relatedGroup->id()],
        'field_group_membership' => ['target_id' => $entity->id()],
        'field_related_user' => ['target_id' => $relatedUser->id()],
      ]);
    }
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
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $this->messageBus->dispatch([
          'template' => ActivityStreamMessageTemplates::getTemplate($entity),
          'field_referenced_node' => $entity,
          'field_operation_type' => $operation,
          'field_entity_type' => $entity->bundle(),
          'field_group_ref' => $group,
        ]);
        break;
    }
  }


}
