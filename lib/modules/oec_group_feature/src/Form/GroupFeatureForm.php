<?php

namespace Drupal\oec_group_feature\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the group feature entity edit forms.
 */
class GroupFeatureForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New group feature %label has been created.', $message_arguments));
      $this->logger('oec_group_feature')->notice('Created new group feature %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The group feature %label has been updated.', $message_arguments));
      $this->logger('oec_group_feature')->notice('Updated new group feature %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.group_feature.canonical', ['group_feature' => $entity->id()]);
  }

}
