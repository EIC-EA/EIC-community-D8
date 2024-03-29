<?php

namespace Drupal\eic_recommend_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\FlagType;
use Drupal\eic_messages\Hooks\MessageTokens;
use Drupal\eic_messages\Service\MessageBusInterface;
use Drupal\eic_messages\Util\NotificationMessageTemplates;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\FlagInterface;
use Drupal\message\Entity\Message;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to trigger message notifications on flag events.
 */
class FlagEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The state cache.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The message bus service.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  protected $messageBus;

  /**
   * Constructs a new FlagEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The message bus service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    MessageBusInterface $message_bus
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->messageBus = $message_bus;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FlagEvents::ENTITY_FLAGGED => ['onFlag', 50],
    ];
  }

  /**
   * React to flagging event.
   *
   * Queues up a message subscription item to be processed later by cron, so
   * that subscribed users can receive a notification.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    // Gets the flagging entity associated with the event.
    $flagging = $event->getFlagging();

    // Gets the flag entity from flagging.
    $flag = $flagging->getFlag();

    if (!$this->isApplicable($flag)) {
      return;
    }

    // Gets the flagged entity.
    $flagged_entity = $flagging->getFlaggable();

    // If entity is not publish we don't need to notify users, so we can exit.
    if (!$flagged_entity->isPublished()) {
      return;
    }

    // Renders the recommended entity using view mode 'mail_teaser'.
    $view_builder = $this->entityTypeManager->getViewBuilder($flagged_entity->getEntityTypeId());
    $renderable_entity = $view_builder->view($flagged_entity, 'mail_teaser');
    $rendered_entity = $this->renderer->renderPlain($renderable_entity);

    // Creates notification message.
    $message = Message::create([
      'uid' => $flagging->getOwnerId(),
      'template' => NotificationMessageTemplates::CONTENT_RECOMMENDATION,
      'field_referenced_flag' => $flagging,
      MessageTokens::RENDERED_CONTENT_FIELD => $rendered_entity,
    ]);

    // Grabs the array of emails to send out the notification.
    $emails = explode(',', trim(strip_tags($flagging->get('field_recommend_emails')->value)));

    // Sends notification to all emails.
    foreach ($emails as $email) {
      $message->set('field_receiver_email', $email);
      $this->messageBus->dispatch(
        $message,
        [
          'mail' => $email,
        ]
      );
    }
  }

  /**
   * Checks if a flag can trigger message subscriptions.
   *
   * @return bool
   *   TRUE if the flag can trigger message subscriptions.
   */
  public function isApplicable(FlagInterface $flag) {
    $allowed_flag_types = self::getAllowedFlagTypes();
    return in_array($flag->id(), $allowed_flag_types);
  }

  /**
   * Gets flag types that can trigger message notification.
   *
   * @return array
   *   Array of allowed flag types.
   */
  public static function getAllowedFlagTypes() {
    return [
      FlagType::RECOMMEND_NODE,
      FlagType::RECOMMEND_CONTENT_GROUP,
    ];
  }

}
