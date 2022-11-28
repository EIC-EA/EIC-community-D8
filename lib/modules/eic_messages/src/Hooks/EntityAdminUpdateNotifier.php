<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_content\EICContentHelper;
use Drupal\eic_messages\Service\MessageBusInterface;
use Drupal\eic_messages\Util\NotificationMessageTemplates;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityAdminUpdateNotifier
 *
 * @package Drupal\eic_messages\Hooks
 */
class EntityAdminUpdateNotifier implements ContainerInjectionInterface {

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  private $moderationInformation;

  /**
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\eic_content\EICContentHelper
   */
  private $contentHelper;

  /**
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(
    ModerationInformationInterface $moderationInformation,
    MessageBusInterface $message_bus,
    AccountProxyInterface $current_user,
    EICContentHelper $content_helper
  ) {
    $this->moderationInformation = $moderationInformation;
    $this->messageBus = $message_bus;
    $this->currentUser = $current_user;
    $this->contentHelper = $content_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('eic_messages.message_bus'),
      $container->get('current_user'),
      $container->get('eic_content.helper')
    );
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return void
   */
  public function __invoke(EntityInterface $entity) {
    if (!UserHelper::isPowerUser($this->currentUser) || !$entity->original->isPublished()) {
      return;
    }

    $author = $entity->getOwner();
    if ($author->id() === $this->currentUser->id()) {
      return;
    }

    $message = [
      'template' => NotificationMessageTemplates::CONTENT_UPDATE_BY_ADMIN,
      'uid' => $author->id(),
      'field_event_executing_user' => $this->currentUser->id(),
      'field_referenced_node' => $entity,
    ];

    $group_content = $this->contentHelper->getGroupContentByEntity($entity, [], ["group_node:{$entity->bundle()}"]);
    if (!empty($group_content)) {
      $group_content = reset($group_content);
      if ($group_content instanceof GroupContentInterface) {
        $message['field_group_ref'] = $group_content->getGroup();
      }
    }

    $this->messageBus->dispatch($message);
  }

}
