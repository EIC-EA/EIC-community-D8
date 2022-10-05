<?php

namespace Drupal\eic_contact;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contact\ContactFormEditForm as ContactFormEditFormBase;
use Drupal\eic_contact\Constants\ContactFormIds;

/**
 * Custom class to override Drupal\contact\ContactFormEditForm.
 */
class ContactFormEditForm extends ContactFormEditFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($form_state->getFormObject()->getEntity()->id() == ContactFormIds::GLOBAL_CONTACT_FORM_ID) {
      // Since we already have recipient per category (see contact_category
      // taxonomy), we disable the required recipients.
      $form['recipients']['#required'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This is a copy of Drupal\contact\ContactFormEditForm::validateForm(), with
   * small adjustments.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Since we override the Drupal\contact\ContactFormEditForm::validateForm()
    // here, we don't want it to be triggered, so we call directly its parent
    // class validateForm() method.
    EntityForm::validateForm($form, $form_state);

    $recipients_string = $form_state->getValue('recipients');
    $recipients = [];

    // Validate only if we have a proper value.
    if (!empty($recipients_string)) {
      // Validate and each email recipient.
      $recipients = explode(',', $form_state->getValue('recipients'));

      foreach ($recipients as &$recipient) {
        $recipient = trim($recipient);
        if (!$this->emailValidator->isValid($recipient)) {
          $form_state->setErrorByName('recipients', $this->t('%recipient is an invalid email address.', ['%recipient' => $recipient]));
        }
      }
    }

    $form_state->setValue('recipients', $recipients);
    $redirect_url = $form_state->getValue('redirect');
    if ($redirect_url && $this->pathValidator->isValid($redirect_url)) {
      if (mb_substr($redirect_url, 0, 1) !== '/') {
        $form_state->setErrorByName('redirect', $this->t('The path should start with /.'));
      }
    }
  }

}
