<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_messages\Service\MessageBusInterface;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   The content moderation information service.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The message bus service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    ModerationInformationInterface $moderationInformation,
    MessageBusInterface $message_bus,
    AccountProxyInterface $current_user
  ) {
    $this->moderationInformation = $moderationInformation;
    $this->messageBus = $message_bus;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('eic_messages.message_bus'),
      $container->get('current_user')
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

    $message_uid = $this->getContentStatusChangedMessageReceiver($entity);
    // If there is no receiver for the message, we don't send the notification.
    if (!$message_uid) {
      return;
    }

    // Gets the entity type label to use in the message notification.
    $entity_type_label = $entity->getEntityType()->getLabel();

    // By default, notification is not skipped.
    $skip_notification = FALSE;
    // Default message fields.
    $message = [
      'template' => 'notify_content_status_changed',
      'uid' => $message_uid,
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
      case self::MODERATION_STATE_BLOCKED:
        // For group entities we use a different message template.
        if ($entity->getEntityTypeId() === 'group') {
          $message = [
            'template' => 'notify_group_re_published',
            'uid' => $message_uid,
            'field_group_ref' => ['target_id' => $entity->id()],
            'field_event_executing_user' => ['target_id' => $this->currentUser->id()],
          ];
          break;
        }

        $message['field_message_subject'] = $this->t(
          '@entity_label was published once again',
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

  /**
   * Gets the user ID that will receive the notification about content status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that changed status.
   *
   * @return bool|int
   *   Returns the user ID if found, otherwise it returns FALSE.
   */
  private function getContentStatusChangedMessageReceiver(EntityInterface $entity) {
    $uid = FALSE;
    switch ($entity->getEntityTypeId()) {
      case 'group':
        /** @var \Drupal\group\Entity\GroupInterface $entity */
        $group_owners = $entity->getMembers([EICGroupsHelper::GROUP_OWNER_ROLE]);

        // The group doesn't have any owner and therefore we don't send any
        // notification.
        if (empty($group_owners)) {
          break;
        }

        // Updates message uid with the right group owner uid.
        $uid = reset($group_owners)->getUser()->id();
        break;

      default:
        $uid = $entity->getOwnerId();
        break;
    }
    return $uid;
  }

}
