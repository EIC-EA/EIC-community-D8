<?php

/**
 * @file
 * Contains hook_form_FORM_ID_alter() for system_theme_settings.
 */

/**
 * Implements hook_form_FORM_ID_alter() for system_theme_settings.
 */
function eic_community_form_system_theme_settings_alter(
  &$form,
  \Drupal\Core\Form\FormStateInterface &$form_state,
  $form_id = NULL
) {
  // Work-around for a core bug affecting admin themes.
  // @todo Remove this once the core issue below is fixed.
  // @link https://www.drupal.org/node/943212
  if (isset($form_id)) {
    return;
  }

  $form['component_library'] = [
    '#type' => 'select',
    '#title' => t('Component library'),
    '#options' => [
      'ec' => t('European Commission'),
      'eu' => t('European Union'),
    ],
    '#default_value' => theme_get_setting('component_library'),
    '#description' => t('Choose "European Commission" for websites hosted under the ec.europa.eu domain and "European Union" for websites hosted under the europa.eu domain.'),
  ];

  $form['branding'] = [
    '#type' => 'select',
    '#title' => t('Branding'),
    '#options' => [
      'core' => t('Core'),
      'standardised' => t('Standardised'),
    ],
    '#default_value' => theme_get_setting('branding') ?? 'core',
    '#description' => t('Choose the ECL branding for your website. Use "Core" for cross-institutional sites and "Standardised" for DG/Agency specific sites.'),
  ];
}
