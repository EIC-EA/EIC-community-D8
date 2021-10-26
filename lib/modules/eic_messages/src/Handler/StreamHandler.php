<?php

namespace Drupal\eic_messages\Handler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\eic_messages\Stamps\PersistentMessageStamp;
use Drupal\message\MessageInterface;

/**
 * Class StreamHandler
 *
 * @package Drupal\eic_messages\Handler
 */
class StreamHandler implements MessageHandlerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(array $payload): void {
    // For activity stream items, just save them.
    $payload['entity']->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return MessageTemplateTypes::STREAM;
  }

  /**
   * {@inheritdoc}
   */
  public function getStamps(MessageInterface $message): array {
    return [
      PersistentMessageStamp::class,
    ];
  }

}
