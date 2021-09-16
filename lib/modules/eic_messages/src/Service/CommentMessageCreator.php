<?php

namespace Drupal\eic_messages\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eic_content\EICContentHelperInterface;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\eic_user\UserHelper;

/**
 * Class CommentMessageCreator.
 */
class CommentMessageCreator extends MessageCreatorBase {

  /**
   * @var \Drupal\eic_content\EICContentHelperInterface
   */
  private $contentHelper;

  /**
   * CommentMessageCreator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eic_messages\MessageHelper $eic_messages_helper
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   * @param \Drupal\eic_content\EICContentHelperInterface $content_helper
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessageHelper $eic_messages_helper,
    UserHelper $eic_user_helper,
    EICContentHelperInterface $content_helper
  ) {
    parent::__construct($entity_type_manager, $eic_messages_helper, $eic_user_helper);

    $this->contentHelper = $content_helper;
  }

  /**
   * Creates an activity stream message for a comment on a content inside a group.
   *
   * @param CommentInterface $entity
   *   The group having this content.
   * @param string $operation
   *   The type of the operation. See ActivityStreamOperationTypes
   */
  public function createCommentActivity(
    CommentInterface $entity,
    string $operation
  ) {
    $route_match = \Drupal::routeMatch();
    if (!in_array($route_match->getRouteName(), ['comment.reply', 'entity.comment.edit_form'])) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $commented_entity */
    $commented_entity = $entity->getCommentedEntity();
    $group_content = $this->contentHelper->getGroupContentByEntity($commented_entity);
    if (empty($group_content)) {
      return;
    }

    $group_content = reset($group_content);
    $group = $group_content->getGroup();
    $message = \Drupal::entityTypeManager()->getStorage('message')->create([
      'template' => ActivityStreamMessageTemplates::getTemplate($entity),
      'field_referenced_comment' => $entity,
      'field_referenced_node' => $commented_entity,
      'field_entity_type' => $entity->bundle(),
      'field_operation_type' => $operation,
      'field_group_ref' => $group,
    ]);

    try {
      $message->save();
    } catch (\Exception $e) {
      $logger = $this->getLogger('eic_messages');
      $logger->error($e->getMessage());
    }
  }

}
