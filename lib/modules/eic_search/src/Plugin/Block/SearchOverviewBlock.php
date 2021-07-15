<?php

namespace Drupal\eic_search\Plugin\Block;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\eic_search\Collector\SourcesCollector;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an SearchOverviewBlock block.
 *
 * @Block(
 *   id = "eic_search_overview",
 *   admin_label = @Translation("EIC Search Overview"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class SearchOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var SourcesCollector sourcesCollector
   */
  protected $sourcesCollector;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param SourcesCollector $sources_collector
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourcesCollector $sources_collector) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sourcesCollector = $sources_collector;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_search.sources_collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $sources_collected = $this->sourcesCollector->getSources();
    $sources = [];

    $current_source_value = $this->configuration['source_type'] ?: GroupSourceType::class;
    $current_source = array_key_exists($current_source_value, $sources_collected) ?
      $sources_collected[$current_source_value] :
      NULL;

    /** @var SourceTypeInterface $source */
    foreach ($sources_collected as $source) {
      $sources[get_class($source)] = $source->getLabel();
    }

    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search overview', [], ['context' => 'eic_search']),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $form['search']['source_type'] = [
      '#type' => 'select',
      '#default_value' => $current_source_value,
      '#title' => $this->t('Source type', [], ['context' => 'eic_search']),
      '#description' => $this->t('For which entity type do you want to create the view for ?', [], ['context' => 'eic_search']),
      '#options' => $sources,
      '#ajax' => [
        'callback' => [$this, 'updateSourceConfig'],
        'disable-refocus' => FALSE,
        // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => 'source-configuration',
        // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading new configuration ...', [], ['context' => 'eic_search']),
        ],
      ],
    ];

    $this->addConfigurationRenderer($form, $current_source ?: reset($sources_collected));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $facets = $this->configuration['facets'];
    $sorts = $this->configuration['sort_options'];

    $facets = array_filter($facets, function ($facet) {
      return $facet;
    });

    $sorts = array_filter($sorts, function ($sort) {
      return $sort;
    });

    $source_type = $this->configuration['source_type'];
    $sources = $this->sourcesCollector->getSources();

    $source = array_key_exists($source_type, $sources) ? $sources[$source_type] : NULL;

    return [
      '#theme' => 'search_overview_block',
      '#facets' => array_keys($facets),
      '#sorts' => array_keys($sorts),
      '#source_class' => $source instanceof SourceTypeInterface ? get_class($source) : NULL,
      '#datasource' => $source instanceof SourceTypeInterface ? $source->getSourceId() : NULL,
      '#bundle' => $source instanceof SourceTypeInterface ? $source->getEntityBundle() : NULL,
      '#layout' => $source instanceof SourceTypeInterface ? $source->getLayoutTheme() : NULL,
      '#page_options' => $this->configuration['page_options'],
      '#enable_search' => $this->configuration['enable_search'],
      '#url' => Url::fromRoute('eic_groups.solr_search')->toString(),
      '#isAnonymous' => \Drupal::currentUser()->isAnonymous(),
      '#translations' => [
        'public' => $this->t('Public', [], ['context' => 'eic_group']),
        'private' => $this->t('Private', [], ['context' => 'eic_group']),
        'filter' => $this->t('Filter', [], ['context' => 'eic_group']),
        'topics' => $this->t('Topics', [], ['context' => 'eic_group']),
        'search_text' => $this->t('Search', [], ['context' => 'eic_group']),
        'no_results' => $this->t('No results', [], ['context' => 'eic_group']),
        'members' => $this->t('Members', [], ['context' => 'eic_group']),
        'reactions' => $this->t('Reactions', [], ['context' => 'eic_group']),
        'documents' => $this->t('Documents', [], ['context' => 'eic_group']),
        'clear_all' => $this->t('Clear all', [], ['context' => 'eic_group']),
        'active_filter' => $this->t('Active filter', [], ['context' => 'eic_group']),
        'sort_by' => $this->t('Sort by', [], ['context' => 'eic_group']),
        'showing' => $this->t('Showing', [], ['context' => 'eic_group']),
        'sort_any' => $this->t('- Any -', [], ['context' => 'eic_group']),
      ],
    ];
  }

  /**
   * The ajax callback that will hide non selected sources parameters (facets,
   * sorts)
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function updateSourceConfig(array &$form, FormStateInterface $form_state) {
    $current_source_value = $form_state->getValue('settings')['search']['source_type'] ?: GroupSourceType::class;
    $response = new AjaxResponse();

    /** @var SourceTypeInterface $current_source */
    $current_source = $this->getCurrentSource($current_source_value);

    $css = ['display' => 'none'];


    $sources_collected = $this->sourcesCollector->getSources();
    foreach ($sources_collected as $source) {
      $response->addCommand(new CssCommand('.source-' . $source->getEntityBundle(), $css));
    }

    $response->addCommand(new CssCommand('.source-' . $current_source->getEntityBundle(), ['display' => 'inline-block']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    //First reset values
    $this->configuration['facets'] = [];
    $this->configuration['sort_options'] = [];

    $current_source = $this->getCurrentSource($values['search']['source_type']);

    $this->configuration['source_type'] = $values['search']['source_type'];
    $this->configuration['facets'] = $values['search']['configuration']['filter'][$current_source->getEntityBundle()]['facets'];
    $this->configuration['sort_options'] = $values['search']['configuration']['sorts'][$current_source->getEntityBundle()]['sort_options'];
    $this->configuration['enable_search'] = $values['search']['configuration']['enable_search'];
    $this->configuration['page_options'] = $values['search']['configuration']['pagination']['page_options'];
  }

  /**
   * @param string $source_value
   *
   * @return SourceTypeInterface|null
   */
  private function getCurrentSource(string $source_value): ?SourceTypeInterface {
    $sources_collected = $this->sourcesCollector->getSources();

    /** @var SourceTypeInterface $current_source */
    return array_key_exists($source_value, $sources_collected) ?
      $sources_collected[$source_value] :
      NULL;
  }

  /**
   * @param array $form
   * @param SourceTypeInterface $current_source
   */
  private function addConfigurationRenderer(array &$form, SourceTypeInterface $current_source) {
    $form['search']['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration', [], ['context' => 'eic_search']),
      '#open' => TRUE,
      '#weight' => 4,
    ];

    $form['search']['configuration']['enable_search'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enable_search'],
      '#title' => $this->t('Enable search ?', [], ['context' => 'eic_search']),
      '#description' => $this->t('Do you want to enable the search box feature into the overview ?', [], ['context' => 'eic_search']),
    ];

    $form['search']['configuration']['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Facets', [], ['context' => 'eic_search']),
      '#open' => TRUE,
    ];

    $form['search']['configuration']['sorts'] = [
      '#type' => 'details',
      '#title' => $this->t('Sorts', [], ['context' => 'eic_search']),
      '#open' => TRUE,
    ];

    $form['search']['configuration']['pagination'] = [
      '#type' => 'details',
      '#title' => $this->t('Pagination', [], ['context' => 'eic_search']),
      '#open' => TRUE,
    ];

    $form['search']['configuration']['pagination']['page_options'] = [
      '#type' => 'select',
      '#default_value' => $this->configuration['page_options'],
      '#title' => $this->t('Number per page options', [], ['context' => 'eic_search']),
      '#options' => [
        'normal' => '10/20/50/100',
        'each_10' => '10/20/30/40/50',
        'each_6' => '6/12/18/24',
      ],
    ];

    /** @var SourceTypeInterface $source */
    foreach ($this->sourcesCollector->getSources() as $source) {
      $form['search']['configuration']['filter'][$source->getEntityBundle()]['facets'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Facets for @label', ['@label' => $source->getLabel()], ['context' => 'eic_search']),
        '#description' => $this->t('Filters that you want to be available on the overview', [], ['context' => 'eic_search']),
        '#default_value' => $this->configuration['facets'],
        '#options' => $this->generateFacetsOptions($source),
        '#attributes' => [
          'class' => [
            'source-type',
            'source-' . $source->getEntityBundle(),
            $current_source === $source ?: 'hidden',
          ],
        ],
      ];

      $form['search']['configuration']['sorts'][$source->getEntityBundle()]['sort_options'] = [
        '#type' => 'checkboxes',
        '#default_value' => $this->configuration['sort_options'],
        '#title' => $this->t('Sorting for @label', ['@label' => $source->getLabel()], ['context' => 'eic_search']),
        '#description' => $this->t('Choose available sorting options on the overview', [], ['context' => 'eic_search']),
        '#options' => $this->generateSortOptions($source),
        '#attributes' => [
          'class' => [
            'source-type',
            'source-' . $source->getEntityBundle(),
            $current_source === $source ?: 'hidden',
          ],
        ],
      ];
    }
  }

  /**
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $current_source
   *
   * @return array
   */
  private function generateFacetsOptions(SourceTypeInterface $current_source): array {
    $available_facets = [];

    foreach ($current_source->getAvailableFacets() as $facet => $label) {
      $available_facets[$facet] = $label;
    }

    return $available_facets;
  }

  /**
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $current_source
   *
   * @return array
   */
  private function generateSortOptions(SourceTypeInterface $current_source): array {
    $available_sorts = [];

    foreach ($current_source->getAvailableSortOptions() as $sort_option => $options) {
      $available_sorts[$sort_option] = $options['label'];
    }

    return $available_sorts;
  }

}
