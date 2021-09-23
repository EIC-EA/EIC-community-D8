<?php

namespace Drupal\oec_group_flex\Plugin\CustomRestrictedVisibility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityBase;

/**
 * Provides a 'restricted_email_domains' custom restricted visibility.
 *
 * @CustomRestrictedVisibility(
 *  id = "restricted_email_domains",
 *  label = @Translation("Specific email domains"),
 *  weight = 0
 * )
 */
class RestrictedEmailDomains extends CustomRestrictedVisibilityBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getPluginForm():array {
    $form = parent::getPluginForm();
    $form[$this->getPluginId()][$this->getPluginId() . '_conf'] = [
      '#title' => $this->t('Email domain'),
      '#description' => $this->t('Add multiple email domains but separating them with a comma'),
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
      $value = str_replace(' ', '', $value);
      $domain_names = explode(',', $value);
      foreach ($domain_names as $domain_name) {
        $valid = \Drupal::service('email.validator')->isvalid("placeholder@$domain_name");
        if (!$valid) {
          return $form_state->setError($element, $this->t('One of the email domains is not valid.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewAccess(GroupInterface $entity, AccountInterface $account, GroupVisibilityRecordInterface $group_visibility_record) {
    $conf_key = $this->getPluginId() . '_conf';
    $options = $this->getOptionsForPlugin($group_visibility_record);
    $configurated_emails = array_key_exists($conf_key, $options) ? $options[$conf_key] : '';
    $configurated_emails = str_replace(' ', '', $configurated_emails);

    $email_domains = explode(',', $configurated_emails);
    $account_email_domain = explode('@', $account->getEmail())[1];

    // Allow access if user's email domain is one of the restricted ones.
    foreach ($email_domains as $email_domain) {
      if ($account_email_domain === $email_domain) {
        return GroupAccessResult::allowed()
          ->addCacheableDependency($account)
          ->addCacheableDependency($entity);
      }
    }
    // Fallback to neutral access.
    return parent::hasViewAccess($entity, $account, $group_visibility_record);
  }

  /**
   * {@inheritdoc}
   */
  public function validatePluginForm(array &$element, FormStateInterface $form_state) {
    // If plugin status is disabled, we do nothing.
    if (parent::validatePluginForm($element, $form_state)) {
      return;
    }
    $conf_key = $this->getPluginId() . '_conf';
    // If plugin has configurations, the validation will be handled in
    // ::validateEmailDomains() so we skip it from here.
    if ($form_state->getValue($conf_key)) {
      return;
    }
    return $form_state->setError($element[$this->getPluginId() . '_conf'], $this->t('You need to enter at least 1 email domain.'));
  }

}
