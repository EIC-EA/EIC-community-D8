<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_messages\Service\MessageBusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityUpdate.
 *
 * Implementations for entity hooks.
 *
 * @package Drupal\eic_messages\Hooks
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The content moderation archived state key.
   *
   * @var string
   */
  const MODERATION_STATE_ARCHIVED = 'archived';

  /**
   * The content moderation blocked state key.
   *
   * @var string
   */
  const MODERATION_STATE_BLOCKED = 'blocked';

  /**
   * The entity type labels to use in notification message.
   *
   * @var array
   */
  const ENTITY_TYPE_LABELS = [
    'node' => 'content',
    'group__group' => 'group',
    'group__event' => 'event',
  ];

  /**
   * The content moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * The message bus service.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   The content moderation information service.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The message bus service.
   */
  public function __construct(
    ModerationInformationInterface $moderationInformation,
    MessageBusInterface $message_bus
  ) {
    $this->moderationInformation = $moderationInformation;
    $this->messageBus = $message_bus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('eic_messages.message_bus')
    );
  }

  /**
   * Implements hook_entity_update().
   */
  public function entityUpdate(EntityInterface $entity) {
    // We do send notifications only for moderated contents.
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    // Gets the previous content moderation state.
    $previous_state = $entity->original->get('moderation_state')->value;
    // Gets the current content moderation state.
    $current_state = $entity->get('moderation_state')->value;
    // If the content moderation state didn't change, we do nothing.
    if ($previous_state === $current_state) {
      return;
    }

    // Gets the entity type label to use in the message notification.
    $entity_type_label = isset(self::ENTITY_TYPE_LABELS[$entity->getEntityTypeId()])
      ? self::ENTITY_TYPE_LABELS[$entity->getEntityTypeId()]
      : (isset(self::ENTITY_TYPE_LABELS["{$entity->getEntityTypeId()}__{$entity->bundle()}"])
          ? self::ENTITY_TYPE_LABELS["{$entity->getEntityTypeId()}__{$entity->bundle()}"]
          : $entity->getEntityTypeId());

    // By default notification is not skipped.
    $skip_notification = FALSE;
    // Default message fields.
    $message = [
      'template' => 'notify_content_status_changed',
      'uid' => $entity->getOwnerId(),
      'field_message_subject' => NULL,
      'field_referenced_entity_label' => $entity->label(),
      'field_entity_prev_status_label' => $previous_state,
      'field_entity_status_label' => $current_state,
      'field_entity_url' => [
        'uri' => $entity->toUrl()->toString(),
        'title' => $entity->label(),
      ],
    ];

    switch ($previous_state) {
      case self::MODERATION_STATE_ARCHIVED:
        $message['field_message_subject'] = $this->t(
          'Your @entity_type "@entity_label" has been published again',
          [
            '@entity_type' => $entity_type_label,
            '@entity_label' => $entity->label(),
          ]
        );
        break;

      case self::MODERATION_STATE_BLOCKED:
        $message['field_message_subject'] = $this->t(
          'Your @entity_type "@entity_label" has been unblocked',
          [
            '@entity_type' => $entity_type_label,
            '@entity_label' => $entity->label(),
          ]
        );
        break;

      default:
        $skip_notification = TRUE;
        break;

    }

    if ($skip_notification) {
      return;
    }

    // Check if the new state is marked as "published".
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $is_published_state = $workflow->getTypePlugin()
      ->getState($current_state)
      ->isPublishedState();
    if (!$is_published_state) {
      return;
    }

    $this->messageBus->dispatch($message);
  }

}
