<?php

namespace Drupal\eic_stakeholder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the stakeholder entity edit forms.
 */
class StakeholderForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New stakeholder %label has been created.', $message_arguments));
        $this->logger('eic_stakeholder')->notice('Created new stakeholder %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The stakeholder %label has been updated.', $message_arguments));
        $this->logger('eic_stakeholder')->notice('Updated stakeholder %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.stakeholder.canonical', ['stakeholder' => $entity->id()]);

    return $result;
  }

}
