<?php

namespace Drupal\eic_paragraphs\Hooks;

/**
 * Class FieldWidgetFormAlter.
 *
 * Description: Implementations for hook_field_widget_WIDGET_TYPE_form_alter().
 */
class FieldWidgetFormAlter {

  /**
   * Field config Definition.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  private $fieldDefinition;

  /**
   * Paragraph Field Name.
   *
   * @var string
   */
  private $paragraphFieldName;

  /**
   * The subform.
   *
   * @var array
   */
  private $subform;

  /**
   * Implements hook_field_widget_WIDGET_TYPE_form_alter().
   */
  public function paragraphsFormAlter(&$element, &$form_state, $context) {
    switch ($element['#paragraph_type']) {
      case 'quote':
        $this->setFormVariables($element, $context);
        $displayTypeFields = [
          'platform_member' => [
            'field_user_ref',
          ],
          'external_person' => [
            'field_name',
            'field_media',
          ],
        ];
        $this->configureFormDisplay($element, $displayTypeFields);
        break;

      case 'contributor':
        $this->setFormVariables($element, $context);
        $displayTypeFields = [
          'platform_member' => [
            'field_user_ref',
          ],
          'external_person' => [
            'field_name',
            'field_media',
            'field_person_email',
            'field_organisation',
            'field_contributor_link',
          ],
        ];
        $this->configureFormDisplay($element, $displayTypeFields);
        break;
    }

  }

  /**
   * Private function to set Form Variables in Class variables.
   *
   * Setting form variables.
   *
   * @param mixed $element
   *   Form Element.
   * @param array $context
   *   Form Context.
   */
  private function setFormVariables(&$element, array $context): void {
    $this->fieldDefinition = $context['items']->getFieldDefinition();
    // Gets the paragraph field machine name.
    $this->paragraphFieldName = $this->fieldDefinition->getName();
    $this->subform = &$element['subform'];
  }

  /**
   * Private function to configure Form Display with states logic.
   *
   * Form Element.
   *
   * @param mixed $element
   *   Form Element.
   * @param array $displayTypeFields
   *   Display View Config.
   */
  private function configureFormDisplay(&$element, array $displayTypeFields): void {
    $delta = $element['#delta'];
    $selector = 'select[name="' . $this->paragraphFieldName . '[' . $delta .
      '][subform][paragraph_view_mode][0][value]"]';

    foreach ($displayTypeFields as $displayType => $fields) {
      foreach ($fields as $field) {
        $this->subform[$field]['#states'] = [
          'visible' => [
            $selector => ['value' => $displayType],
          ],
        ];
      }
    }

  }

}
