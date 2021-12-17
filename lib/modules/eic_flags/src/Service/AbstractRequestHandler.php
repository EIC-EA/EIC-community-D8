<?php

namespace Drupal\eic_flags\Service;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Entity\Flagging;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service handler wrapper that provides logic for entity request flags.
 *
 * @package Drupal\eic_flags\Service\Handler
 */
abstract class AbstractRequestHandler implements HandlerInterface {

  use StringTranslationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Flag service provided by the flag module.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * Core's moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $currentRequest;

  /**
   * AbstractRequestHandler constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\flag\FlagService $flag_service
   *   Flag service provided by the flag module.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   Core's moderation information service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    FlagService $flag_service,
    ModerationInformationInterface $moderation_information,
    RequestStack $request_stack
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
    $this->moderationInformation = $moderation_information;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  abstract public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  );

  /**
   * {@inheritdoc}
   */
  abstract public function getSupportedEntityTypes();

  /**
   * {@inheritdoc}
   */
  abstract public function getMessages();

  /**
   * {@inheritdoc}
   */
  public function closeRequest(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity,
    string $response,
    string $reason
  ) {
    $account_proxy = \Drupal::currentUser();
    if (!$account_proxy->isAuthenticated()) {
      throw new \InvalidArgumentException(
        'You must be authenticated to do this!'
      );
    }

    $now = DrupalDateTime::createFromTimestamp(time());
    $current_user = User::load($account_proxy->id());
    $flagging->set('field_request_moderator', $current_user);

    // For some requests, field request response is not presented.
    if ($flagging->hasField('field_request_response')) {
      $flagging->set('field_request_response', $reason);
    }

    $flagging->set('field_request_status', $response);
    $flagging->set(
      'field_request_closed_date',
      $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)
    );
    $flagging->save();

    $this->moduleHandler->invokeAll(
      'request_close',
      [
        $flagging,
        $content_entity,
        $this->getType(),
      ]
    );

    // For accepted requests we create a log entry.
    if ($response === RequestStatus::ACCEPTED) {
      $log = $this->entityTypeManager->getStorage('message')
        ->create([
          'template' => 'log_request_accepted',
          'field_referenced_flag' => $flagging,
          'uid' => $flagging->getOwnerId(),
        ]);

      $log->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deny(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    // Currently does nothing, this will change.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason, int $request_timeout = 0) {
    $support_entity_types = $this->getSupportedEntityTypes();
    // Entity type is not supported.
    if (!array_key_exists($entity->getEntityTypeId(), $support_entity_types)) {
      return NULL;
    }

    $current_user = \Drupal::currentUser();
    if (!$current_user->isAuthenticated()) {
      return NULL;
    }

    $current_user = User::load($current_user->id());
    $flag = $this->flagService->getFlagById(
      $support_entity_types[$entity->getEntityTypeId()]
    );

    if (!$flag instanceof Flag || $this->hasOpenRequest(
        $entity,
        $current_user
      )) {
      return NULL;
    }

    $flag = $this->entityTypeManager->getStorage('flagging')->create([
      'uid' => $current_user->id(),
      'session_id' => NULL,
      'flag_id' => $flag->id(),
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'global' => $flag->isGlobal(),
    ]);

    $flag->set('field_request_reason', $reason);
    $flag->set('field_request_status', RequestStatus::OPEN);

    if ($flag->hasField(HandlerInterface::REQUEST_TIMEOUT_FIELD)) {
      $flag->set(HandlerInterface::REQUEST_TIMEOUT_FIELD, $request_timeout);
    }

    // Alters flag before saving it.
    $flag = $this->applyFlagAlter($flag);

    $flag->save();

    // Calls post save method to apply logic after saving flag in the database.
    $flag = $this->applyFlagPostSave($flag);

    $this->moduleHandler->invokeAll(
      'request_insert',
      [
        $flag,
        $entity,
        $this->getType(),
      ]
    );

    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlagAlter(FlaggingInterface $flag) {
    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlagPostSave(FlaggingInterface $flag) {
    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function supports(ContentEntityInterface $contentEntity) {
    return in_array(
      $contentEntity->getEntityTypeId(),
      array_keys($this->getSupportedEntityTypes())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagId(string $entity_type) {
    return $this->getSupportedEntityTypes()[$entity_type] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasOpenRequest(
    ContentEntityInterface $content_entity,
    AccountInterface $user
  ) {
    return !empty($this->getOpenRequests($content_entity, $user));
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenRequests(
    ContentEntityInterface $content_entity,
    ?AccountInterface $user = NULL
  ) {
    $supported_entity_types = $this->getSupportedEntityTypes();
    if (!isset($supported_entity_types[$content_entity->getEntityTypeId()])) {
      throw new \InvalidArgumentException('Invalid entity type');
    }

    $query = $this->entityTypeManager->getStorage('flagging')
      ->getQuery()
      ->condition('field_request_status', RequestStatus::OPEN)
      ->condition(
        'flag_id',
        $supported_entity_types[$content_entity->getEntityTypeId()]
      )
      ->condition('entity_type', $content_entity->getEntityTypeId())
      ->condition('entity_id', $content_entity->id());

    if ($user instanceof AccountInterface) {
      $query->condition('uid', $user->id());
    }

    $flagging_ids = $query->execute();
    if (empty($flagging_ids)) {
      return NULL;
    }

    return Flagging::loadMultiple($flagging_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getActions(ContentEntityInterface $entity) {
    return [
      'deny_request' => [
        'title' => $this->t('Deny'),
        'url' => $entity->toUrl('close-request')
          ->setRouteParameter('request_type', $this->getType())
          ->setRouteParameter('response', RequestStatus::DENIED)
          ->setRouteParameter(
            'destination',
            $this->currentRequest->getRequestUri()),
      ],
      'accept_request' => [
        'title' => $this->t('Accept'),
        'url' => $entity->toUrl('close-request')
          ->setRouteParameter('request_type', $this->getType())
          ->setRouteParameter('response', RequestStatus::ACCEPTED)
          ->setRouteParameter(
            'destination',
            $this->currentRequest->getRequestUri()),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageByAction(string $action) {
    $messages = $this->getMessages();

    return $messages[$action] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function canRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  ) {
    if (!$account->isAuthenticated()) {
      return AccessResult::forbidden();
    }

    $supported_entities = array_keys($this->getSupportedEntityTypes());
    if (!in_array($entity->getEntityTypeId(), $supported_entities)) {
      return AccessResult::forbidden();
    }

    // We allow requests only for certain users having the make permission.
    if (!$account->hasPermission('make ' . $this->getType() . ' request')) {
      return AccessResult::forbidden();
    }

    // For groups, the user must be GM/GO/GA or SA/SCM.
    if ($entity instanceof GroupInterface) {
      /** @var \Drupal\group\Entity\GroupInterface $entity */
      $user_roles = $account->getRoles(TRUE);
      $allowed_global_roles = [
        UserHelper::ROLE_CONTENT_ADMINISTRATOR,
        UserHelper::ROLE_SITE_ADMINISTRATOR,
      ];

      $group_membership = $entity->getMember($account);
      $user_group_roles = $group_membership instanceof GroupMembership
        ? array_keys($group_membership->getRoles())
        : [];
      $allowed_group_roles = [
        EICGroupsHelper::GROUP_MEMBER_ROLE,
        EICGroupsHelper::GROUP_ADMINISTRATOR_ROLE,
        EICGroupsHelper::GROUP_OWNER_ROLE,
      ];

      if (empty(array_intersect($user_roles, $allowed_global_roles))
        && empty(array_intersect($user_group_roles, $allowed_group_roles))) {
        return AccessResult::forbidden();
      }
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function canCloseRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  ) {
    // Default access.
    $access = AccessResult::forbidden();

    if ($account->hasPermission('manage archival deletion requests')) {
      $access = AccessResult::allowed();
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedResponsesForClosedRequests() {
    return [
      RequestStatus::DENIED,
      RequestStatus::ACCEPTED,
      RequestStatus::ARCHIVED,
    ];
  }

}
