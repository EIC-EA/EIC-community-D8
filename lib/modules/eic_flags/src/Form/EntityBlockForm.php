<?php

namespace Drupal\eic_flags\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_flags\Service\EntityBlockHandler;
use Drupal\flag\Entity\Flagging;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an entity form for blocking an entity.
 *
 * @package Drupal\eic_flags\Form
 */
class EntityBlockForm extends ContentEntityDeleteForm {

  /**
   * The EIC Flags entity block handler service.
   *
   * @var \Drupal\eic_flags\Service\EntityBlockHandler
   */
  private $entityBlockHandler;

  /**
   * EntityBlockForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\eic_flags\Service\EntityBlockHandler $entity_block_handler
   *   The EIC Flags entity block handler service.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    EntityBlockHandler $entity_block_handler
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityBlockHandler = $entity_block_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('eic_flags.entity_block_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t(
      "You're about to block this @entity_type. Are you sure?",
      [
        '@entity_type' => $this->entity->getEntityTypeId(),
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t(
      'Block @entity_type',
      [
        '@entity_type' => $this->entity->getEntityTypeId(),
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => $this->t(
        'Please explain why this @entity_type will be blocked.',
        [
          '@entity_type' => $this->entity->getEntityTypeId(),
        ],
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
        $this->t('Reason field is required.')
      );
    }

    $entity_moderation_state = $this->entity->get('moderation_state')->value;

    // Group is not published, so we do nothing.
    if ($entity_moderation_state === EntityBlockHandler::ENTITY_BLOCKED_STATE) {
      $form_state->setError(
        $form,
        $this->t(
          'This @entity_type is already blocked.',
          [
            '@entity_type' => $this->entity->getEntityTypeId(),
          ],
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $flag = $this->entityBlockHandler->applyFlag($this->entity, $form_state->getValue('reason'));
    if (!$flag instanceof Flagging) {
      $this->messenger()->addError($this->t('You are not allowed to do this.'));
    }

    $this->entityBlockHandler->blockEntity($this->entity);
    $this->messenger()->addStatus(
      $this->t(
        'The @entity_type has been blocked.',
        [
          '@entity_type' => $this->entity->getEntityTypeId(),
        ],
      )
    );
  }

}
