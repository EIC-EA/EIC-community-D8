<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlaggingInterface;

/**
 * Interface HandlerInterface
 *
 * @package Drupal\eic_flags\Service\Handler
 */
interface HandlerInterface {

  /**
   * Returns the type of request the handler is for.
   *
   * @return string
   */
  public function getType();

  /**
   * Method called before the action is executed.
   * Can be used for sending notifications, triggering hooks, events, etc.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   * @param string $response
   * @param string $reason
   *
   * @return void
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
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *
   * @return bool
   */
  public function deny(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  );

  /**
   * Starts the 'accept' workflow for a request.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *
   * @return bool
   */
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  );

  /**
   * Applies the given the corresponding flag to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $reason
   *
   * @return bool|NULL
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason);

  /**
   * Returns an array of supported entity types.
   *
   * @return array
   */
  public function getSupportedEntityTypes();

  /**
   * Returns the flag id for the given entity type.
   *
   * @param string $entity_id
   *
   * @return string
   */
  public function getFlagId(string $entity_id);

  /**
   * Define if the given entity type is supported by the handler.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $contentEntity
   *
   * @return bool
   */
  public function supports(ContentEntityInterface $contentEntity);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *
   * @return array
   */
  public function getOpenRequests(
    ContentEntityInterface $content_entity,
    ?AccountInterface $account = NULL
  );

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return bool
   */
  public function hasOpenRequest(
    ContentEntityInterface $content_entity,
    AccountInterface $account
  );

  /**
   * Return an array of supported actions which are basically responses.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return array
   */
  public function getActions(ContentEntityInterface $entity);

  /**
   * Returns the list of matching message templates for the request type.
   *
   * @return array
   */
  public function getMessages();

  /**
   * Returns the name of the message template to use for the given action
   *
   * @param string $action
   *
   * @return string|NULL
   */
  public function getMessageByAction(string $action);

}
