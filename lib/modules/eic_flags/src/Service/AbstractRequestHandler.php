<?php

namespace Drupal\eic_flags\Service;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\RequestStatus;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Entity\Flagging;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Drupal\user\Entity\User;
use InvalidArgumentException;

/**
 * Class AbstractRequestHandler
 *
 * @package Drupal\eic_flags\Service\Handler
 */
abstract class AbstractRequestHandler implements HandlerInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * AbstractRequestHandler constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\flag\FlagService $flag_service
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    FlagService $flag_service,
    ModerationInformationInterface $moderation_information
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  abstract function accept(
    FlaggingInterface $flagging, ContentEntityInterface $content_entity,
    string $reason
  );

  /**
   * {@inheritdoc}
   */
  public function deny(
    FlaggingInterface $flagging, ContentEntityInterface $content_entity,
    string $reason
  ) {
    $this->moduleHandler->invokeAll(
      'request_close', [
        $flagging,
        $content_entity,
        RequestStatus::DENIED,
        $reason,
      ]
    );

    $flagging->set('field_request_status', RequestStatus::DENIED);
    $flagging->save();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applyFlag(ContentEntityInterface $entity, string $reason) {
    $support_entity_types = $this->getSupportedEntityTypes();
    // Entity type is not supported
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
        $entity, $current_user
      )) {
      return NULL;
    }

    $flag = $this->entityTypeManager->getStorage('flagging')->create(
      [
        'uid' => $current_user->id(),
        'session_id' => NULL,
        'flag_id' => $flag->id(),
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'global' => $flag->isGlobal(),
      ]
    );

    $flag->set('field_request_reason', $reason);
    $flag->set('field_request_status', RequestStatus::OPEN);
    $flag->save();

    return $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    return [];
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
    ContentEntityInterface $entity,
    AccountInterface $user
  ) {
    $supported_entity_types = $this->getSupportedEntityTypes();
    if (!isset($supported_entity_types[$entity->getEntityTypeId()])) {
      throw new InvalidArgumentException('Invalid entity type');
    }

    $flagging_ids = $this->entityTypeManager->getStorage('flagging')
      ->getQuery()
      ->condition('field_request_status', RequestStatus::OPEN)
      ->condition(
        'flag_id', $supported_entity_types[$entity->getEntityTypeId()]
      )
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('uid', $user->id())
      ->execute();

    return !empty($flagging_ids);
  }

}
