<?php

namespace Drupal\eic_paragraph_quote\Hooks;

/**
 * Class FieldWidgetFormAlter.
 *
 * Implementations for hook_field_widget_WIDGET_TYPE_form_alter().
 */
class FieldWidgetFormAlter {

  /**
   * Implements hook_field_widget_WIDGET_TYPE_form_alter().
   */
  public function paragraphsFormAlter(&$element, &$form_state, $context) {
    if ($element['#paragraph_type'] == 'quote') {
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      $field_definition = $context['items']->getFieldDefinition();
      // Gets the paragraph field machine name.
      $paragraph_field_name = $field_definition->getName();
      $subform = &$element['subform'];
      $this->paragraphsQuoteFormAlter($paragraph_field_name, $element, $subform);
    }
  }

  /**
   * Form alter implementation for the quote paragraph type.
   *
   * @param string $paragraph_field_name
   *   The machine name of the paragraph field.
   * @param array $element
   *   The element as passed to the alter hook.
   * @param array $subform
   *   The subform for the paragraph.
   */
  private function paragraphsQuoteFormAlter($paragraph_field_name, array $element, array &$subform): void {
    $delta = $element['#delta'];
    $selector = 'select[name="' . $paragraph_field_name . '[' . $delta . '][subform][field_view_mode][0][value]"]';
    $subform['field_quote_author_ref']['#states'] = [
      'visible' => [
        $selector => ['value' => 'platform_member'],
      ],
    ];
    $subform['field_quote_author_name']['#states'] = [
      'visible' => [
        $selector => ['value' => 'external_contributor'],
      ],
    ];
    $subform['field_quote_author_image']['#states'] = [
      'visible' => [
        $selector => ['value' => 'external_contributor'],
      ],
    ];
  }

}
