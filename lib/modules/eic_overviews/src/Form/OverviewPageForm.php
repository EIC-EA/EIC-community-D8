<?php

namespace Drupal\eic_overviews\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the overview page entity edit forms.
 */
class OverviewPageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => \Drupal::service('renderer')->render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New overview page %label has been created.', $message_arguments));
      $this->logger('eic_overviews')->notice('Created new overview page %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The overview page %label has been updated.', $message_arguments));
      $this->logger('eic_overviews')->notice('Updated new overview page %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.overview_page.canonical', ['overview_page' => $entity->id()]);

    return $result;
  }

}
