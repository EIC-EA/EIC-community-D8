<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_flags\BlockFlagTypes;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_messages\Util\NotificationMessageTemplates;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for creating messages notifications when an entity is blocked.
 */
class EntityBlockMessageCreator implements ContainerInjectionInterface {

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * EntityBlockMessageCreator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager service.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The Message bus service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessageBusInterface $message_bus
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageBus = $message_bus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_messages.message_bus')
    );
  }

  /**
   * Creates a notification message to be sent after blocking an entity.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flag associated with the block operation.
   */
  public function createBlockEntityNotification(
    FlaggingInterface $flagging
  ) {
    $supported_entity_types = BlockFlagTypes::getSupportedEntityTypes();

    if (!in_array($flagging->getFlagId(), array_values($supported_entity_types))) {
      return;
    }

    $entity = $flagging->getFlaggable();
    $to = [];

    switch ($entity->getEntityTypeId()) {
      case 'group':
        $owners = $entity->getMembers(EICGroupsHelper::GROUP_OWNER_ROLE);

        // If group has no owner, we don't send out any notification.
        if (empty($owners)) {
          return;
        }

        // We need to map the membership into an array of user entities.
        $to = array_map(
          function ($owner) {
            return $owner->getUser();
          },
          $owners
        );

        break;

      default:
        $to[] = $entity->getOwner();
        break;
    }

    foreach ($to as $user) {
      $message = $this->entityTypeManager->getStorage('message')->create(
        [
          'template' => NotificationMessageTemplates::ENTITY_BLOCKED,
          'field_referenced_flag' => $flagging,
          'uid' => $user->id(),
        ]
      );

      // Adds the reference to the user who blocked the entity.
      if ($message->hasField('field_event_executing_user')) {
        $message->set('field_event_executing_user', $flagging->getOwnerId());
      }

      $this->messageBus->dispatch($message);
    }
  }

}
