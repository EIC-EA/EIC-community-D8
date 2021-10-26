<?php

namespace Drupal\eic_messages\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message creator class for comments.
 *
 * @package Drupal\eic_messages
 */
class CommentMessageCreator implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * The EIC Content helper service.
   *
   * @var \Drupal\eic_content\EICContentHelperInterface
   */
  private $contentHelper;

  /**
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   * @param \Drupal\eic_content\EICContentHelperInterface $content_helper
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   */
  public function __construct(
    RouteMatchInterface $route_match,
    AccountProxyInterface $account,
    EICContentHelperInterface $content_helper,
    MessageBusInterface $message_bus
  ) {
    $this->routeMatch = $route_match;
    $this->currentUser = $account;
    $this->contentHelper = $content_helper;
    $this->messageBus = $message_bus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('eic_content.helper'),
      $container->get('eic_messages.message_bus')
    );
  }

  /**
   * Creates an activity stream message for a comment that belongs to a group.
   *
   * @param \Drupal\comment\CommentInterface $entity
   *   The group having this content.
   * @param string $operation
   *   The type of the operation. See ActivityStreamOperationTypes.
   */
  public function createCommentActivity(
    CommentInterface $entity,
    string $operation
  ) {
    if (!in_array($this->routeMatch->getRouteName(),
      ['comment.reply', 'entity.comment.edit_form'])) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $commented_entity */
    $commented_entity = $entity->getCommentedEntity();
    $group_content = $this->contentHelper->getGroupContentByEntity($commented_entity);
    if (empty($group_content)) {
      return;
    }

    $group_content = reset($group_content);
    $this->messageBus->dispatch([
      'template' => ActivityStreamMessageTemplates::getTemplate($entity),
      'field_referenced_comment' => $entity,
      'field_referenced_node' => $commented_entity,
      'field_entity_type' => $entity->bundle(),
      'field_operation_type' => $operation,
      'field_group_ref' => $group_content->getGroup(),
    ]);
  }

}
