<?php

namespace Drupal\eic_messages\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_messages\MessageTemplateTypes;
use Drupal\message\Form\MessageTemplateForm as MessageTemplateFormBase;

/**
 * Form controller for message template forms.
 */
class MessageTemplateForm extends MessageTemplateFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\message\Entity\MessageTemplate $template */
    $template = $this->entity;

    // Add our custom type field to the form.
    $form['message_template_type'] = [
      '#title' => $this->t('Type'),
      '#type' => 'select',
      '#options' => MessageTemplateTypes::getOptionsArray(),
      '#description' => $this->t('Select the type of this message template.'),
      '#default_value' => $template->getThirdPartySetting('eic_messages', 'message_template_type'),
      '#required' => TRUE,
      '#weight' => -1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('message_template_type')) {
      $this->entity->setThirdPartySetting('eic_messages', 'message_template_type', $form_state->getValue('message_template_type'));
    }
    else {
      $this->entity->unsetThirdPartySetting('eic_messages', 'message_template_type');
    }
    parent::submitForm($form, $form_state);
  }

}
