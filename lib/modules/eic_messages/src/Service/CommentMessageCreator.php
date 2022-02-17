<?php

namespace Drupal\eic_messages\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Drupal\eic_messages\Hooks\MessageTokens;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\eic_messages\Util\NotificationMessageTemplates;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message creator class for comments.
 *
 * @package Drupal\eic_messages
 */
class CommentMessageCreator implements ContainerInjectionInterface {

  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The EIC content helper service.
   *
   * @var \Drupal\eic_content\EICContentHelperInterface
   */
  private $contentHelper;

  /**
   * The message bus service.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * Constructs a new CommentMessageCreator object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route.
   * @param \Drupal\eic_content\EICContentHelperInterface $content_helper
   *   The EIC content helper service.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The message bus service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EICContentHelperInterface $content_helper,
    MessageBusInterface $message_bus,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer
  ) {
    $this->routeMatch = $route_match;
    $this->contentHelper = $content_helper;
    $this->messageBus = $message_bus;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_content.helper'),
      $container->get('eic_messages.message_bus'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
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
      ['comment.reply', 'entity.comment.edit_form', 'eic_groups.discussion_add_comment'])) {
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

  /**
   * Creates a notification message for tagged users in a comment.
   *
   * @param \Drupal\comment\CommentInterface $entity
   *   The group having this content.
   */
  public function createCommentTaggedUsersNotification(
    CommentInterface $entity
  ) {
    // If there are no tagged users, we do nothing.
    if ($entity->get('field_tagged_users')->isEmpty()) {
      return;
    }

    // Get the comment notification teaser renderable array.
    $comment_teaser = $this->entityTypeManager->getViewBuilder('comment')
      ->view($entity, 'notification_teaser');
    // Get the rendered HTML markup of comment notification teaser.
    $rendered_comment_teaser = $this->renderer->render($comment_teaser);
    /** @var \Drupal\user\UserInterface[] $tagged_users */
    $tagged_users = $entity->get('field_tagged_users')->referencedEntities();
    // Creates a notification message for each tagged user.
    foreach ($tagged_users as $user) {
      $this->messageBus->dispatch([
        'template' => NotificationMessageTemplates::USER_TAGGED_ON_COMMENT,
        'uid' => $user->id(),
        'field_referenced_comment' => $entity,
        'field_event_executing_user' => $entity->getOwner(),
        MessageTokens::RENDERED_CONTENT_FIELD => $rendered_comment_teaser,
      ]);
    }
  }

}
