<?php

namespace Drupal\eic_admin\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\eic_admin\Service\ActionFormsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Action Forms' condition.
 *
 * @Condition(
 *   id = "eic_admin_action_forms",
 *   label = @Translation("EIC Action Forms"),
 * )
 */
class ActionForms extends ConditionPluginBase implements ContainerFactoryPluginInterface {

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
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Creates a new ActionForms instance.
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
   * @param \Drupal\eic_admin\Service\ActionFormsManager $action_forms_manager
   *   The action forms manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   The current path stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ActionFormsManager $action_forms_manager,
    RouteMatchInterface $route_match,
    CurrentPathStack $current_path_stack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionFormsManager = $action_forms_manager;
    $this->routeMatch = $route_match;
    $this->currentPath = $current_path_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_admin.action_forms_manager'),
      $container->get('current_route_match'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['info'] = [
      '#markup' => $this->t('Acts on all defined action forms, see <a href=":action_forms_config_page">here</a>.', [
        ':action_forms_config_page' => Url::fromRoute('eic_admin.actions_config')->toString(),
      ]),
    ];

    $form['routes'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => 'Routes',
      '#items' => $this->actionFormsManager->getActionFormRoutes(),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if ($config = $this->actionFormsManager->getRouteConfig()) {
      if ($this->actionFormsManager->matchPath($config)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
