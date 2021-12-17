<?php

namespace Drupal\eic_flags\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\ArchiveRequestHandler;
use Drupal\eic_flags\Service\BlockRequestHandler;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\flag\Entity\Flagging;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an entity form for creating new archive/block/delete requests.
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
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  protected $eicUserHelper;

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
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    RequestHandlerCollector $collector,
    UserHelper $eic_user_helper
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->requestHandler = $collector->getHandlerByType(
      $this->getRequest()->get('request_type')
    );
    $this->eicUserHelper = $eic_user_helper;
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
      $container->get('eic_user.helper')
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
  public function getCancelUrl() {
    $request_type = $this->getRequest()
      ->get('request_type');

    switch ($request_type) {
      case RequestTypes::TRANSFER_OWNERSHIP:
        if ($this->getEntity()->getEntityTypeId() !== 'group_content') {
          break;
        }
        // Returns the group URL.
        return $this->getEntity()
          ->getGroup()
          ->toUrl();

    }

    return parent::getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = NULL;

    switch ($this->requestHandler->getType()) {
      case RequestTypes::BLOCK:
        $description = $this->t(
          "You're about to block this @entity_type. Are you sure?",
          [
            '@entity_type' => $this->entity->getEntityType()->getLabel(),
          ],
        );
        break;

      case RequestTypes::TRANSFER_OWNERSHIP:
        if ($this->entity->getEntityTypeId() === 'group_content') {
          $new_owner = $this->entity->getEntity();
          $previous_owner = EICGroupsHelper::getGroupOwner($this->entity->getGroup());
          $description = $this->t("<p>You're about to request transfer ownership to %new_owner?</p>
            <p>If the user accepts, the previous owner %previous_owner will turn into a group admin.</p>",
            [
              '%new_owner' => $this->eicUserHelper->getFullName($new_owner),
              '%previous_owner' => $this->eicUserHelper->getFullName($previous_owner),
            ]
          );
        }
        break;

      default:
        // @todo In the future we should consider creating a helper function if
        // we need to use this line of code elsewhere.
        $action = $this->requestHandler instanceof ArchiveRequestHandler ? 'archival' : 'deletion';
        $description = $this->t(
          "You're about to request @action for this entity. Are you sure?",
          [
            '@action' => $action,
          ]
        );
        break;
    }

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $description = NULL;

    switch ($this->requestHandler->getType()) {
      case RequestTypes::BLOCK:
        $description = $this->t(
          'Block @entity_type',
          [
            '@entity_type' => $this->entity->getEntityType()->getLabel(),
          ],
        );
        break;

      case RequestTypes::TRANSFER_OWNERSHIP:
        $description = $this->t('Request transfer ownership');
        break;

      default:
        // @todo In the future we should consider creating a helper function if
        // we need to use this line of code elsewhere.
        $action = $this->requestHandler instanceof ArchiveRequestHandler ? 'archival' : 'deletion';
        $description = $this->t('Request @action', ['@action' => $action]);
        break;
    }

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form_field_description = NULL;

    switch ($this->requestHandler->getType()) {
      case RequestTypes::BLOCK:
        $form_field_description = $this->t(
          'Please explain why this @entity_type should be @action',
          [
            '@entity_type' => $this->entity->getEntityType()->getLabel(),
            '@action' => $this->t('blocked'),
          ],
        );
        break;

      case RequestTypes::TRANSFER_OWNERSHIP:
        $form_field_description = $this->t(
          'Please explain why you want to @action',
          [
            '@action' => $this->t('transfer ownership'),
          ],
        );
        break;

      default:
        $form_field_description = $this->t(
          'Please explain why this @entity_type should be @action',
          [
            '@entity_type' => $this->entity->getEntityType()->getLabel(),
            '@action' => $this->requestHandler instanceof ArchiveRequestHandler ? 'archived' : 'deleted',
          ],
        );
        break;
    }

    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => $form_field_description,
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

    // If the entity is not a group and request type is not block, we exit.
    if (
      $this->entity->getEntityTypeId() !== 'group' &&
      $this->requestHandler !== RequestTypes::BLOCK
    ) {
      return;
    }

    $entity_moderation_state = $this->entity->get('moderation_state')->value;

    // Group is not published, so we do nothing.
    if ($entity_moderation_state === BlockRequestHandler::ENTITY_BLOCKED_STATE) {
      $form_state->setError(
        $form,
        $this->t(
          'This @entity_type is already blocked.',
          [
            '@entity_type' => $this->entity->getEntityType()->getLabel(),
          ],
        )
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

    $status_message = $this->t('The request has been made');

    // If the request was to block a group, we show a different message status.
    if (
      $this->entity->getEntityTypeId() === 'group' &&
      $this->requestHandler->getType() === RequestTypes::BLOCK
    ) {
      $status_message = $this->t(
        'The @entity_type has been blocked.',
        [
          '@entity_type' => $this->entity->getEntityType()->getLabel(),
        ],
      );
    }

    $this->messenger()->addStatus($status_message);
  }

}
