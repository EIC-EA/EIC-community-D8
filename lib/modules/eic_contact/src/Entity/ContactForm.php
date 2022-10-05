<?php

namespace Drupal\eic_contact\Entity;

use Drupal\contact\Entity\ContactForm as ContactFormBase;

/**
 *
 */
class ContactForm extends ContactFormBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    // Get the emails that are defined in the category.
    // ...
    
    return $this->recipients;
  }

}
