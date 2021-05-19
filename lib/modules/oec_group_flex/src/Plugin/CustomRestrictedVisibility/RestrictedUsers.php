<?php

namespace Drupal\oec_group_flex\Plugin\CustomRestrictedVisibility;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\Annotation\CustomRestrictedVisibility;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityBase;
use Drupal\user\Entity\User;

/**
 * Provides a 'restricted_users' custom restricted visibility.
 *
 * @CustomRestrictedVisibility(
 *  id = "restricted_users",
 *  label = @Translation("Specific trusted users"),
 *  weight = 5
 * )
 */
class RestrictedUsers extends CustomRestrictedVisibilityBase {
  public function getPluginForm() {
    $form = parent::getPluginForm();

    // Specific trusted users text box.
    $form[$this->getPluginId()][$this->getPluginId() . '_conf'] = [
      '#title' => ('Select trusted users'),
      '#type' => 'entity_autocomplete',
      '#tags' => TRUE,
      '#target_type' => 'user',
      '#required' => FALSE,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'filter' => [
          'role' => ['trusted_user'],
        ],
      ],
      '#states' => [
        'visible' => [
          ':input[name="' . $this->getPluginId() . '_status"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#weight' => $this->getWeight() + 1,
    ];
    return $form;
  }

  public function setDefaultFormValues(array &$pluginForm, GroupVisibilityRecordInterface $group_visibility_record = NULL) {
    if (is_null($group_visibility_record)) {
      return $pluginForm;
    }

    $options = $this->getOptionsForPlugin($group_visibility_record);
    if (array_key_exists($this->getStatusKey(), $options) && $options[$this->getStatusKey()] === 1) {
      $pluginForm[$this->getStatusKey()]['#default_value'] = 1;
      $conf_key = $this->getPluginId() . '_conf';

      $restricted_users = $options[$conf_key];
      if ($restricted_users) {
        foreach ($restricted_users as $user) {
          $pluginForm[$conf_key]['#default_value'][] = User::load($user['target_id']);
        }
      }
    }
    return $pluginForm;
  }

}
