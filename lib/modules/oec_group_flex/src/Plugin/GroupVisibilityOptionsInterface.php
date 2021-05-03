<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Extends GroupVisibilityOptionsInterface for Group visibility plugins.
 */
interface GroupVisibilityOptionsInterface {

  /**
   * Gets plugin options form.
   *
   * @return array
   *   The options form renderable array.
   */
  public function getPluginOptionsForm(FormStateInterface $form_state);

  /**
   * Gets the group visibility options from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The group visibility options array.
   */
  public function getFormStateValues(FormStateInterface $form_state);

  /**
   * Check group access based on the group visibility options.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   *   The group entity.
   * @param string $operation
   *   The group operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account object.
   */
  public function groupAccess(GroupInterface $entity, $operation, AccountInterface $account);

  /**
   * Gets field names of all form elements of the plugin.
   *
   * @return array
   *   Array with list of form field names (option_name => form_field_name).
   */
  public static function getPluginFormElementsFieldNames();

}
