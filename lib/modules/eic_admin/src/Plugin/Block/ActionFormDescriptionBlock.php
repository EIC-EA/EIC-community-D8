<?php

namespace Drupal\eic_admin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\eic_admin\Service\ActionFormsManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a custom group content menu block.
 *
 * @Block(
 *   id = "eic_admin_action_forms_description",
 *   admin_label = @Translation("EIC Admin - Action forms description"),
 *   category = @Translation("European Innovation Council"),
 * )
 */
class ActionFormDescriptionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The action forms manager service.
   *
   * @var \Drupal\eic_admin\Service\ActionFormsManager
   */
  protected $actionFormsManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The title resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Constructs a new ActionFormDescriptionBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\eic_admin\Service\ActionFormsManager $action_forms_manager
   *   The action forms manager service.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    ActionFormsManager $action_forms_manager,
    Token $token_service,
    RequestStack $request_stack,
    TitleResolverInterface $title_resolver
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->actionFormsManager = $action_forms_manager;
    $this->tokenService = $token_service;
    $this->requestStack = $request_stack;
    $this->titleResolver = $title_resolver;
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
      $container->get('current_route_match'),
      $container->get('eic_admin.action_forms_manager'),
      $container->get('token'),
      $container->get('request_stack'),
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // If current route exist in config, return the content of its description.
    if ($config = $this->actionFormsManager->getRouteConfig()) {
      $route_parameters = [];
      foreach ($this->routeMatch->getParameters() as $parameter_type => $entity) {
        $route_parameters[$parameter_type] = $entity;
      }

      $request = $this->requestStack->getCurrentRequest();

      // Prepare title and description variables.
      $title = '';
      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = $this->titleResolver->getTitle($request, $route);
      }
      $text = $this->tokenService->replace($config->get('description.value'), $route_parameters);

      if (!empty($title)) {
        $build['subject'] = [
          '#markup' => "<h2>$title</h2>",
        ];
      }
      $build['content'] = [
        '#type' => 'processed_text',
        '#text' => $text,
        '#format' => $config->get('description.format'),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
