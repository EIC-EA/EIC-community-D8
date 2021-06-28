<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\eic_messages\MessageHelper;
use Drupal\eic_user\UserHelper;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RequestMessageCreator.
 */
class RequestMessageCreator extends MessageCreatorBase {

  use StringTranslationTrait;

  /**
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $collector;

  /**
   * RequestMessageCreator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\eic_messages\MessageHelper $eic_messages_helper
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessageHelper $eic_messages_helper,
    UserHelper $eic_user_helper,
    RequestHandlerCollector $collector
  ) {
    parent::__construct(
      $entity_type_manager,
      $eic_messages_helper,
      $eic_user_helper
    );

    $this->collector = $collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_messages.helper'),
      $container->get('eic_user.helper'),
      $container->get('eic_flags.handler_collector')
    );
  }

  /**
   * Implements hook_request_insert().
   */
  public function requestInsert(
    FlaggingInterface $flag,
    ContentEntityInterface $entity,
    string $type
  ) {
    $handler = $this->collector->getHandlerByType($type);
    if (!$handler instanceof HandlerInterface) {
      \Drupal::logger('eic_messages')->warning(
        'Invalid type @type provided on request insert',
        ['@type' => $type]
      );

      return;
    }

    $message_name = $handler->getMessageByAction(RequestStatus::OPEN);
    if (!$message_name) {
      \Drupal::logger('eic_messages')->warning(
        'Message does not exists for action insert'
      );

      return;
    }

    $messages = [];
    // Prepare messages to SA/CA.
    foreach ($this->eicUserHelper->getSitePowerUsers() as $uid) {
      $message = $this->entityTypeManager->getStorage('message')->create(
        [
          'template' => $message_name,
          'field_message_subject' => $this->t(
            'New @type request',
            ['@type' => $handler->getType()]
          ),
          'field_referenced_flag' => $flag,
          'uid' => $uid,
        ]
      );

      $message->save();

      $messages[] = $message;
    }

    $this->processMessages($messages);
  }

  /**
   * Implements hook_request_close().
   */
  public function requestClose(
    FlaggingInterface $flagging,
    ContentEntityInterface $entity,
    string $type
  ) {
    $handler = $this->collector->getHandlerByType($type);
    if (!$handler instanceof HandlerInterface) {
      \Drupal::logger('eic_messages')->warning(
        'Invalid type @type provided on request close',
        ['@type' => $type]
      );

      return;
    }

    /** @var \Drupal\user\UserInterface[] $to */
    $to = [$entity->getOwner(), $flagging->getOwner()];
    $response = $flagging->get('field_request_response')->value;
    $message_name = $handler->getMessageByAction($response);
    if (!$message_name) {
      \Drupal::logger('eic_messages')->warning(
        'Message does not exists for response type @response',
        ['@response' => $response]
      );

      return;
    }

    foreach ($to as $user) {
      $message = $this->entityTypeManager->getStorage('message')->create(
        [
          'template' => $message_name,
          'field_message_subject' => $this->getResponseSubject(
            $entity,
            $response
          ),
          'field_referenced_flag' => $flagging,
          'uid' => $user->id(),
        ]
      );

      $message->save();

      $messages[] = $message;
    }

    $this->processMessages($messages);
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $response
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   */
  private function getResponseSubject(
    ContentEntityInterface $entity,
    string $response
  ) {
    switch ($response) {
      case RequestStatus::DENIED:
        return $this->t(
          'Deletion request for @label denied',
          ['@label' => $entity->label()]
        );
      case RequestStatus::ACCEPTED:
        return $this->t(
          'Deletion request for @type accepted',
          ['@label' => $entity->label()]
        );
      case RequestStatus::ARCHIVED:
        return $this->t(
          'Deletion request for @label denied and the content has been archived instead',
          ['@label' => $entity->label()]
        );
      default:
        return NULL;
    }
  }

}
