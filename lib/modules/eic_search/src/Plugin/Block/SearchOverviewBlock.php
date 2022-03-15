<?php

namespace Drupal\eic_search\Plugin\Block;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_search\Collector\SourcesCollector;
use Drupal\eic_search\Search\Sources\GroupSourceType;
use Drupal\eic_search\Search\Sources\Profile\ActivityStreamSourceType;
use Drupal\eic_search\Search\Sources\SourceTypeInterface;
use Drupal\eic_search\SearchHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * @var RequestStack $requestStack
   */
  protected $requestStack;

  /**
   * @var RouteMatchInterface $routeMatch
   */
  protected $routeMatch;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param SourcesCollector $sources_collector
   * @param EICGroupsHelper $groups_helper
   * @param RequestStack $request_stack
   * @param RouteMatchInterface $route_match
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SourcesCollector $sources_collector,
    EICGroupsHelper $groups_helper,
    RequestStack $request_stack,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sourcesCollector = $sources_collector;
    $this->groupsHelper = $groups_helper;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
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
      $container->get('eic_search.sources_collector'),
      $container->get('eic_groups.helper'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $sources_collected = $this->sourcesCollector->getSources();
    $sources = [];

    $current_source_value = $this->configuration['source_type'] ?: GroupSourceType::class;
    $current_source = array_key_exists(
      $current_source_value,
      $sources_collected
    ) ?
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
      '#description' => $this->t(
        'For which entity type do you want to create the view for ?', [],
        ['context' => 'eic_search']
      ),
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
          'message' => $this->t(
            'Loading new configuration ...',
            [],
            ['context' => 'eic_search']
          ),
        ],
      ],
    ];

    $this->addConfigurationRenderer(
      $form,
      $current_source ?: reset($sources_collected)
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $facets = $this->configuration['facets'];
    $sorts = $this->configuration['sort_options'];

    $search_value = $this->requestStack
      ->getCurrentRequest()
      ->query
      ->get('search', '');

    $facets = array_filter($facets, function ($facet) {
      return $facet;
    });

    $sorts = array_filter($sorts, function ($sort) {
      return $sort;
    });

    $source_type = $this->configuration['source_type'];
    $sources = $this->sourcesCollector->getSources();

    $source = array_key_exists(
      $source_type,
      $sources
    ) ? $sources[$source_type] : NULL;
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
    $account = NULL;

    $group_admins = [
      'owner' => -1,
      'admins' => [],
    ];

    if ($current_group_route) {
      $account = \Drupal::currentUser();
      $membership = $current_group_route->getMember($account);
      $user_group_roles = $membership instanceof GroupMembership ? $membership->getRoles() : [];

      // Send as props group admin/owners.
      $owners = $current_group_route->getMembers(
        [EICGroupsHelper::GROUP_OWNER_ROLE]
      );
      $admins = $current_group_route->getMembers(
        [EICGroupsHelper::GROUP_ADMINISTRATOR_ROLE]
      );
      $owner = reset($owners);

      $group_admins = [
        'owner' => $owner instanceof GroupMembership ? $owner->getUser()->id() : -1,
        'admin' => array_map(function (GroupMembership $admin) {
          return $admin->getUser()->id();
        }, $admins),
      ];
    }

    $build['#attached']['drupalSettings']['overview'] = [
      'is_group_owner' => array_key_exists(
        EICGroupsHelper::GROUP_OWNER_ROLE,
        $user_group_roles
      ),
      'label_my_groups' => $source->getLabelFilterMyGroups(),
      'registration_filter' => $this->t('Open registration', [], ['context' => 'eic_search']),
      'is_group_admin' => array_key_exists(
        EICGroupsHelper::GROUP_ADMINISTRATOR_ROLE,
        $user_group_roles
      ),
      'is_power_user' => $account instanceof AccountInterface && UserHelper::isPowerUser(
          $account
        ),
      'source_bundle_id' => $source->getEntityBundle(),
      'default_sorting_option' => $source->getDefaultSort(),
      'filter_label' => [
        'ss_global_content_type' => [
          'news' => t('News article', [], ['context' => 'eic_search']),
        ],
        'bs_content_is_private' => [
          'false' => t('Public', [], ['context' => 'eic_search']),
          'true' => t('Private', [], ['context' => 'eic_search']),
        ],
      ],
    ];

    $route_name = $this->routeMatch->getRouteName();

    return $build + [
        '#theme' => 'search_overview_block',
        '#cache' => ['contexts' => ['url.path', 'url.query_args']],
        '#manager_roles' => $group_admins,
        '#facets' => array_keys($facets),
        '#sorts' => array_keys($sorts),
        '#prefilters' => $this->extractFilterFromUrl(),
        '#prefilter_my_interests' => $source instanceof ActivityStreamSourceType,
        '#search_string' => $search_value,
        '#source_class' => $source instanceof SourceTypeInterface ? get_class(
          $source
        ) : NULL,
        '#datasource' => $source instanceof SourceTypeInterface ? $source->getSourcesId() : NULL,
        '#bundle' => $source instanceof SourceTypeInterface ? $source->getEntityBundle() : NULL,
        '#layout' => $source instanceof SourceTypeInterface ? $source->getLayoutTheme() : NULL,
        '#page_options' => $this->configuration['page_options'],
        '#enable_search' => $this->configuration['enable_search'],
        '#enable_date_filter' => $this->configuration['enable_date_filter'] ?? FALSE,
        '#url' => Url::fromRoute('eic_search.solr_search')->toString(),
        '#isAnonymous' => \Drupal::currentUser()->isAnonymous(),
        '#currentGroup' => $current_group_route instanceof GroupInterface ? $current_group_route->id() : NULL,
        '#currentGroupUrl' => $current_group_route instanceof GroupInterface ? $current_group_route->toUrl()->toString(
        ) : NULL,
        '#enable_facet_interests' => $this->configuration['add_facet_interests'],
        '#enable_facet_my_groups' => $this->configuration['add_facet_my_groups'],
        '#isGroupOwner' => array_key_exists(
          EICGroupsHelper::GROUP_OWNER_ROLE,
          $user_group_roles
        ),
        '#enable_registration_filter' =>
          $source instanceof SourceTypeInterface &&
          !empty($source->getRegistrationDateIntervalField()),
        '#allow_pagination' => $source instanceof SourceTypeInterface ? (int) $source->allowPagination() : 1,
        '#load_more_number' => $source->getLoadMoreBatchItems(),
        '#is_route_group_search_results' =>
          (int) ('eic_overviews.groups.overview_page.search' === $route_name),
        '#enable_invite_user_action' =>
          (int) ('eic_overviews.groups.overview_page.members' === $route_name),
        '#invite_user_url' => $current_group_route instanceof GroupInterface ?
          Url::fromRoute(
            'ginvite.invitation.bulk',
            ['group' => $current_group_route->id()]
          )->toString() :
          '',
        '#translations' => [
          'public' => $this->t('Public', [], ['context' => 'eic_group']),
          'private' => $this->t('Private', [], ['context' => 'eic_group']),
          'filter' => $this->t('Filter', [], ['context' => 'eic_group']),
          'refine' => $this->t('Refine your search', [], ['context' => 'eic_group']),
          'topics' => $this->t('Topics', [], ['context' => 'eic_group']),
          'search_text' => $this->t('Search here', [], ['context' => 'eic_group']),
          'commented_on' => $this->t('commented on', [], ['context' => 'eic_group']),
          'custom_search_text' => [
            'user_gallery' => $this->t('Search for a member', [], ['context' => 'eic_group']),
            'group' => $this->t('Search for a group', [], ['context' => 'eic_group']),
          ],
          'no_results_title' => $this->t(
            'We haven’t found any search results',
            [],
            ['context' => 'eic_group']
          ),
          'no_results_body' => $this->t(
            'Please try again with another keyword',
            [],
            ['context' => 'eic_group']
          ),
          'members' => $this->t('Members', [], ['context' => 'eic_group']),
          'reactions' => $this->t('Reactions', [], ['context' => 'eic_group']),
          'documents' => $this->t('Documents', [], ['context' => 'eic_group']),
          'clear_all' => $this->t('Clear all', [], ['context' => 'eic_group']),
          'active_filter' => $this->t(
            'Active filter',
            [],
            ['context' => 'eic_group']
          ),
          'sort_by' => $this->t('Sort by', [], ['context' => 'eic_group']),
          'showing' => $this->t('Showing', [], ['context' => 'eic_group']),
          'sort_any' => $this->t('- Any -', [], ['context' => 'eic_group']),
          'label_video' => $this->t('Video', [], ['context' => 'eic_group']),
          'label_file' => $this->t('File', [], ['context' => 'eic_group']),
          'label_image' => $this->t('Image', [], ['context' => 'eic_group']),
          'like' => $this->t('Like', [], ['context' => 'eic_group']),
          'unlike' => $this->t('Unlike', [], ['context' => 'eic_group']),
          'add_video' => $this->t('Add video', [], ['context' => 'eic_group']),
          'add_document' => $this->t(
            'Add document',
            [],
            ['context' => 'eic_group']
          ),
          'add_gallery' => $this->t(
            'Add gallery',
            [],
            ['context' => 'eic_group']
          ),
          'post_content' => $this->t(
            'Post content',
            [],
            ['context' => 'eic_group']
          ),
          'uploaded_by' => $this->t(
            'Uploaded by',
            [],
            ['context' => 'eic_group']
          ),
          'draft' => $this->t('Draft', [], ['context' => 'eic_group']),
          'archived' => $this->t('Archived', [], ['context' => 'eic_group']),
          'pending' => $this->t('Pending', [], ['context' => 'eic_group']),
          'blocked' => $this->t('Blocked', [], ['context' => 'eic_group']),
          'load_more' => $this->t('Load more', [], ['context' => 'eic_group']),
          'invite_member' => $this->t(
            'Invite a member',
            [],
            ['context' => 'eic_group']
          ),
          'show_more' => $this->t('Show more', [], ['context' => 'eic_group']),
          'collapse' => $this->t('Show less', [], ['context' => 'eic_group']),
          'highlight' => $this->t('Highlight this content', [], ['context' => 'eic_group']),
          'unHighlight' => $this->t('Disable highlighting of this content', [], ['context' => 'eic_group']),
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
  public function updateSourceConfig(
    array &$form,
    FormStateInterface $form_state
  ) {
    if ($form_state->hasValue('field_overview_block')) {
      $current_source_value = $form_state->getValue(
        'field_overview_block'
      )[0]['settings']['search']['source_type'] ?: NULL;
    }
    else {
      $current_source_value = $form_state->getValue(
        'settings'
      )['search']['source_type'] ?: GroupSourceType::class;
    }

    $response = new AjaxResponse();

    /** @var SourceTypeInterface $current_source */
    $current_source = $this->getCurrentSource($current_source_value);

    $css = ['display' => 'none'];

    $sources_collected = $this->sourcesCollector->getSources();
    foreach ($sources_collected as $source) {
      $response->addCommand(
        new CssCommand('.source-' . $source->getEntityBundle(), $css)
      );
    }

    $response->addCommand(
      new CssCommand(
        '.source-' . $current_source->getEntityBundle(),
        ['display' => 'inline-block']
      )
    );

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
    $current_bundle = $current_source->getEntityBundle();

    $this->setConfiguration([
      'source_type' => $values['search']['source_type'],
      'facets' => $values['search']['configuration']['filter'][$current_bundle]['facets'],
      'sort_options' => $values['search']['configuration']['sorts'][$current_bundle]['sort_options'],
      'enable_search' => $values['search']['configuration']['enable_search'],
      'page_options' => $values['search']['configuration']['pagination']['page_options'],
      'prefilter_group' => $values['search']['configuration']['prefilter_group'],
      'enable_date_filter' => array_key_exists(
        'enable_date_filter',
        $values['search']['configuration']['date']
      ) ?
        $values['search']['configuration']['date']['enable_date_filter'] :
        FALSE,
      'add_facet_interests' => $values['search']['configuration']['add_facet_interests'],
      'add_facet_my_groups' => $values['search']['configuration']['add_facet_my_groups'],
    ]);
  }

  /**
   * Extracting filters values from the URL.
   *
   * Example of filters url parameter: ?filter[topics][0]=Financial
   * development&filter[content_type][0]=wiki_page
   *
   * @return array|NULL
   */
  private function extractFilterFromUrl(): ?array {
    $filters = $this->requestStack
      ->getCurrentRequest()
      ->query
      ->get('filter', []);

    if (!is_array($filters)) {
      return NULL;
    }

    return SearchHelper::decodeSolrQueryParams($filters);
  }

  /**
   * @param string $source_value
   *
   * @return SourceTypeInterface|null
   */
  private function getCurrentSource(string $source_value
  ): ?SourceTypeInterface {
    $sources_collected = $this->sourcesCollector->getSources();

    return array_key_exists($source_value, $sources_collected) ?
      $sources_collected[$source_value] :
      NULL;
  }

  /**
   * @param array $form
   * @param SourceTypeInterface $current_source
   */
  private function addConfigurationRenderer(
    array &$form,
    SourceTypeInterface $current_source
  ) {
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
      '#description' => $this->t(
        'Do you want to enable the search box feature into the overview ?', [],
        ['context' => 'eic_search']
      ),
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
      '#title' => $this->t(
        'Number per page options',
        [],
        ['context' => 'eic_search']
      ),
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
        '#title' => $this->t(
          'Facets for @label',
          ['@label' => $source->getLabel()],
          ['context' => 'eic_search']
        ),
        '#description' => $this->t(
          'Filters that you want to be available on the overview', [],
          ['context' => 'eic_search']
        ),
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
        '#title' => $this->t(
          'Sorting for @label',
          ['@label' => $source->getLabel()],
          ['context' => 'eic_search']
        ),
        '#description' => $this->t(
          'Choose available sorting options on the overview', [],
          ['context' => 'eic_search']
        ),
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
          '#title' => $this->t(
            'Prefilter the overview by group',
            [],
            ['context' => 'eic_search']
          ),
          '#description' => $this->t(
            'It will prefiltered the overview with the current group from page',
            [],
            ['context' => 'eic_search']
          ),
        ];
      }
    }

    $form['search']['configuration']['date']['enable_date_filter'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['enable_date_filter'],
      '#title' => $this->t('Enable date filter', [], ['context' => 'eic_search']
      ),
      '#description' => $this->t(
        'It will show a new date filter in the overview\'s sidebar.', [],
        ['context' => 'eic_search']
      ),
    ];

    $form['search']['configuration']['add_facet_my_groups'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['add_facet_my_groups'],
      '#title' => $this->t(
        'Add filter for my groups/contents',
        [],
        ['context' => 'eic_search']
      ),
      '#description' => $this->t(
        'Add new checkbox on the sidebar to filter only user groups/contents',
        [],
        ['context' => 'eic_search']
      ),
    ];

    $form['search']['configuration']['add_facet_interests'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['add_facet_interests'],
      '#title' => $this->t(
        'Add filter for my topics of interests',
        [],
        ['context' => 'eic_search']
      ),
      '#description' => $this->t(
        'Add new checkbox on the sidebar to filter only topics of interests from user',
        [],
        ['context' => 'eic_search']
      ),
    ];
  }

  /**
   * @param \Drupal\eic_search\Search\Sources\SourceTypeInterface $current_source
   *
   * @return array
   */
  private function generateFacetsOptions(SourceTypeInterface $current_source
  ): array {
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
  private function generateSortOptions(SourceTypeInterface $current_source
  ): array {
    $available_sorts = [];

    foreach ($current_source->getAvailableSortOptions() as $sort_option => $options) {
      $available_sorts[$sort_option] = $options['label'];
    }

    return $available_sorts;
  }

}
