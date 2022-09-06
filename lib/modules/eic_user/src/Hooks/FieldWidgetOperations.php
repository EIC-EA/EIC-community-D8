<?php

namespace Drupal\eic_user\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eic_helper\SocialLinksFieldHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FieldWidgetOperations.
 *
 * Implementations for field widget hooks.
 */
class FieldWidgetOperations implements ContainerInjectionInterface {

  /**
   * The social links field helper.
   *
   * @var \Drupal\eic_helper\SocialLinksFieldHelper
   */
  protected $socialLinksFieldHelper;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\eic_helper\SocialLinksFieldHelper $social_link_helper
   *   The social links field helper.
   */
  public function __construct(SocialLinksFieldHelper $social_link_helper) {
    $this->socialLinksFieldHelper = $social_link_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_helper.social_link_field')
    );
  }

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
    $field_name = reset($element['#parents']);

    // Get form state values for the field.
    $form_state_values = $form_state->getValue($field_name);

    // Users will tend to add the full url from the social network platform
    // instead of just the username. Because of this issue, we remove the
    // social network base url from the values in order to pass in the
    // validation so that users don't need to figure out how to properly insert
    // their usernames.
    foreach ($form_state_values as $key => $value) {
      $social_network_name = $element['social']['#default_value'];
      $form_state_values[$key]['link'] = $this->socialLinksFieldHelper->cleanUpSocialLinkValue(
        $social_network_name,
        $value['link']
      );
    }

    $form_state->setValue($field_name, $form_state_values);
  }

}
