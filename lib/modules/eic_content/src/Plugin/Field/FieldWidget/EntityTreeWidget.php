<?php

namespace Drupal\eic_content\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['match_top_level_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Max top-level choices', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('match_top_level_limit'),
      '#min' => 0,
      '#description' => $this->t('The number of top-level choices to make. Use <em>0</em> to remove the limit.', [], ['context' => 'eic_content']),
    ];

    $element['disable_top_choices'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable top choices selection', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('disable_top_choices'),
    ];

    $element['load_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load all items by default', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('load_all'),
      '#description' => $this->t('It will disable the "load more" button and load all items on the current level of the tree in once.', [], ['context' => 'eic_content']),
    ];

    $element['items_to_load'] = [
      '#type' => 'number',
      '#title' => $this->t('Items to load', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('items_to_load'),
      '#description' => $this->t('The number of items to load when getting more datas', [], ['context' => 'eic_content']),
    ];

    $element['auto_select_parents'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-select parents ?'),
      '#default_value' => $this->getSetting('auto_select_parents'),
      '#description' => $this->t('When checking an option it will auto select all parent values in the tree', [], ['context' => 'eic_content']),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'match_top_level_limit' => 0,
        'items_to_load' => 25,
        'auto_select_parents' => TRUE,
        'disable_top_choices' => FALSE,
        'load_all' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getFieldSetting('handler_settings');
    $target_entity = $this->fieldDefinition->getFieldStorageDefinition()
      ->getSetting('target_type');
    $target_bundles = array_key_exists('target_bundles', $settings) ? $settings['target_bundles'] : [];
    $target_bundle = reset($target_bundles);

    $element +=
      [
        '#type' => 'entity_autocomplete',
        '#default_value' => $items->referencedEntities(),
        '#tags' => TRUE,
        '#target_type' => $this->getFieldSetting('target_type'),
        '#element_validate' => [
          [static::class, 'validate'],
        ],
        '#attributes' => [
          'class' => ['hidden', 'entity-tree-reference-widget'],
          'data-selected-terms' => json_encode(array_map(function (Term $term) {
            $parent = $term->get('parent')->getValue();

            return [
              'name' => $term->getName(),
              'tid' => $term->id(),
              'parent' => reset($parent)['target_id'],
            ];
          }, $items->referencedEntities())),
          'data-translations' => json_encode([
            'select_value' => $this->t('Select a value', [], ['context' => 'eic_search']),
            'match_limit' => $this->t(
              'You can select only <b>@match_limit</b> top-level items.',
              ['@match_limit' => $this->getSetting('match_top_level_limit')],
              ['context' => 'eic_search']
            ),
            'search' => $this->t('Search', [], ['context' => 'eic_search']),
            'your_values' => $this->t('Your selected values', [], ['context' => 'eic_search']),
          ]),
          'data-terms-url' => Url::fromRoute('eic_content.entity_tree')
            ->toString(),
          'data-terms-url-search' => Url::fromRoute('eic_content.entity_tree_search')
            ->toString(),
          'data-terms-url-children' => Url::fromRoute('eic_content.entity_tree_children')
            ->toString(),
          'data-match-limit' => $this->getSetting('match_top_level_limit'),
          'data-items-to-load' => $this->getSetting('items_to_load'),
          'data-disable-top' => $this->getSetting('disable_top_choices'),
          'data-load-all' => $this->getSetting('load_all'),
          'data-target-bundle' => $target_bundle,
          'data-target-entity' => $target_entity,
        ],
      ];

    $element['#attached']['library'][] = 'eic_community/react-tree-field';

    return $element;
  }

  public static function validate($element, FormStateInterface $form_state) {
    $values = $element['#value'];

    if (!is_string($values)) {
      return;
    }

    $values = explode(',', $values);
    $terms = [];

    foreach ($values as $value) {
      $term_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($value);
      if ($term_id) {
        $terms[] = ['target_id' => $term_id];
      }
    }

    $form_state->setValueForElement($element, $terms);
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
