<?php

namespace Drupal\eic_paragraphs\Hooks;

/**
 * Class FieldWidgetFormAlter.
 *
 * Implementations for hook_field_widget_WIDGET_TYPE_form_alter().
 */
class FieldWidgetFormAlter {

  /**
   * Implements hook_field_widget_WIDGET_TYPE_form_alter().
   */
  public function paragraphs(&$element, &$form_state, $context) {
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $context['items']->getFieldDefinition();
    // Gets the paragraph field machine name.
    $paragraph_field_name = $field_definition->getName();
    $subform = &$element['subform'];
    if ($element['#paragraph_type'] == 'quote') {
      $this->paragraphsQuote($paragraph_field_name, $element, $subform);
    }
  }

  /**
   * Implementation for the quoate paragraph type.
   *
   * @param string $paragraph_field_name
   *   The machine name of the paragraph field.
   * @param array $element
   *   The element as passed to the alter hook.
   * @param array $subform
   *   The subform for the paragraph.
   */
  private function paragraphsQuote($paragraph_field_name, array $element, array &$subform): void {
    $delta = $element['#delta'];
    $selector = 'select[name="' . $paragraph_field_name . '[' . $delta . '][subform][field_view_mode][0][value]"]';
    $subform['field_tx_author']['#states'] = [
      'visible' => [
        $selector => ['value' => 'external_contributor'],
      ],
    ];
    $subform['field_ur_author']['#states'] = [
      'visible' => [
        $selector => ['value' => 'platform_member'],
      ],
    ];
  }

}
