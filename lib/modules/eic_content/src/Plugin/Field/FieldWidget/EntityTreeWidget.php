<?php

namespace Drupal\eic_content\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

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

    $element['ignore_current_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore the current user in the users tree'),
      '#default_value' => $this->getSetting('ignore_current_user'),
      '#description' => $this->t('<b>Only for "User" entity.</b> if checked, the current user will not be available.', [], ['context' => 'eic_content']),
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
        'ignore_current_user' => FALSE,
        'target_bundles' => [],
        'is_required' => FALSE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getFieldSetting('handler_settings');

    $preselected_items = self::formatPreselection($items->referencedEntities());

    $options = [
      'match_top_level_limit' => $this->getSetting('items_to_load'),
      'items_to_load' => $this->getSetting('items_to_load'),
      'auto_select_parents' => $this->getSetting('auto_select_parents'),
      'disable_top_choices' => $this->getSetting('disable_top_choices'),
      'load_all' => $this->getSetting('load_all'),
      'ignore_current_user' => $this->getSetting('ignore_current_user'),
      'target_bundles' => $settings['target_bundles'] ?? [],
      'is_required' => $this->fieldDefinition->isRequired(),
    ];

    $element += self::getEntityTreeFieldStructure(
      $items->referencedEntities(),
      $this->getFieldSetting('target_type'),
      $preselected_items,
      $this->getSetting('items_to_load'),
      Url::fromRoute('eic_content.entity_tree')->toString(),
      Url::fromRoute('eic_content.entity_tree_search')->toString(),
      Url::fromRoute('eic_content.entity_tree_children')->toString(),
      $options
    );

    $element['#attached']['library'][] = 'eic_community/react-tree-field';

    return $element;
  }

  /**
   * Formats the preselection for the widget.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities.
   *
   * @return string
   *   A JSON encoded string.
   */
  public static function formatPreselection(array $entities) {
    return json_encode(array_map(function (EntityInterface $entity) {
      if ($entity instanceof TermInterface) {
        $parents = $entity->get('parent')->getValue();
        $parent = reset($parents)['target_id'];
        $name = $entity->getName();
      }

      if ($entity instanceof UserInterface) {
        $parent = 0;
        $name = realname_load($entity) . ' ' . '('. $entity->getEmail() .')';
      }

      return [
        'name' => $name,
        'tid' => $entity->id(),
        'parent' => $parent,
      ];
    }, $entities));
  }

  /**
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function validate($element, FormStateInterface $form_state) {
    $form_state->setValueForElement($element, self::extractEntitiesFromWidget($element['#value']));
  }

  /**
   * @param $element
   *
   * @return array
   */
  public static function extractEntitiesFromWidget($element) {
    if (!is_string($element)) {
      return [];
    }

    $values = explode(',', $element);
    $entities = [];

    foreach ($values as $value) {
      $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($value);
      if ($entity_id) {
        $entities[] = ['target_id' => $entity_id];
      }
    }

    return $entities;
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

  /**
   * Returns an array for the entity_tree widget.
   *
   * This array can be used for widget form elements (Field API) or in custom
   * forms.
   *
   * @param array $default_values
   * @param string $target_type
   * @param string $preselected_terms
   * @param int $number_to_load
   * @param array $options
   *   A config array with following optional keys:
   *   - match_top_level_limit: (int) Maximum number of top-level selection.
   *   - items_to_load: (int) Number of items to load with load more.
   *   - auto_select_parents: (bool) Whether to auto-select parents when
   *     selecting a child.
   *   - disable_top_choices: (bool) Whether to disable the top-level selection.
   *   - load_all: (bool) Whether to load all items.
   *   - ignore_current_user: (bool) Whether to ignore the current user in the
   *     user tree.
   *   - target_bundles: (array) The selectable bundles. Defaults to all.
   *   - is_required: (bool) Indicates if field is required.
   *
   * @return array
   */
  public static function getEntityTreeFieldStructure (
    array $default_values,
    string $target_type,
    string $preselected_terms,
    int $number_to_load,
    string $base_url,
    string $base_url_search,
    string $base_url_search_children,
    array $options = []
  ): array {
    /** @var \Drupal\Core\StringTranslation\TranslationManager $translation_manager */
    $translation_manager = \Drupal::service('string_translation');

    $options += self::defaultSettings();

    // @todo We should be able to work with multiple bundles.
    $target_bundle = empty($options['target_bundles']) ? '' : reset($options['target_bundles']);

    return [
      '#type' => 'entity_autocomplete',
      '#default_value' => $default_values,
      '#tags' => TRUE,
      '#target_type' => $target_type,
      '#maxlength' => 5000,
      '#element_validate' => [
        [static::class, 'validate'],
      ],
      '#attributes' => [
        'class' => ['hidden', 'entity-tree-reference-widget'],
        'data-selected-terms' => $preselected_terms,
        'data-translations' => json_encode([
          'select_value' => $translation_manager->translate('Select a value', [], ['context' => 'eic_search']),
          'match_limit' => $translation_manager->translate(
            'You can select only <b>@match_limit</b> top-level items.',
            ['@match_limit' => $options['match_top_level_limit']],
            ['context' => 'eic_search']
          ),
          'search' => $translation_manager->translate('Search', [], ['context' => 'eic_search']),
          'your_values' => $translation_manager->translate('Your selected values', [], ['context' => 'eic_search']),
          'required_field' => $translation_manager->translate('This field is required', [], ['context' => 'eic_content']),
        ]),
        'data-terms-url' => $base_url,
        'data-terms-url-search' => $base_url_search,
        'data-terms-url-children' => $base_url_search_children,
        'data-match-limit' => $options['match_top_level_limit'],
        'data-items-to-load' => $number_to_load,
        'data-disable-top' => (int) $options['disable_top_choices'],
        'data-load-all' => (int) $options['load_all'],
        'data-ignore-current-user' => (int) $options['ignore_current_user'],
        'data-target-bundle' => $target_bundle,
        'data-target-entity' => $target_type,
        'data-is-required' => (int) $options['is_required'],
      ],
    ];
  }

}
