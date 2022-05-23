<?php

namespace Drupal\eic_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\eic_admin\Service\ActionFormsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom implementation to alter action forms.
 */
class ActionFormsController extends ControllerBase {

  /**
   * The action forms manager service.
   *
   * @var \Drupal\eic_admin\Service\ActionFormsManager
   */
  protected $actionFormsManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * ActionFormsController constructor.
   *
   * @param \Drupal\eic_admin\Service\ActionFormsManager $action_forms_manager
   *   The action forms manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   */
  public function __construct(
    ActionFormsManager $action_forms_manager,
    RouteMatchInterface $route_match,
    Token $token_service) {
    $this->actionFormsManager = $action_forms_manager;
    $this->routeMatch = $route_match;
    $this->tokenService = $token_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_admin.action_forms_manager'),
      $container->get('current_route_match'),
      $container->get('token'),
    );
  }

  /**
   * Renders title for the current page.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The untranslated page title.
   */
  public function pageTitle() {
    $title = '';
    $route_name = $this->routeMatch->getRouteName();
    if ($config = $this->actionFormsManager->getRouteConfig($route_name)) {
      $route_parameters = [];
      foreach ($this->routeMatch->getParameters() as $parameter_type => $entity) {
        $route_parameters[$parameter_type] = $entity;
      }
      $title = $config->get('title');
      $title = $this->tokenService->replace($title, $route_parameters);
    }

    return Markup::create($title);
  }

}
