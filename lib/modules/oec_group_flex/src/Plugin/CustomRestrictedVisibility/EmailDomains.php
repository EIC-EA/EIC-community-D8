<?php

namespace Drupal\oec_group_flex\Plugin\CustomRestrictedVisibility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\Annotation\CustomRestrictedVisibility;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityBase;

/**
 * Provides a 'email_domains' custom restricted visibility.
 *
 * @CustomRestrictedVisibility(
 *  id = "email_domains",
 *  label = @Translation("Specific email domains"),
 *  weight = 0
 * )
 */
class EmailDomains extends CustomRestrictedVisibilityBase {
  public function getPluginForm() {
    $form = parent::getPluginForm();
    $form[$this->getPluginId()][$this->getPluginId() . '_conf'] = [
      '#title' => ('Email domain'),
      '#description' => t('Add multiple email domains but separating them with a comma'),
      '#type' => 'textfield',
      '#element_validate' => [
        [$this, 'validateEmailDomains'],
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

  /**
   * Form element validation for restricted_email_domains field.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateEmailDomains(array &$element, FormStateInterface $form_state) {
    $conf_key = $this->getPluginId() . '_conf';
    if ($value = $form_state->getValue($conf_key)) {
      $domain_names = explode(',', $value);
      foreach ($domain_names as $domain_name) {
        $valid = \Drupal::service('email.validator')->isvalid("placeholder@$domain_name");
        if (!$valid) {
          return $form_state->setError($element, t('One of the email domains is not valid.'));
        }
      }
    }
  }

}
