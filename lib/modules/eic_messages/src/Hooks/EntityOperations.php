<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_messages\Service\MessageCreatorBase;
use Drupal\eic_user\UserHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityUpdate
 *
 * @package Drupal\eic_messages\Hooks
 */
class EntityOperations extends MessageCreatorBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * EntityUpdate constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eic_messages\MessageHelper $eic_messages_helper
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessageHelper $eic_messages_helper,
    UserHelper $eic_user_helper,
    ModerationInformationInterface $moderationInformation
  ) {
    parent::__construct(
      $entity_type_manager,
      $eic_messages_helper,
      $eic_user_helper
    );

    $this->moderationInformation = $moderationInformation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_messages.helper'),
      $container->get('eic_user.helper'),
      $container->get('content_moderation.moderation_information')
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

    $message = $this->entityTypeManager->getStorage('message')
      ->create([
        'template' => 'notify_archived_republished',
        'uid' => $entity->getOwnerId(),
        'field_message_subject' => $this->t('Your content is published again'),
        'field_referenced_entity_label' => $entity->label(),
      ]);

    $message->save();
    $this->processMessages([$message]);
  }

}
