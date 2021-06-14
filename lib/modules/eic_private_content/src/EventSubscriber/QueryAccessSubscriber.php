<?php

namespace Drupal\eic_private_content\EventSubscriber;

use Drupal\eic_private_content\PrivateContentConst;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides an Event Subscriber for entity query access events.
 */
class QueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity.query_access.node' => 'onNodeQueryAccess',
    ];
  }

  /**
   * Modifies node access conditions based on the privacy field.
   *
   * If a node is set to private, only users with "view private content"
   * permission should be able to view the node. Otherwise users won't be able
   * to view the node in lists except if the user is the owner of the node.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The query access event.
   */
  public function onNodeQueryAccess(QueryAccessEvent $event) {
    $conditions = $event->getConditions();
    $account = $event->getAccount();

    if (!$account->hasPermission('view private content')) {
      $conditions->addCondition((new ConditionGroup('OR'))
        // Access is allowed if privacy field is empty.
        ->addCondition(PrivateContentConst::FIELD_NAME, NULL, 'IS NULL')
        // Access is allowed node is not private.
        ->addCondition(PrivateContentConst::FIELD_NAME, 0)
        // Allow owners to see their nodes.
        ->addCondition('uid', $account->id())
      );
    }
  }

}
