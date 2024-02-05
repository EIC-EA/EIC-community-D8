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
   * Implements hook_ENTITY_TYPE_presave().
   */
  public function nodePresave(NodeInterface $node) {
    $this->revisionLogUpdate($node);
  }

  /**
   * Implements hook_node_update().
   */
  public function nodeUpdate(NodeInterface $node) {
    $this->sendNotification($node);
  }

  /**
   * Sets the revision log message automatically.
   */
  public function revisionLogUpdate(NodeInterface $node) {
    $allowed_content_types = [
      'book',
      'document',
      'event',
      'gallery',
      'news',
      'page',
      'story',
      'video',
      'wiki_page',
    ];
    $bundle = $node->bundle();
    if (in_array($bundle, $allowed_content_types, TRUE)) {
      if (!$this->contentModerationManager->isSupportedByWorkflow($node, EICContentModeration::MACHINE_NAME)) {
        return;
      }

      if (!$this->contentModerationManager->isTransitioned($node)) {
        return;
      }

      $workflow_state_action_map = [
        EICContentModeration::STATE_DRAFT => $this->t('draft created', [], ['context' => 'eic_moderation'])->render(),
        EICContentModeration::STATE_WAITING_APPROVAL => $this->t('sent for approval', [], ['context' => 'eic_moderation'])->render(),
        EICContentModeration::STATE_NEEDS_REVIEW => $this->t('rejected', [], ['context' => 'eic_moderation'])->render(),
        EICContentModeration::STATE_PUBLISHED => $this->t('approved and published', [], ['context' => 'eic_moderation'])->render(),
        EICContentModeration::STATE_UNPUBLISHED => $this->t('unpublished', [], ['context' => 'eic_moderation'])->render(),
      ];

      $current_user = $this->userHelper->getCurrentUser();
      $user_message = ((string) $current_user->getDisplayName());
      $roles = $current_user->getRoles(TRUE);
      if (in_array('content_administrator', $roles, TRUE)) {
        $user_message .= ' - Content Administrator';
      }
      $message = $this->t("@bundle @action by @display_name", [
        '@bundle' => ucfirst($bundle),
        '@action' => $workflow_state_action_map[$node->get('moderation_state')->value],
        '@display_name' => $user_message,
      ]);
      if (!$node->get('revision_log')->isEmpty()) {
        $message = $node->get('revision_log')
          ->getValue()[0]['value'] . ' - ' . $message->render();
      }
      $node->set('revision_log', $message);

    }
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
            'field_message' => $node->getRevisionLogMessage(),
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
          'field_message' => $node->getRevisionLogMessage(),
        ]);
        break;

      case EICContentModeration::STATE_PUBLISHED:
        // Dispatch the message.
        $this->messageBus->dispatch([
          'template' => EICContentModeration::MESSAGE_PUBLISHED,
          'uid' => $node->getOwnerId(),
          'field_referenced_node' => $node->id(),
          'field_event_executing_user' => $this->userHelper->getCurrentUser()->id(),
          'field_message' => $node->getRevisionLogMessage(),
        ]);
        break;
    }

  }

}
