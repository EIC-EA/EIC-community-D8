<?php

/**
 * @file
 * Contains hook_form_FORM_ID_alter() for system_theme_settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_BASE_FORM_ID_alter() for \Drupal\node\NodeForm.
 *
 * Changes vertical tabs to container.
 */
function eic_community_form_node_form_alter(&$form, FormStateInterface $form_state) {
  $form['#theme'] = ['node_edit_form'];

  $form['advanced']['#type'] = 'container';
  $form['meta']['#type'] = 'container';
  $form['meta']['#access'] = TRUE;
  $form['meta']['changed']['#wrapper_attributes']['class'][] = 'container-inline';
  $form['meta']['author']['#wrapper_attributes']['class'][] = 'container-inline';

  $form['revision_information']['#type'] = 'container';
  $form['revision_information']['#group'] = 'meta';

  $form['#attributes']['class'][] = 'has-sidebar';
}
