<?php

/**
 * @file
 * Contains form alter related functions.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_alter().
 */
function eic_community_form_alter(&$form, FormStateInterface $form_state) {
  _eic_community_form_cancel_button($form, $form_state);
  _eic_community_form_delete_button($form, $form_state);

  $build_info = $form_state->getBuildInfo();
  if (!empty($build_info['base_form_id'])) {
    $base_form_id = $form_state->getBuildInfo()['base_form_id'];

    switch ($base_form_id) {
      case 'node_form':
        // We want to always open the book outline in node forms.
        if (!empty($form['book']) && $form['book']['#type'] == 'details') {
          $form['book']['#open'] = TRUE;
        }
        break;
    }
  }
}

/**
 * Custom function to handle cancel buttons.
 *
 * @param array $form
 *   The form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state object.
 * @param array $additional_actions
 *   Additional actions to apply the style.
 */
function _eic_community_form_cancel_button(array &$form, FormStateInterface $form_state, array $additional_actions = []) {
  $actions = array_merge($additional_actions, ['cancel']);

  $classes = [
    'ecl-link',
    'ecl-link--default',
    'ecl-link--button',
    'ecl-link--button-ghost',
  ];

  foreach ($actions as $action_name) {
    if (!empty($form['actions'][$action_name]['#attributes']['class'])) {
      $form['actions'][$action_name]['#attributes']['class'] = array_merge(
        $form['actions'][$action_name]['#attributes']['class'],
        $classes
      );
    }
    else {
      $form['actions'][$action_name]['#attributes']['class'] = $classes;
    }
  }
}

/**
 * Custom function to handle delete buttons.
 *
 * @param array $form
 *   The form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state object.
 * @param array $additional_actions
 *   Additional actions to apply the style.
 */
function _eic_community_form_delete_button(array &$form, FormStateInterface $form_state, array $additional_actions = []) {
  $actions = array_merge($additional_actions, ['delete']);

  $classes = [
    'ecl-link',
    'ecl-link--default',
    'ecl-link--button',
    'ecl-link--button-danger',
  ];

  foreach ($actions as $action_name) {
    if (!empty($form['actions'][$action_name]['#attributes']['class'])) {
      $form['actions'][$action_name]['#attributes']['class'] = array_merge(
        $form['actions'][$action_name]['#attributes']['class'],
        $classes
      );
    }
    else {
      $form['actions'][$action_name]['#attributes']['class'] = $classes;
    }
  }
}
