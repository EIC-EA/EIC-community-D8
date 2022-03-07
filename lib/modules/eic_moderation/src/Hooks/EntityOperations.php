<?php

namespace Drupal\eic_moderation\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\eic_messages\Service\MessageBusInterface;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\eic_moderation\Service\ContentModerationManager;
use Drupal\eic_user\UserHelper;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityOperations.
 *
 * Implementations for entity hooks.
 */
class EntityOperations implements ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\eic_moderation\Service\ContentModerationManager
   */
  protected $contentModerationManager;

  /**
   * The EIC message bus.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  protected $messageBus;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $userHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The Moderation information service.
   * @param \Drupal\eic_moderation\Service\ContentModerationManager $content_moderation_manager
   *   The EIC content moderation service.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The EIC message bus.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC User helper service.
   */
  public function __construct(
    ModerationInformationInterface $moderation_information,
    ContentModerationManager $content_moderation_manager,
    MessageBusInterface $message_bus,
    UserHelper $user_helper
  ) {
    $this->moderationInformation = $moderation_information;
    $this->contentModerationManager = $content_moderation_manager;
    $this->messageBus = $message_bus;
    $this->userHelper = $user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('eic_moderation.content_moderation_manager'),
      $container->get('eic_messages.message_bus'),
      $container->get('eic_user.helper')
    );
  }

  /**
   * Implements hook_node_insert().
   */
  public function nodeInsert(NodeInterface $node) {
    $this->sendNotification($node);
  }

  /**
   * Implements hook_node_update().
   */
  public function nodeUpdate(NodeInterface $node) {
    $this->sendNotification($node);
  }

  /**
   * Sends out message notifications for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   */
  public function sendNotification(NodeInterface $node) {
    if (!$this->contentModerationManager->isSupportedByWorkflow($node, EICContentModeration::MACHINE_NAME)) {
      return;
    }

    if (!$this->contentModerationManager->isTransitioned($node)) {
      return;
    }

    switch ($node->get('moderation_state')->value) {
      case EICContentModeration::STATE_WAITING_APPROVAL:
        // Dispatch the message.
        foreach ($this->userHelper->getSitePowerUsers() as $uid) {
          $this->messageBus->dispatch([
            'template' => EICContentModeration::MESSAGE_WAITING_APPROVAL,
            'uid' => $uid,
            'field_referenced_node' => $node->id(),
            'field_event_executing_user' => $this->userHelper->getCurrentUser()->id(),
          ]);
        }
        break;

      case EICContentModeration::STATE_NEEDS_REVIEW:
        // Dispatch the message.
        $this->messageBus->dispatch([
          'template' => EICContentModeration::MESSAGE_NEEDS_REVIEW,
          'uid' => $node->getOwnerId(),
          'field_referenced_node' => $node->id(),
          'field_event_executing_user' => $this->userHelper->getCurrentUser()->id(),
        ]);
        break;

      case EICContentModeration::STATE_PUBLISHED:
        // Dispatch the message.
        $this->messageBus->dispatch([
          'template' => EICContentModeration::MESSAGE_PUBLISHED,
          'uid' => $node->getOwnerId(),
          'field_referenced_node' => $node->id(),
          'field_event_executing_user' => $this->userHelper->getCurrentUser()->id(),
        ]);
        break;
    }

  }

}
