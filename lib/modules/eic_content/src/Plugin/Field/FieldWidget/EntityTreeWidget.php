<?php

namespace Drupal\eic_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_tree' widget.
 *
 * @FieldWidget(
 *   id = "entity_tree",
 *   label = @Translation("Entity tree"),
 *   description = @Translation("Display the entities tree with search"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityTreeWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element +
      [
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#attributes' => ['class' => ['testy']],
      ];

    $element['#attached']['library'][] = 'eic_community/library_name';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'default_color' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if (!empty($this->getSetting('default_color'))) {
      $summary[] = t('Default color: @placeholder', [
        '@placeholder' => $this->getSetting('default_color'),
      ]);
    }

    return $summary;
  }
}
