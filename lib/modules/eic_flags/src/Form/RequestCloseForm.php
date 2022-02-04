<?php

namespace Drupal\eic_flags\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
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
 * Provides an entity form for closing requests.
 *
 * @package Drupal\eic_flags\Form
 */
class RequestCloseForm extends ContentEntityConfirmFormBase {

  /**
   * The handler collector service.
   *
   * @var \Drupal\eic_flags\Service\RequestHandlerCollector
   */
  protected $collector;

  /**
   * The flag service provided by the flag module.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * The request type.
   *
   * @var string
   */
  private $requestType;

  /**
   * RequestCloseForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The handler collector service.
   * @param \Drupal\flag\FlagService $flagService
   *   The flag service provided by the flag module.
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

    $this->requestType = $this->getRequest()
      ->get('request_type');
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
    switch ($this->requestType) {
      case RequestTypes::TRANSFER_OWNERSHIP:
        if ($this->getEntity()->getEntityTypeId() !== 'group_content') {
          break;
        }
        // Returns the group URL.
        return $this->getEntity()
          ->getGroup()
          ->toUrl();

    }

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
        '@entity-type' => $this->getEntity()
          ->getEntityType()
          ->getSingularLabel(),
        '@response' => $this->getRequest()->get('response'),
        '%label' => $this->getEntity()->label(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $response = $this->getRequest()->get('response');
    switch ($this->requestType) {
      case RequestTypes::TRANSFER_OWNERSHIP:
        return $this->requestType === RequestTypes::TRANSFER_OWNERSHIP &&
          ($response === RequestStatus::ACCEPTED || $response === RequestStatus::DENIED)
            ? $this->t('This action cannot be undone.')
            : '';

      default:
        return $this->requestType === RequestTypes::DELETE && $response === RequestStatus::ACCEPTED
          ? $this->t('This action cannot be undone.')
          : '';

    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $response = $this->getRequest()->get('response');
    $show_response_field = TRUE;
    $handler = $this->collector->getHandlerByType($this->requestType);

    // Action is not supported. We show a message and remove the cancel link.
    if (
      !in_array($response, $handler->getSupportedResponsesForClosedRequests()) ||
      $response === RequestStatus::CANCELLED
    ) {
      $form['response_title'] = [
        '#markup' => $this->t('You are trying to execute an action which is not supported'),
      ];
      $form['actions']['submit']['#value'] = $this->t('Back');
      unset($form['actions']['cancel']);
      return $form;
    }

    switch ($this->requestType) {
      case RequestTypes::TRANSFER_OWNERSHIP:
        if ($response === RequestStatus::ACCEPTED) {
          $show_response_field = FALSE;
          $form['response_title'] = [
            '#markup' => $this->getResponseTitle(),
          ];
        }
        break;

    }

    if ($show_response_field) {
      $form['response_text'] = [
        '#type' => 'textarea',
        '#title' => $this->getResponseTitle(),
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $response = $this->getRequest()->get('response');
    $has_response_field = TRUE;
    $handler = $this->collector->getHandlerByType($this->requestType);

    // Action is not supported, we do nothing.
    if (
      !in_array($response, $handler->getSupportedResponsesForClosedRequests()) ||
      $response === RequestStatus::CANCELLED
    ) {
      return;
    }

    switch ($this->requestType) {
      case RequestTypes::TRANSFER_OWNERSHIP:
        if ($response === RequestStatus::ACCEPTED) {
          $has_response_field = FALSE;
        }
        break;

    }

    if (!$has_response_field) {
      return;
    }

    if (!$form_state->getValue('response_text')) {
      $form_state->setErrorByName(
        'response_text',
        $this->t('Reason field is required')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = $this->getRequest()->query->get('response');
    if (!$response) {
      throw new InvalidArgumentException('Invalid response');
    }

    $handler = $this->collector->getHandlerByType($this->requestType);
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

    }

    // Action is not supported for the response type, we do nothing.
    if (
      !in_array($response, $handler->getSupportedResponsesForClosedRequests()) ||
      $response === RequestStatus::CANCELLED
    ) {
      return;
    }

    foreach ($entity_flags as $flag) {
      // Close requests and trigger hooks, events, etc.
      $handler->closeRequest(
        $flag,
        $this->entity,
        $response,
        $form_state->getValue('response_text') ?? ''
      );
    }

    // Execute the response.
    call_user_func(
      [$handler, $action],
      $flag,
      $this->entity
    );

    $redirect_response = FALSE;
    switch ($this->requestType) {
      case RequestTypes::TRANSFER_OWNERSHIP:

        if ($response === RequestStatus::DENIED) {
          $this->messenger()->addStatus($this->t('Ownership transfer has been denied'));
        }
        else {
          $this->messenger()->addStatus($this->t('Ownership transfer has been accepted'));
        }

        if ($this->getEntity()->getEntityTypeId() === 'group_content') {
          // Builds response to redirect user to the group detail page.
          $redirect_response = new TrustedRedirectResponse(
            $this->getEntity()
              ->getGroup()
              ->toUrl()
              ->toString()
          );
          break;
        }

        // Builds response to redirect user to the entity detail page.
        $redirect_response = new TrustedRedirectResponse(
          $this->getEntity()
            ->toUrl()
            ->toString()
        );
        break;

    }

    if ($redirect_response instanceof TrustedRedirectResponse) {
      $form_state->setResponse($redirect_response);
    }
  }

  /**
   * Gets form response title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title to display.
   */
  protected function getResponseTitle() {
    switch ($this->requestType) {
      case RequestTypes::TRANSFER_OWNERSHIP:
        return $this->getTransferOwnershipResponseTitle();

    }

    $operation = $this->requestType === RequestTypes::DELETE
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
            '@operation_prefix' => $this->requestType === RequestTypes::DELETE ? 'permanently' : '',
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

  /**
   * Gets form response title for transfer ownership requests.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title to display.
   */
  private function getTransferOwnershipResponseTitle() {
    switch ($this->getRequest()->query->get('response')) {
      case RequestStatus::DENIED:
        if ($this->getEntity()->getEntityTypeId() === 'group_content') {
          return $this->t(
            'Are you sure you want to deny ownership transfer for the @entity-type %group-label? Please provide a mandatory reason for denying this request.',
            [
              '@entity-type' => $this->getEntity()
                ->getGroup()
                ->getEntityType()
                ->getSingularLabel(),
              '%group-label' => $this->getEntity()
                ->getGroup()
                ->label(),
            ]
          );
        }
        return $this->t(
          'Are you sure you want to deny ownership transfer for the @entity-type %entity-label? Please provide a mandatory reason for denying this request.',
          [
            '@entity-type' => $this->getEntity()
              ->getEntityType()
              ->getSingularLabel(),
            '%entity-label' => $this->getEntity()
              ->label(),
          ]
        );

      case RequestStatus::ACCEPTED:
        if ($this->getEntity()->getEntityTypeId() === 'group_content') {
          return $this->t(
            'Are you sure you want to become the owner of @entity-type %group-label?',
            [
              '@entity-type' => $this->getEntity()
                ->getGroup()
                ->getEntityType()
                ->getSingularLabel(),
              '%group-label' => $this->getEntity()
                ->getGroup()
                ->label(),
            ]
          );
        }
        return $this->t(
          'Are you sure you want to become the owner of @entity-type %group-label?',
          [
            '@entity-type' => $this->getEntity()
              ->getEntityType()
              ->getSingularLabel(),
            '%entity-label' => $this->getEntity()
              ->label(),
          ]
        );

    }
  }

}
