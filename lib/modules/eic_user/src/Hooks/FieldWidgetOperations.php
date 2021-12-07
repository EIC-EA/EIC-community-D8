<?php

namespace Drupal\eic_user\Hooks;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FieldWidgetOperations.
 *
 * Implementations for field widget hooks.
 */
class FieldWidgetOperations {

  /**
   * Implements hook_field_widget_social_links_form_alter().
   */
  public function fieldWidgetSocialLinksFormAlter(&$element, FormStateInterface $form_state, $context) {
    $form_build_info = $form_state->getBuildInfo();

    if ($form_build_info['base_form_id'] === 'profile_form') {
      // Adds custom element validation to fix the social network links when
      // the user inserts the full url from the social network platform.
      $element['#element_validate'] = [
        [$this, 'socialLinksFieldValidate'],
      ];
    }
  }

  /**
   * Custom element validation for social link fields.
   */
  public function socialLinksFieldValidate($element, FormStateInterface $form_state, $form) {
    $social_network_name = $element['social']['#default_value'];
    $social_network_base_url = $element['link']['#field_prefix'];
    $field_name = reset($element['#parents']);

    // Get form state values for the field.
    $form_state_values = $form_state->getValue($field_name);

    // Users will tend to add the full url from the social network platform
    // instead of just the username. Because of this issue, we remove the
    // social network base url from the values in order to pass in the
    // validation so that users don't need to figure out how to properly insert
    // their usernames.
    foreach ($form_state_values as $key => $value) {
      // Make sure full URLs start with "www".
      $value['link'] = str_replace('https://' . $value['social'], 'https://www.' . $value['social'], $value['link']);

      if ($value['social'] === $social_network_name) {
        $form_state_values[$key]['link'] = str_replace($social_network_base_url, '', $value['link']);
        $new_url = $form_state_values[$key]['link'];

        // Exception for LinkedIn since we need to prepend "in/" if missing.
        if ($social_network_name === 'linkedin' && !empty($value['link'])) {
          // Remove backslash from the beginning if exists.
          if (substr($form_state_values[$key]['link'], 0, 1) === '/') {
            $new_url = substr($form_state_values[$key]['link'], 1);
          }

          if (substr($new_url, 0, 3) !== 'in/') {
            $form_state_values[$key]['link'] = 'in/' . $new_url;
          }

          // If the link value only contains the base LinkedIn path, we clean
          // up the value.
          if ($form_state_values[$key]['link'] === 'in/') {
            $form_state_values[$key]['link'] = '';
          }
        }
        break;
      }
    }

    $form_state->setValue($field_name, $form_state_values);
  }

}
