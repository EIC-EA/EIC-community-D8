<?php

namespace Drupal\eic_groups\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oec_group_flex\OECGroupFlexHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FieldWidgetOperations.
 *
 * Implementations for field widget hooks.
 */
class FieldWidgetOperations implements ContainerInjectionInterface {

  /**
   * The group flex helper service.
   *
   * @var \Drupal\oec_group_flex\OECGroupFlexHelper
   */
  protected $oecGroupFlexHelper;

  /**
   * Constructs a new FieldWidgetOperations object.
   *
   * @param \Drupal\oec_group_flex\OECGroupFlexHelper $oec_group_flex_helper
   *   The group flex helper service.
   */
  public function __construct(OECGroupFlexHelper $oec_group_flex_helper) {
    $this->oecGroupFlexHelper = $oec_group_flex_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oec_group_flex.helper')
    );
  }

  /**
   * Implements hook_field_widget_entity_reference_autocomplete_form_alter().
   */
  public function fieldWidgetEntityReferenceAutocompleteFormAlter(&$element, FormStateInterface $form_state, $context) {
    $form_build_info = $form_state->getBuildInfo();

    if ($form_build_info['base_form_id'] == 'group_form' && $form_build_info['form_id'] == 'group_group_edit_form') {
      /** @var \Drupal\Core\Field\FieldItemListInterface $items */
      $items = $context['items'];

      // Gets the entity type ID the field is attached to .
      $entity_type = $items->getFieldDefinition()->getTargetEntityTypeId();

      // We do nothing if entity type is not a group.
      if ($entity_type !== 'group') {
        return;
      }

      // Gets the field machine name.
      $field_name = $items->getFieldDefinition()->getName();

      // We do nothing if the field is not the Related News and Stories.
      if ($field_name !== 'field_related_news_stories') {
        return;
      }

      $group = $form_state->getFormObject()->getEntity();

      // Gets visibility settings of the group.
      $group_visibility_settings = $this->oecGroupFlexHelper->getGroupVisibilitySettings($group);

      // If group is public we can only reference public News and Stories.
      if ($group_visibility_settings['plugin_id'] == 'public') {
        $element['target_id']['#selection_settings']['view']['display_name'] = 'entity_ref_public_news_stories';
      }
    }
  }

}
