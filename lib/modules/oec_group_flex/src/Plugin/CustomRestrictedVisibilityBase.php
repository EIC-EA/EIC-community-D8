<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;

/**
 * Base class for Custom restricted visibility plugins.
 */
abstract class CustomRestrictedVisibilityBase extends PluginBase implements CustomRestrictedVisibilityInterface {

  /**
   * {@inheritdoc}
   */
  public function getPluginForm(): array {
    $form[$this->getPluginId()] = [
      '#type' => 'container',
      '#element_validate' => [
        [$this, 'validatePluginForm'],
      ],
    ];
    $form[$this->getPluginId()][$this->getStatusKey()] = [
      '#title' => $this->getLabel(),
      '#type' => 'checkbox',
      '#weight' => $this->getWeight(),
      '#default_value' => 0,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePluginForm(array &$element, FormStateInterface $form_state) {
    $status_key = $this->getPluginId() . '_status';
    // If plugin status is disabled, we don't need any validation and therefore
    // we return TRUE.
    if (!$form_state->getValue($status_key)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultFormValues(array &$pluginForm, GroupVisibilityRecordInterface $group_visibility_record = NULL): array {
    if (is_null($group_visibility_record)) {
      return $pluginForm;
    }

    $options = $this->getOptionsForPlugin($group_visibility_record);
    if (array_key_exists($this->getStatusKey(), $options) && $options[$this->getStatusKey()] === 1) {
      $pluginForm[$this->getStatusKey()]['#default_value'] = 1;
      $conf_key = $this->getPluginId() . '_conf';
      $pluginForm[$conf_key]['#default_value'] = $options[$conf_key];
    }
    return $pluginForm;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormFieldNames(): array {
    $status_field = $this->getPluginId() . '_status';
    $conf_field = $this->getPluginId() . '_conf';
    return [
      $status_field => $status_field,
      $conf_field => $conf_field,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusKey(): string {
    return $this->getPluginId() . '_status';
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewAccess(GroupInterface $entity, AccountInterface $account, GroupVisibilityRecordInterface $group_visibility_record) {
    return AccessResultNeutral::neutral();
  }

  /**
   * Get the stored options for a given plugin.
   *
   * @param \Drupal\oec_group_flex\GroupVisibilityRecordInterface $group_visibility_record
   *   The group visibility record to retrieve the options from.
   *
   * @return array|mixed
   *   The options for the given plugin.
   */
  protected function getOptionsForPlugin(GroupVisibilityRecordInterface $group_visibility_record) {
    $allOptions = $group_visibility_record->getOptions();
    return isset($allOptions[$this->getPluginId()]) ? $allOptions[$this->getPluginId()] : [];
  }

}
