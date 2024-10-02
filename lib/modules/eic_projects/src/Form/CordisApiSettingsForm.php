<?php

namespace Drupal\eic_projects\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure EIC Projects settings for this site.
 */
class CordisApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_projects_cordis_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eic_projects.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CORDIS API key'),
      '#default_value' => $this->config('eic_projects.settings')->get('api_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('eic_projects.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
