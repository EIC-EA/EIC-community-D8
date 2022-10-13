<?php

namespace Drupal\eic_flags\Service;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_moderation\ModerationHelper;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service that provides logic to flag a blocked entity.
 *
 * @package Drupal\eic_flags\Service
 */
class BlockRequestHandler extends AbstractRequestHandler {

  /**
   * The content moderation blocked state key.
   */
  const ENTITY_BLOCKED_STATE = 'blocked';

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
   * @param \Drupal\eic_moderation\ModerationHelper $moderation_helper
   *   The EIC Moderation helper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    FlagService $flag_service,
    ModerationInformationInterface $moderation_information,
    ModerationHelper $moderation_helper,
    RequestStack $request_stack
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
    $this->moderationInformation = $moderation_information;
    $this->moderationHelper = $moderation_helper;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return RequestTypes::BLOCK;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    return [
      RequestStatus::ACCEPTED => 'notify_block_request_accepted',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    return [
      'group' => 'request_block_group',
    ];
  }

  /**
   * Blocks the given entity..
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The concerned entity.
   */
  public function blockEntity(ContentEntityInterface $content_entity) {
    if ($this->moderationInformation->isModeratedEntity($content_entity)) {
      $content_entity->set('moderation_state', self::ENTITY_BLOCKED_STATE);
    }
    else {
      $content_entity->set('status', FALSE);
    }

    $content_entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason, int $request_timeout = 0) {
    $flag = parent::applyFlag($entity, $reason);

    // Automatically accepts the request.
    $this->accept($flag, $entity);

    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlagAlter(FlaggingInterface $flag) {
    $flag->set('field_request_status', RequestStatus::ACCEPTED);
    return parent::applyFlagAlter($flag);
  }

  /**
   * {@inheritdoc}
   */
  public function accept(
    FlaggingInterface $flagging,
    ContentEntityInterface $content_entity
  ) {
    $this->blockEntity($content_entity);
    $this->closeRequest(
      $flagging,
      $content_entity,
      RequestStatus::ACCEPTED,
      $flagging->get('field_request_reason')->value
    );
  }

  /**
   * {@inheritdoc}
   */
  public function canRequest(
    AccountInterface $account,
    ContentEntityInterface $entity
  ) {
    // Default access.
    $access = AccessResult::forbidden();

    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);

    $access->addCacheableDependency($account)
      ->addCacheableDependency($entity);

    if (!$workflow) {
      return $access;
    }

    $access->addCacheableDependency($workflow);

    if (!$workflow->getTypePlugin()->getState(self::ENTITY_BLOCKED_STATE)) {
      return $access;
    }

    $moderation_state = $entity->get('moderation_state')->value;

    // If entity is already blocked, we can't block it again and therefore we
    // return access denied.
    if ($moderation_state === self::ENTITY_BLOCKED_STATE) {
      return $access;
    }

    // Make sure entity can transition to blocked state, otherwise we return
    // access denied.
    if (!$workflow->getTypePlugin()->hasTransitionFromStateToState($moderation_state, self::ENTITY_BLOCKED_STATE)) {
      return $access;
    }

    $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($moderation_state, self::ENTITY_BLOCKED_STATE);

    // Make sure the user can use the blocked transition, otherwise we return
    // access denied.
    if (!$account->hasPermission("use {$workflow->id()} transition {$transition->id()}")) {
      return $access;
    }

    switch ($entity->getEntityTypeId()) {
      case 'group':
        if (!$entity->access('update', $account)) {
          break;
        }

        // The user can update the group so we allow access to block it.
        $access = AccessResult::allowed()
          ->addCacheableDependency($account)
          ->addCacheableDependency($entity)
          ->addCacheableDependency($workflow);
        break;

      case 'node':
        if (!$entity->access('update', $account)) {
          break;
        }

        $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity);

        if (!empty($group_contents)) {
          $group_content = reset($group_contents);
          $group = $group_content->getGroup();

          if (!$group->access('update', $account)) {
            break;
          }
        }

        // The user can update the node so we allow access to block it.
        $access = AccessResult::allowed()
          ->addCacheableDependency($account)
          ->addCacheableDependency($entity)
          ->addCacheableDependency($workflow);

        if (isset($group)) {
          $access->addCacheableDependency($group);
        }
        break;

    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getActions(ContentEntityInterface $entity) {
    $support_entity_types = $this->getSupportedEntityTypes();

    // Entity type is not supported.
    if (!array_key_exists($entity->getEntityTypeId(), $support_entity_types)) {
      return [];
    }

    return [
      'request_block' => [
        'title' => $this->t('Block'),
        'url' => $entity->toUrl('new-request')
          ->setRouteParameter(
            'destination',
            $this->currentRequest->getRequestUri())
          ->setRouteParameter('request_type', RequestTypes::BLOCK),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function canLogRequest() {
    return FALSE;
  }

}
