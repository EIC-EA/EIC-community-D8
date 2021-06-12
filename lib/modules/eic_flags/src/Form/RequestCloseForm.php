<?php

namespace Drupal\eic_flags\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagService;
use http\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RequestCloseForm
 *
 * @package Drupal\eic_flags\Form
 */
class RequestCloseForm extends ContentEntityConfirmFormBase {

  /**
   * @var RequestHandlerCollector
   */
  protected $collector;

  /**
   * @var FlagService
   */
  protected $flagService;

  /**
   * RequestCloseForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   * @param \Drupal\flag\FlagService $flagService
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    RequestHandlerCollector $collector,
    FlagService $flagService
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->collector = $collector;
    $this->flagService = $flagService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('eic_flags.handler_collector'),
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('eic_flags.flagged_entities.list', ['flag_type' => RequestTypes::DELETE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to apply response "@response" to the @entity-type %label?', [
      '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
      '@response' => RequestStatus::DENIED,
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = $this->getRequest()->query->get('response');
    if (!$response) {
      throw new InvalidArgumentException('Invalid response');
    }

    $request_type = $this->getRequest()->get('request_type');
    $handler = $this->collector->getHandlerByType($request_type);
    if (!$handler instanceof HandlerInterface) {
      throw new InvalidArgumentException('Request type is invalid');
    }

    $flag_id = $handler->getFlagId($this->entity->getEntityTypeId());
    $flag = $this->flagService->getFlagById($flag_id);
    if (!$flag instanceof FlagInterface) {
      throw new InvalidArgumentException('Flag doesn\'t exists');
    }

    $entity_flags = $this->flagService->getEntityFlaggings($flag, $this->entity);
    switch ($response) {
      case RequestStatus::DENIED:
        $action = 'deny';
        break;
      default:
        throw new InvalidArgumentException('Action isnt\'t supported');
    }

    foreach ($entity_flags as $flag) {
      call_user_func([$handler, $action], $flag, $this->entity, 'test');
    }
  }

}
