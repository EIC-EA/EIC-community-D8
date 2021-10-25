<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eic_flags\FlagType;
use Drupal\eic_message_subscriptions\MessageSubscriptionTypes;
use Drupal\eic_message_subscriptions\SubscriptionOperationTypes;
use Drupal\flag\FlaggingInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\message\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message creator class for group content.
 *
 * @package Drupal\eic_messages
 */
class NodeMessageCreator implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(AccountProxyInterface $account) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_user'));
  }

  /**
   * Creates a subscription message for a node with terms of interest.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param string $operation
   *   The type of the operations. See SubscriptionOperationTypes.
   */
  public function createTermsOfInterestNodeSubscription(
    ContentEntityInterface $entity,
    string $operation
  ) {
    $message = NULL;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        $message_type = $operation === SubscriptionOperationTypes::NEW_ENTITY
          ? MessageSubscriptionTypes::NODE_PUBLISHED
          : NULL;

        // We only create subscription message if the operation is 'created'.
        if (!$message_type) {
          break;
        }

        $message = Message::create([
          'template' => $message_type,
          'field_node_ref' => $entity,
        ]);

        $group_contents = GroupContent::loadByEntity($entity);

        if (!empty($group_contents)) {
          $group_content = reset($group_contents);
          $group = $group_content->getGroup();
          // Adds reference to group.
          $message->set('field_group_ref', $group);
        }

        // Set the owner of the message to the current user.
        $executing_user_id = $this->currentUser->id();
        $message->setOwnerId($executing_user_id);

        // Adds the reference to the user who created/updated the entity.
        if ($message->hasField('field_event_executing_user')) {
          $message->set('field_event_executing_user', $executing_user_id);
        }
        break;
    }

    return $message;
  }

  /**
   * Creates a subscription message to be sent when a user recommends a node.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging object.
   */
  public function createContentRecommendedSubscription(
    FlaggingInterface $flagging
  ) {
    $flag = $flagging->getFlag();
    $message = NULL;

    if ($flag->id() !== FlagType::RECOMMEND) {
      return $message;
    }

    // Gets the flagged entity.
    $flagged_entity = $flagging->getFlaggable();

    // Instantiates a new message entity.
    $message = Message::create([
      'template' => MessageSubscriptionTypes::CONTENT_RECOMMENDED,
      'field_referenced_node' => $flagged_entity,
    ]);

    // Adds the reference to the user who recommended the content.
    $message->set('field_event_executing_user', $flagging->getOwnerId());
    $message->setOwnerId($flagging->getOwnerId());

    return $message;
  }

}
