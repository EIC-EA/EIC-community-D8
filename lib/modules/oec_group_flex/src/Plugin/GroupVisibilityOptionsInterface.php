<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Extends GroupVisibilityOptionsInterface for Group visibility plugins.
 */
interface GroupVisibilityOptionsInterface {

  /**
   * Get options form.
   *
   * @return array
   *   The options form renderable array.
   */
  public function getPluginForm();

  /**
   * Gets the group visibility options from the form state.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The group visibility options array.
   */
  public function getFormStateValues(array &$form, FormStateInterface $form_state);

}
