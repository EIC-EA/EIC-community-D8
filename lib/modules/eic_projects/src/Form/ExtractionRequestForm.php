<?php

namespace Drupal\eic_projects\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the extraction request entity edit forms.
 */
class ExtractionRequestForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New extraction request %label has been created.', $message_arguments));
        $this->logger('eic_projects')->notice('Created new extraction request %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The extraction request %label has been updated.', $message_arguments));
        $this->logger('eic_projects')->notice('Updated extraction request %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.extraction_request.canonical', ['extraction_request' => $entity->id()]);

    return $result;
  }

}
