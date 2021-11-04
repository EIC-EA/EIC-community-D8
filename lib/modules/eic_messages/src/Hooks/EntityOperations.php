<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_messages\Service\MessageBusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityUpdate
 *
 * @package Drupal\eic_messages\Hooks
 */
class EntityOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
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
    if (!$this->moderationInformation->isModeratedEntity($entity)
      || $entity->original->get('moderation_state')->value !== 'archived') {
      return;
    }

    // Check if the new state is marked as "published".
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $is_published_state = $workflow->getTypePlugin()
      ->getState($entity->get('moderation_state')->value)
      ->isPublishedState();
    if (!$is_published_state) {
      return;
    }

    $this->messageBus->dispatch([
      'template' => 'notify_archived_republished',
      'uid' => $entity->getOwnerId(),
      'field_message_subject' => $this->t('Your content is published again'),
      'field_referenced_entity_label' => $entity->label(),
    ]);
  }

}
