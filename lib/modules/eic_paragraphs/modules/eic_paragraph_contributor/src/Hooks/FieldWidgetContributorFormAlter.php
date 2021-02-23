<?php

namespace Drupal\eic_paragraph_contributor\Hooks;

/**
 * Class FieldWidgetFormAlter.
 *
 * Implementations for hook_field_widget_WIDGET_TYPE_form_alter().
 */
class FieldWidgetContributorFormAlter {

  /**
   * Implements hook_field_widget_WIDGET_TYPE_form_alter().
   */
  public function paragraphsFormAlter(&$element, &$form_state, $context) {
    if ($element['#paragraph_type'] == 'contributor') {
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      $field_definition = $context['items']->getFieldDefinition();
      // Gets the paragraph field machine name.
      $paragraph_field_name = $field_definition->getName();
      $subform = &$element['subform'];
      $this->paragraphsContributorFormAlter($paragraph_field_name, $element, $subform);
    }
  }

  /**
   * Form alter implementation for the contributor paragraph type.
   *
   * @param string $paragraph_field_name
   *   The machine name of the paragraph field.
   * @param array $element
   *   The element as passed to the alter hook.
   * @param array $subform
   *   The subform for the paragraph.
   */
  private function paragraphsContributorFormAlter($paragraph_field_name, array $element, array &$subform): void {
    $delta = $element['#delta'];
    $selector = 'select[name="' . $paragraph_field_name . '[' . $delta . '][subform][paragraph_view_mode][0][value]"]';

    $subform['field_user_ref']['#states'] = [
      'visible' => [
        $selector => ['value' => 'platform_member'],
      ],
    ];
    $subform['field_name']['#states'] = [
      'visible' => [
        $selector => ['value' => 'external_person'],
      ],
    ];
    $subform['field_media']['#states'] = [
      'visible' => [
        $selector => ['value' => 'external_person'],
      ],
    ];
    $subform['field_person_email']['#states'] = [
      'visible' => [
        $selector => ['value' => 'external_person'],
      ],
    ];
    $subform['field_organisation']['#states'] = [
      'visible' => [
        $selector => ['value' => 'external_person'],
      ],
    ];
  }

}
