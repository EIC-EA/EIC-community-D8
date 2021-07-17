<?php

namespace Drupal\eic_flags\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    return Url::fromRoute(
      'eic_flags.flagged_entities.list',
      ['request_type' => RequestTypes::DELETE]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to apply response "@response" to the @entity-type %label?',
      [
        '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
        '@response' => $this->getRequest()->get('response'),
        '%label' => $this->getEntity()->label(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $request_type = $this->getRequest()
      ->get('request_type');
    $response = $this->getRequest()->get('response');

    return $request_type === RequestTypes::DELETE && $response === RequestStatus::ACCEPTED
      ? $this->t('This action cannot be undone.')
      : '';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['response_text'] = [
      '#type' => 'textarea',
      '#title' => $this->getResponseTitle(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('response_text')) {
      $form_state->setErrorByName(
        'response_text',
        $this->t('Reason field is required')
      );
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
    /** @var \Drupal\flag\FlaggingInterface $flag */
    $flag = $this->flagService->getFlagById($flag_id);
    if (!$flag instanceof FlagInterface) {
      throw new InvalidArgumentException('Flag doesn\'t exists');
    }

    $flagging_storage = $this->entityTypeManager->getStorage('flagging');
    $entity_flags = $flagging_storage->getQuery()
      ->condition('flag_id', $flag->id())
      ->condition('entity_type', $this->entity->getEntityTypeId())
      ->condition('entity_id', $this->entity->id())
      ->condition('field_request_status', RequestStatus::OPEN)
      ->execute();

    if (empty($entity_flags)) {
      return;
    }

    $entity_flags = $flagging_storage->loadMultiple($entity_flags);
    switch ($response) {
      case RequestStatus::DENIED:
        $action = 'deny';
        break;
      case RequestStatus::ACCEPTED:
        $action = 'accept';
        break;
      case RequestStatus::ARCHIVED:
        $action = 'archive';
        break;
      default:
        throw new InvalidArgumentException('Action isnt\'t supported');
    }

    foreach ($entity_flags as $flag) {
      // Close requests and trigger hooks, events, etc.
      $handler->closeRequest(
        $flag,
        $this->entity,
        $response,
        $form_state->getValue('response_text')
      );
    }

    // Execute the response
    call_user_func(
      [$handler, $action],
      $flag,
      $this->entity
    );
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getResponseTitle() {
    $request_type = $this->getRequest()->get('request_type');
    $operation = $request_type === RequestTypes::DELETE
      ? 'deleted'
      : 'archived';

    switch ($this->getRequest()->query->get('response')) {
      case RequestStatus::DENIED:
        return $this->t(
          '@entity-type will not be @operation. Please provide a mandatory reason for denying this request.',
          [
            '@entity-type' => $this->getEntity()
              ->getEntityType()
              ->getSingularLabel(),
            '@operation' => $operation,
          ]
        );
      case RequestStatus::ACCEPTED:
        return $this->t(
          '@entity-type will be @operation_prefix @operation. Please enter a reason or remark why you accept this request.',
          [
            '@entity-type' => $this->getEntity()
              ->getEntityType()
              ->getSingularLabel(),
            '@operation' => $operation,
            '@operation_prefix' => $request_type === RequestTypes::DELETE ? 'permanently' : '',
          ]
        );
      case RequestStatus::ARCHIVED:
        return $this->t(
          '@entity-type will be archived. Please provide a mandatory reason for denying this request.',
          [
            '@entity-type' => $this->getEntity()
              ->getEntityType()
              ->getSingularLabel(),
          ]
        );
    }
  }

}
