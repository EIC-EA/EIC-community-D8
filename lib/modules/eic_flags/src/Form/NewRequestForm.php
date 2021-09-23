<?php

namespace Drupal\eic_flags\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_flags\Service\ArchiveRequestHandler;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\flag\Entity\Flagging;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NewRequestForm.
 *
 * @package Drupal\eic_flags\Form
 */
class NewRequestForm extends ContentEntityDeleteForm {

  /**
   * The handler for the request type.
   *
   * @var \Drupal\eic_flags\Service\HandlerInterface
   */
  private $requestHandler;

  /**
   * NewRequestForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\eic_flags\Service\RequestHandlerCollector $collector
   *   The handler collector service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    RequestHandlerCollector $collector
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->requestHandler = $collector->getHandlerByType(
      $this->getRequest()->get('request_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('eic_flags.handler_collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_new_request_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $action = $this->requestHandler instanceof ArchiveRequestHandler ? 'archival' : 'deletion';
    return $this->t(
      "You're about to request @action for this entity. Are you sure?",
      ['@action' => $action]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $action = $this->requestHandler instanceof ArchiveRequestHandler ? 'archival' : 'deletion';
    return $this->t('Request @action', ['@action' => $action]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $action = $this->requestHandler instanceof ArchiveRequestHandler ? 'archived' : 'deleted';
    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => $this->t(
        'Please explain why this content should be @action',
        ['@action' => $action]
      ),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('reason')) {
      $form_state->setErrorByName(
        'reason',
        $this->t('Reason field is required')
      );
    }

    if ($this->requestHandler->hasOpenRequest(
      $this->entity,
      $this->currentUser()
    )) {
      $form_state->setError(
        $form,
        $this->t('An open request already exists for this entity.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $flag = $this->requestHandler
      ->applyFlag($this->entity, $form_state->getValue('reason'));
    if (!$flag instanceof Flagging) {
      $this->messenger()->addError($this->t('You are not allowed to do this'));
    }

    $this->messenger()->addStatus($this->t('The request has been made'));
  }

}
