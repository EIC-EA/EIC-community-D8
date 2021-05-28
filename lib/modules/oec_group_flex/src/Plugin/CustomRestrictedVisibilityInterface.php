<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;

/**
 * Defines an interface for Custom restricted visibility plugins.
 */
interface CustomRestrictedVisibilityInterface extends PluginInspectionInterface {

  /**
   * The label of the custom restricted visibility.
   *
   * @return string
   *   The label of the custom restricted visibility.
   */
  public function getLabel(): string;

  /**
   * The weight of the custom restricted visibility.
   *
   * @return int
   *   The weight of the custom restricted visibility.
   */
  public function getWeight(): int;


  /**
   * Get the status key for this plugin.
   *
   * @return string
   *   The status key of the custom restricted visibility.
   */
  public function getStatusKey(): string;

  /**
   * Whether a given user has view access to an entity.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *  The user account.
   * @param \Drupal\oec_group_flex\GroupVisibilityRecordInterface $group_visibility_record
   *   The group visibility record.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function hasViewAccess(GroupInterface $entity, AccountInterface $account, GroupVisibilityRecordInterface $group_visibility_record);

  /**
   * Get the plugin form to be used in the group form.
   *
   * @return array
   *   The plugin form.
   */
  public function getPluginForm(): array;

  /**
   * Form validation for the whole container element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validatePluginForm(array &$element, FormStateInterface $form_state);

  /**
   * Set the default form values.
   *
   * @param array $pluginForm
   *   The plugin form to enhance with default form values.
   * @param \Drupal\oec_group_flex\GroupVisibilityRecordInterface|null $group_visibility_record
   *   The group visibility record to get the values from.
   *
   * @return array
   *   The form element array.
   */
  public function setDefaultFormValues(array &$pluginForm, GroupVisibilityRecordInterface $group_visibility_record = NULL): array;

  /**
   * Get an array of form field names for this plugin.
   *
   * @return array
   *   The form field names array.
   */
  public function getFormFieldNames(): array;

}
