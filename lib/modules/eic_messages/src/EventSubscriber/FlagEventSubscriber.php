<?php

namespace Drupal\eic_messages\EventSubscriber;

use Drupal\eic_flags\BlockFlagTypes;
use Drupal\eic_messages\Service\EntityBlockMessageCreator;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to act on flag events.
 */
class FlagEventSubscriber implements EventSubscriberInterface {

  /**
   * The Entity Block Message Creator service.
   *
   * @var \Drupal\eic_messages\Service\EntityBlockMessageCreator
   */
  private $entityBlockMessageCreator;

  /**
   * FlagEventSubscriber constructor.
   *
   * @param \Drupal\eic_messages\Service\EntityBlockMessageCreator $entity_block_message_creator
   *   The Entity Block Message Creator service.
   */
  public function __construct(EntityBlockMessageCreator $entity_block_message_creator) {
    $this->entityBlockMessageCreator = $entity_block_message_creator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FlagEvents::ENTITY_FLAGGED] = ['onFlag', 49];
    return $events;
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    $flagging = $event->getFlagging();

    if (!in_array(
      $flagging->getFlagId(),
      array_values(BlockFlagTypes::getSupportedEntityTypes())
    )) {
      return;
    }

    // Create message notification and queue it without saving it in the
    // database.
    $this->entityBlockMessageCreator->createBlockEntityNotification($flagging);
  }

}
