<?php

namespace Drupal\eic_flags\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_flags\RequestStatus;
use Drupal\flag\Entity\Flag;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\FlagService;
use Drupal\user\Entity\User;

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
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * AbstractRequestHandler constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\flag\FlagService $flagService
   */
  public function __construct(ModuleHandlerInterface $module_handler, FlagService $flagService) {
    $this->moduleHandler = $module_handler;
    $this->flagService = $flagService;
  }

  /**
   * {@inheritdoc}
   */
  abstract function accept(FlaggingInterface $flagging, ContentEntityInterface $content_entity, string $reason);

  /**
   * {@inheritdoc}
   */
  public function deny(FlaggingInterface $flagging, ContentEntityInterface $content_entity, string $reason) {
    $this->moduleHandler->invokeAll('request_close', [
      $flagging,
      $content_entity,
      RequestStatus::DENIED,
      $reason,
    ]);

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
    $flag = $this->flagService->getFlagById($support_entity_types[$entity->getEntityTypeId()]);
    if (!$flag instanceof Flag || $flag->isFlagged($entity, $current_user)) {
      return NULL;
    }

    $flag = $this->flagService->flag($flag, $entity, $current_user);
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

}
