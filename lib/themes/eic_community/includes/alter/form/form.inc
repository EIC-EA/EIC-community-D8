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
}

/**
 * Custom function to handle cancel buttons.
 */
function _eic_community_form_cancel_button(&$form, FormStateInterface $form_state) {
  if (!isset($form['actions']['cancel'])) {
    return;
  }

  $classes = [
    'ecl-link',
    'ecl-link--default',
    'ecl-link--button',
    'ecl-link--button-ghost',
  ];

  if (!empty($form['actions']['cancel']['#attributes']['class'])) {
    $form['actions']['cancel']['#attributes']['class'] = array_merge(
      $form['actions']['cancel']['#attributes']['class'],
      $classes
    );
  }
  else {
    $form['actions']['cancel']['#attributes']['class'] = $classes;
  }
}

/**
 * Custom function to handle delete buttons.
 */
function _eic_community_form_delete_button(&$form, FormStateInterface $form_state) {
  if (!isset($form['actions']['delete'])) {
    return;
  }

  $classes = [
    'ecl-link',
    'ecl-link--default',
    'ecl-link--button',
    'ecl-link--button-ghost',
  ];

  if (!empty($form['actions']['delete']['#attributes']['class'])) {
    $form['actions']['delete']['#attributes']['class'] = array_merge(
      $form['actions']['delete']['#attributes']['class'],
      $classes
    );
  }
  else {
    $form['actions']['delete']['#attributes']['class'] = $classes;
  }
}
