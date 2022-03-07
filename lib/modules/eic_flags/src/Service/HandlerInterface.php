<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlaggingInterface;

/**
 * Provides an interface for request handlers.
 *
 * @package Drupal\eic_flags\Service\Handler
 */
interface HandlerInterface {

  const REQUEST_TIMEOUT_FIELD = 'field_request_timeout';

  /**
   * Returns the type of request the handler is for.
   *
   * @return string
   *   The handler type.
   */
  public function getType();

  /**
   * Method called before the action is executed.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flag object representing the request.
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   * @param string $response
   *   The response given when closing the request.
   * @param string $reason
   *   The reason given when opening the request.
   */
  public function closeRequest(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity,
    string $response,
    string $reason
  );

  /**
   * Starts the 'deny' workflow for a request.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flag object representing the request.
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   *
   * @return bool
   *   Result of the deny operation.
   */
  public function deny(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  );

  /**
   * Starts the 'accept' workflow for a request.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   Flag object representing the request.
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   *
   * @return bool
   *   Result of the accept operation.
   */
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  );

  /**
   * Starts the 'cancel' workflow for a request.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   Flag object representing the request.
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   *
   * @return bool
   *   Result of the accept operation.
   */
  public function cancel(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  );

  /**
   * Applies the given the corresponding flag to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The concerned entity.
   * @param string $reason
   *   Reason given when opening the request.
   * @param int $request_timeout
   *   (optional) Number of days the request will remain open.
   *
   * @return \Drupal\flag\FlaggingInterface|null
   *   Result of the operation.
   */
  public function applyFlag(
    ContentEntityInterface $entity,
    string $reason,
    int $request_timeout = 0
  );

  /**
   * Alters the given the corresponding flag before saving it.
   *
   * @param \Drupal\flag\FlaggingInterface $flag
   *   The flagging entity to alter.
   *
   * @return \Drupal\flag\FlaggingInterface|null
   *   The altered flagging entity.
   */
  public function applyFlagAlter(FlaggingInterface $flag);

  /**
   * Post save method to apply logic after saving flag in the database.
   *
   * @param \Drupal\flag\FlaggingInterface $flag
   *   The flagging entity to alter.
   *
   * @return \Drupal\flag\FlaggingInterface|null
   *   The altered flagging entity.
   */
  public function applyFlagPostSave(FlaggingInterface $flag);

  /**
   * Returns an array of supported entity types.
   *
   * @return array
   *   Array of supported entity types for the request type.
   */
  public function getSupportedEntityTypes();

  /**
   * Returns the flag id for the given entity type.
   *
   * @param string $entity_id
   *   The entity id.
   *
   * @return string
   *   The id of the flag.
   */
  public function getFlagId(string $entity_id);

  /**
   * Define if the given entity type is supported by the handler.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $contentEntity
   *   The concerned entity.
   *
   * @return bool
   *   Whether or not this entity is supported.
   */
  public function supports(ContentEntityInterface $contentEntity);

  /**
   * Returns the list of open requests for the given entity and account.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Current account object.
   *
   * @return array
   *   List of open requests for the given account and entity.
   */
  public function getOpenRequests(
    ContentEntityInterface $content_entity,
    ?AccountInterface $account = NULL
  );

  /**
   * Whether or not we have open requests.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current account object.
   *
   * @return bool
   *   Whether or not we have open requests.
   */
  public function hasOpenRequest(
    ContentEntityInterface $content_entity,
    AccountInterface $account
  );

  /**
   * Return an array of supported actions which are basically responses.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The concerned entity.
   *
   * @return array
   *   Available actions for the entity.
   */
  public function getActions(ContentEntityInterface $entity);

  /**
   * Returns the list of matching message templates for the request type.
   *
   * @return array
   *   List of supported messages.
   */
  public function getMessages();

  /**
   * Returns the name of the message template to use for the given action.
   *
   * @param string $action
   *   The action type (accept, create, etc.).
   *
   * @return string|null
   *   The message template id.
   */
  public function getMessageByAction(string $action);

  /**
   * Whether or not the requests can be made by the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Currently logged in account, anonymous users are not allowed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity against the access check is made.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function canRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  );

  /**
   * Whether or not the requests can be closed by the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Currently logged in account, anonymous users are not allowed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity against the access check is made.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function canCloseRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  );

  /**
   * Whether or not the requests can be cancelled by the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Currently logged in account, anonymous users are not allowed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity against the access check is made.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function canCancelRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  );

  /**
   * Get supported response types for closed requests.
   *
   * @return array
   *   Array of supported response types.
   */
  public function getSupportedResponsesForClosedRequests();

  /**
   * Whether or not the request flag has been expired.
   *
   * @param \Drupal\flag\FlaggingInterface $flag
   *   The flagging entity to alter.
   *
   * @return bool
   *   TRUE if has expiration.
   */
  public function hasExpiration(FlaggingInterface $flag);

  /**
   * Whether or not the request flag has been expired.
   *
   * @param \Drupal\flag\FlaggingInterface $flag
   *   The flagging entity to alter.
   *
   * @return bool
   *   TRUE if request has expired.
   */
  public function hasExpired(FlaggingInterface $flag);

  /**
   * Triggers request timeout.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flag object representing the request.
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   */
  public function requestTimeout(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  );

  /**
   * Checks if a request should be logged as critical action.
   *
   * @return bool
   *   TRUE if the request should be logged.
   */
  public function canLogRequest();

}
