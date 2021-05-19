<?php

namespace Drupal\oec_group_flex\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;

/**
 * Base class for Custom restricted visibility plugins.
 */
abstract class CustomRestrictedVisibilityBase extends PluginBase implements CustomRestrictedVisibilityInterface {

  public function getPluginForm() {
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
   * Form validation for the whole container element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validatePluginForm(array &$element, FormStateInterface $form_state) {
  }

  public function setDefaultFormValues(array &$pluginForm, GroupVisibilityRecordInterface $group_visibility_record = NULL) {
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

  public function getGroupVisibilitySettings(GroupInterface $group) {
    return \Drupal::service('oec_group_flex.group_visibility.storage')->load($group->id());
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

  protected function getOptionsForPlugin(GroupVisibilityRecordInterface $group_visibility_record) {
    $allOptions = $group_visibility_record->getOptions();
    return isset($allOptions[$this->getPluginId()]) ? $allOptions[$this->getPluginId()] : [];
  }

}
