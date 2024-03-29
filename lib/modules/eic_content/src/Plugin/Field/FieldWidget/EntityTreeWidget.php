<?php

namespace Drupal\eic_content\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eic_content\Services\EntityTreeManager;
use Drupal\eic_content\TreeWidget\TreeWidgetProperties;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \Drupal\eic_content\Services\EntityTreeManager
   */
  private EntityTreeManager $treeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTreeManager $tree_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->treeManager = $tree_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('eic_content.entity_tree_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['match_top_level_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Max top-level choices', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('match_top_level_limit'),
      '#min' => 0,
      '#description' => $this->t(
        'The number of top-level choices to make. Use <em>0</em> to remove the limit.',
        [],
        ['context' => 'eic_content']
      ),
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
      '#description' => $this->t(
        'It will disable the "load more" button and load all items on the current level of the tree in once.', [],
        ['context' => 'eic_content']
      ),
    ];

    $element['can_create_tag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Can create tag?', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('can_create_tag'),
      '#description' => $this->t(
        'It will allows user to create tag on the fly (only working with taxonomy terms).', [],
        ['context' => 'eic_content']
      ),
    ];

    $element['items_to_load'] = [
      '#type' => 'number',
      '#title' => $this->t('Items to load', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('items_to_load'),
      '#description' => $this->t('The number of items to load when getting more datas', [], ['context' => 'eic_content']
      ),
    ];

    $element['selected_terms_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selected terms label', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('selected_terms_label'),
      '#description' => $this->t(
        'The label to show above the search input where you can see your selected values.',
        [],
        ['context' => 'eic_content']
      ),
    ];

    $element['search_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('search_label'),
      '#description' => $this->t('The label to show under the search input.', [], ['context' => 'eic_content']
      ),
    ];

    $element['search_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder', [], ['context' => 'eic_content']),
      '#default_value' => $this->getSetting('search_placeholder'),
      '#description' => $this->t('The placeholder in the search input.', [], ['context' => 'eic_content']
      ),
    ];

    $element['auto_select_parents'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-select parents ?'),
      '#default_value' => $this->getSetting('auto_select_parents'),
      '#description' => $this->t(
        'When checking an option it will auto select all parent values in the tree',
        [],
        ['context' => 'eic_content']
      ),
    ];

    $element[TreeWidgetProperties::OPTION_IGNORE_CURRENT_USER] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore the current user in the users tree'),
      '#default_value' => $this->getSetting(TreeWidgetProperties::OPTION_IGNORE_CURRENT_USER),
      '#description' => $this->t(
        '<b>Only for "User" entity.</b> if checked, the current user will not be available.',
        [],
        ['context' => 'eic_content']
      ),
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
        TreeWidgetProperties::OPTION_IGNORE_CURRENT_USER => FALSE,
        'target_bundles' => [],
        'is_required' => FALSE,
        'can_create_tag' => FALSE,
        'search_label' => t('Select a value', [], ['context' => 'eic_search']),
        'search_placeholder' => t('Search', [], ['context' => 'eic_search']),
        'selected_terms_label' => t('Your selected values', [], ['context' => 'eic_search']),
        'has_error' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $settings = $this->getFieldSetting('handler_settings');
    $entity_type = $this->getFieldSetting('target_type');

    $user_input = $form_state->getUserInput();
    $has_required_error = (
      (
        isset($user_input[$items->getName()]) &&
        empty($user_input[$items->getName()])
      ) &&
      $this->fieldDefinition->isRequired()
    );
    $has_topics = (
      array_key_exists($items->getName(), $user_input) &&
      !empty($user_input[$items->getName()])
    );
    $input_topics = $has_topics ?
      $user_input[$items->getName()] :
      NULL;
    $preselected_items = json_encode([]);

    if ($input_topics) {
      $entities_id = explode(',', $input_topics);
      $entities_id = array_map(function($input_topic) {
        return EntityAutocomplete::extractEntityIdFromAutocompleteInput($input_topic);
      }, $entities_id);
      $entities = 'taxonomy_term' !== $entity_type ?
        $this->treeManager->getTreeWidgetProperty($entity_type)->loadEntities($entities_id) :
        Term::loadMultiple($entities_id);
      $preselected_items = $this->formatPreselection($entities, $entity_type);
    } elseif (!$has_required_error) {
      $preselected_items = $this->formatPreselection($items->referencedEntities(), $entity_type);
    }

    $options = [
      'match_top_level_limit' => $this->getSetting(TreeWidgetProperties::OPTION_ITEMS_TO_LOAD),
      TreeWidgetProperties::OPTION_ITEMS_TO_LOAD => $this->getSetting(
        TreeWidgetProperties::OPTION_ITEMS_TO_LOAD
      ),
      'auto_select_parents' => $this->getSetting('auto_select_parents'),
      'disable_top_choices' => $this->getSetting('disable_top_choices'),
      'can_create_tag' => $this->getSetting('can_create_tag'),
      TreeWidgetProperties::OPTION_LOAD_ALL => $this->getSetting(TreeWidgetProperties::OPTION_LOAD_ALL),
      TreeWidgetProperties::OPTION_IGNORE_CURRENT_USER => $this->getSetting(
        TreeWidgetProperties::OPTION_IGNORE_CURRENT_USER
      ),
      'target_bundles' => $settings['target_bundles'] ?? [],
      'is_required' => $this->fieldDefinition->isRequired(),
      'selected_terms_label' => $this->getSetting('selected_terms_label'),
      'search_label' => $this->getSetting('search_label'),
      'search_placeholder' => $this->getSetting('search_placeholder'),
      'has_error' => $has_required_error,
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

    return $element;
  }

  /**
   * Formats the preselection for the widget.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return string
   *   A JSON encoded string.
   */
  private function formatPreselection(array $entities, string $entity_type) {
    if ('taxonomy_term' !== $entity_type) {
      return $this
        ->treeManager
        ->getTreeWidgetProperty($entity_type)
        ->formatPreselection($entities);
    }

    return self::formatTaxonomyPreSelection($entities);

  }

  /**
   * Returns the formatted preselection of taxonomy terms for the widget.
   *
   * @param \Drupal\taxonomy\TermInterface[] $entities
   *   An array of taxonomy terms.
   *
   * @return false|string
   */
  public static function formatTaxonomyPreSelection(array $entities) {
    return json_encode(
      array_map(function (EntityInterface $entity) {
        $parents = $entity->get('parent')->getValue();
        $parent = reset($parents)['target_id'];
        $name = $entity->getName();

        return [
          'name' => $name,
          'tid' => $entity->id(),
          'parent' => $parent,
        ];
      }, array_values($entities))
    );
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
   *   Array of referenced entities.
   * @param string $target_type
   *   The entity type target.
   * @param string $preselected_terms
   *   Array of referenced entities formatted for FE (use formatPreselection() method).
   * @param int $number_to_load
   *   Number to load by batch.
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
   *   Return render array of entity tree widget.
   */
  public static function getEntityTreeFieldStructure(
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
    $current_user = \Drupal::currentUser();

    $options += self::defaultSettings();

    // @todo We should be able to work with multiple bundles.
    $target_bundle = empty($options['target_bundles']) ? '' : reset($options['target_bundles']);

    $element = [
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
          'select_value' => $options['search_label'],
          'match_limit' => $translation_manager->translate(
            'You can select only <b>@match_limit</b> top-level items.',
            ['@match_limit' => $options['match_top_level_limit']],
            ['context' => 'eic_search']
          ),
          'search' => $options['search_placeholder'],
          'your_values' => $options['selected_terms_label'],
          'required_field' => $translation_manager->translate(
            'This field is required',
            [],
            ['context' => 'eic_content'],
          ),
          'add_label' => $translation_manager->translate('Add', [], ['context' => 'eic_content']),
        ]),
        'data-create-term-url' => Url::fromRoute('eic_content.entity_tree_create_term')->toString(),
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
        'data-search-specific-users' => (int) ('user' === $target_type),
        // We can only create terms on the fly.
        'data-can-create-tag' =>
          (int) ($current_user->hasPermission("create terms in $target_bundle") &&
            $options['can_create_tag']),
        'data-has-error' => (int) $options['has_error'],
      ],
    ];

    $element['#attached']['library'][] = 'eic_community/react-tree-field';

    return $element;
  }

}
