<?php

namespace Drupal\eic_flags\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\ArchiveRequestHandler;
use Drupal\eic_flags\Service\BlockRequestHandler;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\eic_flags\Service\RequestHandlerCollector;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\flag\Entity\Flagging;
use Drupal\flag\FlagServiceInterface;
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
   * The Flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

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
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The Flag service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity field manager.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    RequestHandlerCollector $collector,
    UserHelper $eic_user_helper,
    FlagServiceInterface $flag_service,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->requestHandler = $collector->getHandlerByType(
      $this->getRequest()->get('request_type')
    );
    $this->eicUserHelper = $eic_user_helper;
    $this->flagService = $flag_service;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('eic_user.helper'),
      $container->get('flag'),
      $container->get('entity_field.manager')
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
          $description = $this->t("<p>Do you want to request the ownership transfer to %new_owner?</p>
            <p>If the user accepts, the current owner %previous_owner will become a group admin.</p>",
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
        $description = $this->t('Request ownership transfer');
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
    $has_timeout_field = FALSE;
    $flag = $this->flagService->getFlagById(
      $this->requestHandler->getSupportedEntityTypes()[$this->entity->getEntityTypeId()]
    );

    $fields = $this->entityFieldManager->getFieldDefinitions('flagging', $flag->id());
    if (isset($fields[HandlerInterface::REQUEST_TIMEOUT_FIELD])) {
      $has_timeout_field = TRUE;
    }

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

    if ($has_timeout_field) {
      $form['timeout'] = [
        '#type' => 'select',
        '#title' => $this->t('How many days should this request remain open?'),
        '#options' => RequestTypes::getRequestTimeoutExpirationOptions(),
        '#default_value' => $fields[HandlerInterface::REQUEST_TIMEOUT_FIELD]->getDefaultValueLiteral()[0]['value'],
        '#required' => TRUE,
      ];
    }

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
      ->applyFlag($this->entity, $form_state->getValue('reason'), $form_state->getValue('timeout') ?? 0);
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
