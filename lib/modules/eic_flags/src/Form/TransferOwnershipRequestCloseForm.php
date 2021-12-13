<?php

namespace Drupal\eic_flags\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_flags\RequestStatus;
use Drupal\eic_flags\RequestTypes;
use Drupal\eic_flags\Service\HandlerInterface;
use Drupal\flag\FlagInterface;
use http\Exception\InvalidArgumentException;

/**
 * Provides an entity form for closing transfer ownership requests.
 *
 * @package Drupal\eic_flags\Form
 */
class TransferOwnershipRequestCloseForm extends RequestCloseForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()
      ->getGroup()
      ->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $request_type = $this->getRequest()
      ->get('request_type');
    $response = $this->getRequest()->get('response');

    return $request_type === RequestTypes::TRANSFER_OWNERSHIP &&
      ($response === RequestStatus::ACCEPTED || $response === RequestStatus::DENIED)
        ? $this->t('This action cannot be undone.')
        : '';
  }

  /**
   * {@inheritdoc}
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

    // Execute the response.
    call_user_func(
      [$handler, $action],
      $flag,
      $this->entity
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getResponseTitle() {
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
            'Are you sure you want to become the owner of @entity-type %group-label? Please provide a mandatory reason for accepting this request.',
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
          'Are you sure you want to become the owner of @entity-type %group-label? Please provide a mandatory reason for accepting this request.',
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
