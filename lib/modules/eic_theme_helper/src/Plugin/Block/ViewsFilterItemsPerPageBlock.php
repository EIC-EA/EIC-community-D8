<?php

namespace Drupal\eic_theme_helper\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a ViewsFilterItemsPerPageBlock block.
 *
 * @Block(
 *   id = "eic_theme_helper_views_filter_items_per_page",
 *   admin_label = @Translation("Views filter items per page block"),
 *   category = @Translation("OpenEuropa")
 * )
 */
class ViewsFilterItemsPerPageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new ViewsFilterItemsPerPageBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Get current route object.
    $route = $this->routeMatch->getRouteObject();

    if (is_null($route)) {
      return $build;
    }

    // Get view id and display id from route.
    $view_id = $route->getDefault('view_id');
    $display_id = $route->getDefault('display_id');

    if (empty($view_id) || empty($display_id)) {
      return $build;
    }

    // Get current route name.
    $current_route_name = $this->routeMatch->getRouteName();

    // We check if the current route is actually a view page.
    if ($current_route_name !== "view.$view_id.$display_id") {
      return $build;
    }

    // Get the view by id and initializes the right display.
    $view = Views::getView($view_id);
    $view->setDisplay($display_id);

    // Check if items per page are exposed and grab the options.
    if (!($items_per_age_options = $this->getItemsPerPageOptions($view))) {
      return $build;
    }

    $query_params = $this->requestStack->getCurrentRequest()->query->all();

    // Create items per page links.
    $links = [];
    foreach ($items_per_age_options as $option) {
      $query_params['items_per_page'] = $option;
      $url = Url::fromRoute($current_route_name, [], ['query' => $query_params]);
      $links[] = Link::fromTextAndUrl($option, $url)->toString()->setCacheContexts([
        'url.path',
        'url.query_args',
      ]);
    }

    if (empty($links)) {
      return $build;
    }

    $build['content'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => $this->t('Showing'),
      '#items' => $links,
    ];

    return $build;
  }

  /**
   * Gets the items per page options for a given View.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view executable instance object.
   *
   * @return array|bool
   *   An array containing items per page values or FALSE if none.
   */
  private function getItemsPerPageOptions(ViewExecutable $view) {
    $pager = $view->display_handler->getOption('pager');

    if (empty($pager['options'])) {
      return FALSE;
    }

    $pager_options = $pager['options'];

    if (empty($pager_options['expose'])) {
      return FALSE;
    }

    if (empty($pager_options['items_per_page'])) {
      return FALSE;
    }

    return explode(',', $pager_options['expose']['items_per_page_options']);
  }

}
