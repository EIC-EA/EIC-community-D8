<?php

/**
 * @file
 * Contains hook_form_FORM_ID_alter() for groups.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_BASE_FORM_ID_alter() for groups.
 */
function eic_community_form_group_form_alter(&$form, FormStateInterface $form_state) {
  $form['#theme'] = ['eic_group_edit_form'];

  $form['revision_information']['#type'] = 'container';
  $form['#attributes']['class'][] = 'has-sidebar';
}
