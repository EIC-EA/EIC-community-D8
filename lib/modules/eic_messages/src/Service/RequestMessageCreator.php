<?php

namespace Drupal\eic_messages\Service;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\BlockRequestHandler;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\eic_flags\Service\TransferOwnershipRequestHandler;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\flag\FlaggingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message creator service for request flags.
 */
class RequestMessageCreator implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The request handler collector service.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  private $collector;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * The EIC user helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  private $userHelper;

  /**
   * The message bus service.
   *
   * @var \Drupal\eic_messages\Service\MessageBusInterface
   */
  private $messageBus;

  /**
   * RequestMessageCreator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\eic_user\UserHelper $user_helper
   *   The EIC user helper service.
   * @param \Drupal\eic_messages\Service\MessageBusInterface $message_bus
   *   The message bus service.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The request handler collector service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    UserHelper $user_helper,
    MessageBusInterface $message_bus,
    RequestHandlerCollector $collector,
    RendererInterface $renderer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->userHelper = $user_helper;
    $this->messageBus = $message_bus;
    $this->collector = $collector;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eic_user.helper'),
      $container->get('eic_messages.message_bus'),
      $container->get('eic_flags.handler_collector'),
      $container->get('renderer')
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
      $this->getLogger('eic_messages')->warning(
        'Invalid type @type provided on request insert',
        ['@type' => $type]
      );

      return;
    }

    $message_name = $handler->getMessageByAction(RequestStatus::OPEN);
    if (!$message_name) {
      $this->getLogger('eic_messages')->warning(
        'Message does not exists for action insert'
      );

      return;
    }

    // Transfer ownership request messages are handled separately.
    if ($type === RequestTypes::TRANSFER_OWNERSHIP) {
      $this->transferOwnershipRequestInsertClose($flag, $entity, $handler);
      return;
    }

    // Prepare messages to SA/CA.
    foreach ($this->userHelper->getSitePowerUsers() as $uid) {
      $this->messageBus->dispatch(
        [
          'template' => $message_name,
          'field_message_subject' => $this->t(
            'New @type request for @label',
            ['@type' => $handler->getType(), '@label' => $entity->label()]
          ),
          'field_referenced_flag' => $flag,
          'uid' => $uid,
        ]
      );
    }
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
      $this->getLogger('eic_messages')->warning(
        'Invalid type @type provided on request close',
        ['@type' => $type]
      );

      return;
    }

    switch ($type) {
      case RequestTypes::BLOCK:
        // Block request messages are handled separately.
        $this->blockRequestClose($flagging, $entity, $handler);
        return;

      case RequestTypes::TRANSFER_OWNERSHIP:
        // Transfer ownership request messages are handled separately.
        $this->transferOwnershipRequestInsertClose($flagging, $entity, $handler);
        return;

    }

    /** @var \Drupal\user\UserInterface[] $to */
    $to = [$entity->getOwner(), $flagging->getOwner()];
    $response = $flagging->get('field_request_status')->value;
    $message_name = $handler->getMessageByAction($response);
    if (!$message_name) {
      $this->getLogger('eic_messages')->warning(
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
            $handler,
            $response
          ),
          'field_referenced_flag' => $flagging,
          'uid' => $user->id(),
        ]
      );

      if ($handler->getType() === RequestTypes::DELETE && RequestStatus::ACCEPTED === $response) {
        // For accepted delete requests things are a bit more different. Since it is a hard delete
        // the entity is lost forever. This means tokens won't return a valid value anymore.
        // We have to render the content and store it before the entity is gone.
        $content = $this->getRenderedContent($message);
        $message->set('field_rendered_content',
          ['value' => $content, 'format' => 'full_html']);
      }

      $this->messageBus->dispatch($message);
    }
  }

  /**
   * Gets the request response subject depending on response type.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param \Drupal\eic_flags\Service\HandlerInterface $handler
   *   The request service handler.
   * @param string $response
   *   The response type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The translated subject.
   */
  private function getResponseSubject(
    ContentEntityInterface $entity,
    HandlerInterface $handler,
    string $response
  ) {
    $operation = $handler->getType() === RequestTypes::DELETE ? 'deletion' : 'archival';

    switch ($response) {
      case RequestStatus::DENIED:
        return $this->t(
          '@operation request for @label denied',
          ['@label' => $entity->label(), '@operation' => ucfirst($operation)]
        );

      case RequestStatus::ACCEPTED:
        return $this->t(
          '@operation request for @type accepted',
          [
            '@label' => $entity->label(),
            '@operation' => ucfirst($operation),
            '@type' => $entity->getEntityTypeId(),
          ]
        );

      case RequestStatus::ARCHIVED:
        return $this->t(
          '@operation request for @label denied and the content has been archived instead',
          ['@label' => $entity->label(), '@operation' => ucfirst($operation)]
        );

      default:
        return NULL;

    }
  }

  /**
   * Gets the rendered entity to save in the message.
   *
   * @param \Drupal\Core\Entity\EntityInterface $message
   *   The message being created.
   *
   * @return string
   *   The rendered markup.
   */
  private function getRenderedContent(EntityInterface $message) {
    $view_builder = $this->entityTypeManager->getViewBuilder('message');
    $build = $view_builder->view($message, 'pre_render');

    return $build['partial_0']['#markup'];
  }

  /**
   * Handles message notification for closed block requests.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The request flag.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param \Drupal\eic_flags\Service\BlockRequestHandler $handler
   *   The block request handler service.
   */
  private function blockRequestClose(
    FlaggingInterface $flagging,
    ContentEntityInterface $entity,
    BlockRequestHandler $handler
  ) {
    $response = $flagging->get('field_request_status')->value;
    $message_name = $handler->getMessageByAction($response);
    $to = [];

    switch ($entity->getEntityTypeId()) {
      case 'group':
        $owners = $entity->getMembers(EICGroupsHelper::GROUP_OWNER_ROLE);

        // If group has no owner, we don't send out any notification.
        if (empty($owners)) {
          return;
        }

        // We need to map the membership into an array of user entities.
        $to = array_map(
          function ($owner) {
            return $owner->getUser();
          },
          $owners
        );

        break;

      default:
        $to[] = $entity->getOwner();
        break;
    }

    foreach ($to as $user) {
      $message = $this->entityTypeManager->getStorage('message')->create(
        [
          'template' => $message_name,
          'field_referenced_flag' => $flagging,
          'uid' => $user->id(),
        ]
      );

      $this->messageBus->dispatch($message);
    }
  }

  /**
   * Handles message notification for open/closed transfer ownership requests.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The request flag.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param \Drupal\eic_flags\Service\TransferOwnershipRequestHandler $handler
   *   The transfer ownership request handler service.
   */
  private function transferOwnershipRequestInsertClose(
    FlaggingInterface $flagging,
    ContentEntityInterface $entity,
    TransferOwnershipRequestHandler $handler
  ) {
    $response = $flagging->get('field_request_status')->value;
    $message_name = $handler->getMessageByAction($response);
    if (!$message_name) {
      $this->getLogger('eic_messages')->warning(
        'Message does not exists for response type @response',
        ['@response' => $response]
      );

      return;
    }

    $new_owner = $flagging->get('field_new_owner_ref')->entity;

    $message = [
      'template' => $message_name,
      'field_referenced_flag' => $flagging,
      'uid' => $new_owner->id(),
    ];

    // For open requests, we show accept and deny links in the notification.
    if ($response === RequestStatus::OPEN) {
      $accept_url = $entity->toUrl('user-close-request')
        ->setRouteParameter('request_type', $handler->getType())
        ->setRouteParameter('response', RequestStatus::ACCEPTED);
      $deny_url = $entity->toUrl('user-close-request')
        ->setRouteParameter('request_type', $handler->getType())
        ->setRouteParameter('response', RequestStatus::DENIED);
      $message['field_request_accept_url'] = [
        'uri' => $accept_url->toString(),
      ];
      $message['field_request_deny_url'] = [
        'uri' => $deny_url->toString(),
      ];
    }
    else {
      $message['uid'] = $flagging->getOwner()->id();
    }

    switch ($entity->getEntityTypeId()) {
      case 'group_content':
        $group = $entity->getGroup();
        $message['field_entity_type'] = $group->getEntityType()->getSingularLabel();
        $message['field_referenced_entity_label'] = $group->label();
        $message['field_entity_url'] = [
          'uri' => $group->toUrl()->toString(),
        ];
        break;

      default:
        $message['field_entity_type'] = $entity->getEntityType()->getSingularLabel();
        $message['field_referenced_entity_label'] = $entity->label();
        $message['field_entity_url'] = [
          'uri' => $entity->toUrl()->toString(),
        ];
        break;
    }

    $this->messageBus->dispatch($message);
  }

}
