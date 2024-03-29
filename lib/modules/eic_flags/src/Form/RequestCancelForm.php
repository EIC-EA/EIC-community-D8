<?php

namespace Drupal\eic_flags\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\flag\FlagInterface;
use http\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an entity form for cancelling requests.
 *
 * @package Drupal\eic_flags\Form
 */
class RequestCancelForm extends ContentEntityConfirmFormBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->collector = $container->get('eic_flags.handler_collector');
    $instance->flagService = $container->get('flag');

    $instance->requestType = $instance->getRequest()
      ->get('request_type');
    return $instance;
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
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['response_text'] = [
      '#type' => 'textarea',
      '#title' => $this->getResponseTitle(),
      '#attributes' => [
        'placeholder' => $this->t('Your reason here'),
      ],
      '#required' => TRUE,
    ];

    return $form;
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
        return $this->t(
          'State the reason for cancelling this request.',
        );

    }

    return $this->t(
      'Are you sure you want to cancel this request? Please provide a mandatory reason for cancelling this request.',
    );
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = RequestStatus::CANCELLED;

    $handler = $this->collector->getHandlerByType($this->requestType);
    if (!$handler instanceof HandlerInterface) {
      throw new InvalidArgumentException('Request type is invalid');
    }

    $flag_id = $handler->getFlagId($this->entity->getEntityTypeId());
    /** @var \Drupal\flag\FlaggingInterface $flag */
    $flag = $this->flagService->getFlagById($flag_id);
    if (!$flag instanceof FlagInterface) {
      throw new InvalidArgumentException("Flag doesn't exists");
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

    // Action is not supported for the response type.
    if (!in_array($response, $handler->getSupportedResponsesForClosedRequests())) {
      $this->messenger()->addStatus($this->t("Action isnt't supported"));
      return;
    }

    // Action can no longer be executed.
    if (!$handler->canCancelRequest($this->currentUser(), $this->entity)) {
      $this->messenger()->addStatus($this->t('This request can no longer be cancelled'));
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
      [$handler, 'cancel'],
      $flag,
      $this->entity
    );

    $redirect_response = FALSE;
    switch ($this->requestType) {
      case RequestTypes::TRANSFER_OWNERSHIP:
        $this->messenger()->addStatus($this->t('Ownership transfer has been cancelled'));

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

}
