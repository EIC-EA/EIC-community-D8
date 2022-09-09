<?php

namespace Drupal\eic_organisations\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormOperations {

  use StringTranslationTrait;

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formGroupFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    switch ($entity->bundle()) {
      case 'organisation':
        // Webservices don't always have information for this field. Normally we
        // would create an exception for this field when organisation is being
        // created/updated from Webservice.
        // However it seems overly complicated to create this exception.
        // Therefore we work the other way-round: the field is now non-required
        // but we require it through the form.
        $form['field_vocab_topics']['widget']['#required'] = TRUE;
        break;
    }
  }

}
