<?php

namespace Drupal\eic_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\eic_search\Search\Sources\ResearchInstitutionSourceType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Research Institution Search Overview block.
 *
 * This block renders the React-based search interface for Research Institutions
 * with faceted search, sorting, and pagination capabilities.
 *
 * @Block(
 *   id = "rins_search_overview",
 *   admin_label = @Translation("Research Institution Search Overview"),
 *   category = @Translation("DDC"),
 * )
 */
final class RinsSearchOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new RinsSearchOverviewBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\eic_search\Search\Sources\ResearchInstitutionSourceType $sourceType
   *   The research institution source type service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ResearchInstitutionSourceType $sourceType,
    private readonly RequestStack $requestStack,
    private readonly AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_search.source_type.research_institution'),
      $container->get('request_stack'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $facets = $this->sourceType->getAvailableFacets();
    $sorts = $this->sourceType->getAvailableSortOptions();
    $default_sort = $this->sourceType->getDefaultSort();

    // Get current search query from URL.
    $search_value = $this->requestStack
      ->getCurrentRequest()
      ->query
      ->get('search', '');

    // Extract filter parameters from URL.
    $prefilters = $this->extractFilterFromUrl();

    // Build translations array for frontend.
    $translations = $this->buildTranslations();

    // Build API endpoint URL.
    $api_url = Url::fromRoute('eic_search.solr_search')->toString();

    // Prepare settings for drupalSettings.
    $settings = [
      'sourceBundle' => $this->sourceType->getEntityBundle(),
      'datasource' => $this->sourceType->getSourcesId(),
      'defaultSort' => $default_sort,
      'allowPagination' => $this->sourceType->allowPagination(),
      'loadMoreNumber' => $this->sourceType->getLoadMoreBatchItems(),
      'pageOptions' => [10, 20, 50, 100],
      'enableSearch' => TRUE,
      'layoutTheme' => $this->sourceType->getLayoutTheme(),
    ];

    // Cache metadata.
    $cache = [
      'contexts' => [
        'url.path',
        'url.query_args',
        'user.roles',
      ],
      'tags' => [
        'config:search_api.index.research_institution',
      ],
    ];

    // Filter virtual exclude keys from facet.field list sent to Solr.
    $exclude_keys = array_keys($this->sourceType->getExcludeFacets());
    $solr_facet_fields = array_filter(
      array_keys($facets),
      fn($key) => !in_array($key, $exclude_keys)
    );

    return [
      '#theme' => 'rins_search_overview_block',
      '#facets' => array_values($solr_facet_fields),
      '#sorts' => array_keys($sorts),
      '#translations' => $translations,
      '#settings' => $settings,
      '#url' => $api_url,
      '#search_string' => $search_value,
      '#prefilters' => $prefilters,
      '#isAnonymous' => $this->currentUser->isAnonymous(),
      '#source_class' => ResearchInstitutionSourceType::class,
      '#cache' => $cache,
      '#attached' => [
        'library' => [
          'ddc_theme/react-block-overview-search',
        ],
        'drupalSettings' => [
          'overview' => [
            'default_sorting_option' => $default_sort,
            'source_bundle_id' => $this->sourceType->getEntityBundle(),
            'is_group_owner' => FALSE,
            'is_group_admin' => FALSE,
            'is_power_user' => FALSE,
          ],
          'rinsSearch' => [
            'apiUrl' => $api_url,
            'facets' => $facets,
            'sorts' => $sorts,
            'settings' => $settings,
            'translations' => $translations,
            'currentUser' => [
              'isAnonymous' => $this->currentUser->isAnonymous(),
              'roles' => $this->currentUser->getRoles(),
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Builds translations array for React frontend.
   *
   * @return array
   *   Array of translated strings keyed by translation key.
   */
  private function buildTranslations(): array {
    return [
      'filter' => $this->t('Filter', [], ['context' => 'eic_search']),
      'refine' => $this->t('Refine your search', [], ['context' => 'eic_search']),
      'search_placeholder' => $this->t('Search institutions', [], ['context' => 'eic_search']),
      'search_text' => $this->t('Search for Research Institutions', [], ['context' => 'eic_search']),
      'no_results_title' => $this->t('No research institutions found', [], ['context' => 'eic_search']),
      'no_results_body' => $this->t('Please try again with different filters or keywords', [], ['context' => 'eic_search']),
      'clear_all' => $this->t('Clear all', [], ['context' => 'eic_search']),
      'active_filter' => $this->t('Active filter', [], ['context' => 'eic_search']),
      'sort_by' => $this->t('Sort by', [], ['context' => 'eic_search']),
      'showing' => $this->t('Showing', [], ['context' => 'eic_search']),
      'sort_any' => $this->t('- Any -', [], ['context' => 'eic_search']),
      'load_more' => $this->t('Load more', [], ['context' => 'eic_search']),
      'results_per_page' => $this->t('Results per page', [], ['context' => 'eic_search']),
      // Facet translations.
      'sm_ri_entity_type' => $this->t('Entity type', [], ['context' => 'eic_search']),
      'sm_ri_key_disciplines' => $this->t('Key disciplines', [], ['context' => 'eic_search']),
      'sm_ri_province' => $this->t('Province', [], ['context' => 'eic_search']),
      'sm_ri_transparency_level' => $this->t('Transparency level', [], ['context' => 'eic_search']),
      'sm_ri_is_sanctioned' => $this->t('Is sanctioned entity', [], ['context' => 'eic_search']),
      'sm_ri_evidence_defense_links' => $this->t('Evidence of defense links', [], ['context' => 'eic_search']),
      'sm_ri_risk_indicators' => $this->t('Risk indicators (include)', [], ['context' => 'eic_search']),
      'sm_ri_risk_indicators_exclude' => $this->t('Risk indicators (exclude)', [], ['context' => 'eic_search']),
      // Table header translations.
      'institution' => $this->t('Institution', [], ['context' => 'eic_search']),
      'entity_type' => $this->t('Entity type', [], ['context' => 'eic_search']),
      'key_disciplines' => $this->t('Key disciplines', [], ['context' => 'eic_search']),
      'risk_indicator' => $this->t('Risk indicator', [], ['context' => 'eic_search']),
    ];
  }

  /**
   * Extracts filter values from URL query parameters.
   *
   * Example URL format: ?filter[country][0]=China&filter[risk_level][0]=High
   *
   * @return array|null
   *   Array of filter values keyed by facet name, or NULL if no filters.
   */
  private function extractFilterFromUrl(): ?array {
    $filters = $this->requestStack->getCurrentRequest()
      ->query
      ->all('filter');

    if (!is_array($filters)) {
      return NULL;
    }

    // Transform filter structure for frontend consumption.
    $processed_filters = [];
    foreach ($filters as $facet => $values) {
      if (is_array($values)) {
        $processed_filters[$facet] = array_values($values);
      }
    }

    return $processed_filters ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'url.query_args',
      'user.roles',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'config:search_api.index.research_institution',
      'group_list:research_institution',
    ]);
  }

}
