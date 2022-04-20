<?php

namespace Drupal\eic_user_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure EIC User Login settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_user_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eic_user_login.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // User settings.
    $form['user_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User settings'),
    ];
    $form['check_sync_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check/sync user'),
      '#default_value' => $this->config('eic_user_login.settings')->get('check_sync_user'),
      '#description' => $this->t('Check this option if user should be checked/synchronised against SMED upon user login.'),
      '#group' => 'user_settings',
    ];
    $form['allow_user_register'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to register within Drupal'),
      '#default_value' => $this->config('eic_user_login.settings')->get('allow_user_register'),
      '#description' => $this->t('Check this option if users should be able to register within Drupal without any initial check against the SMED.'),
      '#group' => 'user_settings',
    ];
    $form['endpoint_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint URL'),
      '#default_value' => $this->config('eic_user_login.settings')->get('endpoint_url'),
      '#description' => $this->t('This should be configured in settings.php only, in %config_snippet.', [
        '%config_snippet' => '$config[\'eic_user_login.settings\'][\'endpoint_url\']',
      ]),
      '#required' => FALSE,
      '#disabled' => TRUE,
      '#group' => 'user_settings',
    ];
    $form['basic_auth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Basic Auth username'),
      '#default_value' => $this->config('eic_user_login.settings')->get('basic_auth_username'),
      '#description' => $this->t('This should be configured in settings.php only, in %config_snippet.', [
        '%config_snippet' => '$config[\'eic_user_login.settings\'][\'basic_auth_username\']',
      ]),
      '#required' => FALSE,
      '#disabled' => TRUE,
      '#group' => 'user_settings',
    ];
    $form['basic_auth_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Basic Auth password'),
      '#default_value' => $this->config('eic_user_login.settings')->get('basic_auth_password'),
      '#description' => $this->t('This should be configured in settings.php only, in %config_snippet.', [
        '%config_snippet' => '$config[\'eic_user_login.settings\'][\'basic_auth_password\']',
      ]),
      '#required' => FALSE,
      '#disabled' => TRUE,
      '#group' => 'user_settings',
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->config('eic_user_login.settings')->get('api_key'),
      '#description' => $this->t('This should be configured in settings.php only, in %config_snippet.', [
        '%config_snippet' => '$config[\'eic_user_login.settings\'][\'api_key\']',
      ]),
      '#required' => FALSE,
      '#disabled' => TRUE,
      '#group' => 'user_settings',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('eic_user_login.settings')
      ->set('check_sync_user', $form_state->getValue('check_sync_user'))
      ->set('allow_user_register', $form_state->getValue('allow_user_register'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
