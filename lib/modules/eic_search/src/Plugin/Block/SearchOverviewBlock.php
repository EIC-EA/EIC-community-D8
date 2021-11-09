<?php

namespace Drupal\eic_search\Plugin\Block;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Collector\SourcesCollector;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
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
   * @var EICGroupsHelper $groupsHelper
   */
  protected $groupsHelper;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param SourcesCollector $sources_collector
   * @param EICGroupsHelper $groups_helper
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourcesCollector $sources_collector, EICGroupsHelper $groups_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sourcesCollector = $sources_collector;
    $this->groupsHelper = $groups_helper;
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
      $container->get('eic_search.sources_collector'),
      $container->get('eic_groups.helper'),
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

    $search_value = \Drupal::request()->query->get('search', '');

    $facets = array_filter($facets, function ($facet) {
      return $facet;
    });

    $sorts = array_filter($sorts, function ($sort) {
      return $sort;
    });

    $source_type = $this->configuration['source_type'];
    $sources = $this->sourcesCollector->getSources();

    $source = array_key_exists($source_type, $sources) ? $sources[$source_type] : NULL;
    $prefilter_group = $this->configuration['prefilter_group'] ?? FALSE;
    $current_group_route = NULL;

    if (
      $source instanceof SourceTypeInterface &&
      $source->ableToPrefilteredByGroup() &&
      $prefilter_group
    ) {
      $current_group_route = $this->groupsHelper->getGroupFromRoute();
    }

    $user_group_roles = [];

    if ($current_group_route) {
      $account = \Drupal::currentUser();
      $membership = $current_group_route->getMember($account);
      $user_group_roles = $membership instanceof GroupMembership ? $membership->getRoles() : [];
    }

    $build['#attached']['drupalSettings']['overview'] = [
      'is_group_owner' => array_key_exists(EICGroupsHelper::GROUP_OWNER_ROLE, $user_group_roles),
      'source_bundle_id' => $source->getEntityBundle(),
      'default_sorting_option' => $source->getDefaultSort(),
    ];

    return $build + [
      '#theme' => 'search_overview_block',
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
      '#facets' => array_keys($facets),
      '#sorts' => array_keys($sorts),
      '#search_string' => $search_value,
      '#source_class' => $source instanceof SourceTypeInterface ? get_class($source) : NULL,
      '#datasource' => $source instanceof SourceTypeInterface ? $source->getSourcesId() : NULL,
      '#bundle' => $source instanceof SourceTypeInterface ? $source->getEntityBundle() : NULL,
      '#layout' => $source instanceof SourceTypeInterface ? $source->getLayoutTheme() : NULL,
      '#page_options' => $this->configuration['page_options'],
      '#enable_search' => $this->configuration['enable_search'],
      '#url' => Url::fromRoute('eic_groups.solr_search')->toString(),
      '#isAnonymous' => \Drupal::currentUser()->isAnonymous(),
      '#currentGroup' => $current_group_route instanceof GroupInterface ? $current_group_route->id() : NULL,
      '#currentGroupUrl' => $current_group_route instanceof GroupInterface ? $current_group_route->toUrl()->toString() : NULL,
      '#enable_facet_interests' => $this->configuration['add_facet_interests'],
      '#enable_facet_my_groups' => $this->configuration['add_facet_my_groups'],
      '#isGroupOwner' => array_key_exists(EICGroupsHelper::GROUP_OWNER_ROLE, $user_group_roles),
      '#allow_pagination' => $source instanceof SourceTypeInterface ? (int) $source->allowPagination() : 1,
      '#load_more_number' => SourceTypeInterface::READ_MORE_NUMBER_TO_LOAD,
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
        'label_video' => $this->t('Video', [], ['context' => 'eic_group']),
        'label_file' => $this->t('File', [], ['context' => 'eic_group']),
        'label_image' => $this->t('Image', [], ['context' => 'eic_group']),
        'like' => $this->t('Like', [], ['context' => 'eic_group']),
        'add_video' => $this->t('Add video', [], ['context' => 'eic_group']),
        'add_document' => $this->t('Add document', [], ['context' => 'eic_group']),
        'add_gallery' => $this->t('Add gallery', [], ['context' => 'eic_group']),
        'post_content' => $this->t('Post content', [], ['context' => 'eic_group']),
        'uploaded_by' => $this->t('Uploaded by', [], ['context' => 'eic_group']),
        'draft' => $this->t('Draft', [], ['context' => 'eic_group']),
        'pending' => $this->t('Pending', [], ['context' => 'eic_group']),
        'load_more' => $this->t('Load more', [], ['context' => 'eic_group']),
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
    if ($form_state->hasValue('field_overview_block')) {
      $current_source_value = $form_state->getValue('field_overview_block')[0]['settings']['search']['source_type'] ?: NULL;
    } else {
      $current_source_value = $form_state->getValue('settings')['search']['source_type'] ?: GroupSourceType::class;
    }

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

    if (!array_key_exists('search', $values)) {
      return;
    }

    //First reset values
    $this->configuration['facets'] = [];
    $this->configuration['sort_options'] = [];

    $current_source = $this->getCurrentSource($values['search']['source_type']);

    $this->setConfiguration([
      'source_type' => $values['search']['source_type'],
      'facets' => $values['search']['configuration']['filter'][$current_source->getEntityBundle()]['facets'],
      'sort_options' => $values['search']['configuration']['sorts'][$current_source->getEntityBundle()]['sort_options'],
      'enable_search' => $values['search']['configuration']['enable_search'],
      'page_options' => $values['search']['configuration']['pagination']['page_options'],
      'prefilter_group' => $values['search']['configuration']['prefilter_group'],
      'add_facet_interests' => $values['search']['configuration']['add_facet_interests'],
      'add_facet_my_groups' => $values['search']['configuration']['add_facet_my_groups'],
    ]);
  }

  /**
   * @param string $source_value
   *
   * @return SourceTypeInterface|null
   */
  private function getCurrentSource(string $source_value): ?SourceTypeInterface {
    $sources_collected = $this->sourcesCollector->getSources();

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

      if ($source->ableToPrefilteredByGroup()) {
        $form['search']['configuration']['prefilter_group'] = [
          '#type' => 'checkbox',
          '#default_value' => $this->configuration['prefilter_group'],
          '#title' => $this->t('Prefilter the overview by group', [], ['context' => 'eic_search']),
          '#description' => $this->t('It will prefiltered the overview with the current group from page', [], ['context' => 'eic_search']),
        ];
      }
    }

    $form['search']['configuration']['add_facet_my_groups'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['add_facet_my_groups'],
      '#title' => $this->t('Add filter for my groups/contents', [], ['context' => 'eic_search']),
      '#description' => $this->t('Add new checkbox on the sidebar to filter only user groups/contents', [], ['context' => 'eic_search']),
    ];

    $form['search']['configuration']['add_facet_interests'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['add_facet_interests'],
      '#title' => $this->t('Add filter for my topics of interests', [], ['context' => 'eic_search']),
      '#description' => $this->t('Add new checkbox on the sidebar to filter only topics of interests from user', [], ['context' => 'eic_search']),
    ];
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
