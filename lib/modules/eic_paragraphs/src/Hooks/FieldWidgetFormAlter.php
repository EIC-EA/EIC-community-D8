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
    $subform = &$element['subform'];
    if ($element['#paragraph_type'] == 'quote') {
      $this->paragraphsQuote($element, $subform);
    }
  }

  /**
   * Implementation for the quoate paragraph type.
   *
   * @param array $element
   *   The element as passed to the alter hook.
   * @param array $subform
   *   The subform for the paragraph.
   */
  private function paragraphsQuote(array $element, array &$subform): void {
    $delta = $element;
    $selector = 'input[name="field_paragraphs[' . $delta . '][subform][field_variant]"]';
    $subform['field_tx_author']['#states'] = [
      'visible' => [
        $selector => ['value' => 'manual'],
      ],
    ];
    $subform['field_ur_author']['#states'] = [
      'visible' => [
        $selector => ['value' => 'reference'],
      ],
    ];
  }

}
