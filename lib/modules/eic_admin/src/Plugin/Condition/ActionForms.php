<?php

namespace Drupal\eic_admin\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActionFormsManager $action_forms_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionFormsManager = $action_forms_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eic_admin.action_forms_manager')
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
      '#items' => $this->getActionFormRoutes(),
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
    if ($this->isNegated()) {
      return in_array($this->actionFormsManager->routeMatch->getRouteName(), $this->getActionFormRoutes());
    }
    return !in_array($this->actionFormsManager->routeMatch->getRouteName(), $this->getActionFormRoutes());
  }

  /**
   * Returns all the existing action form routes.
   *
   * @return string[]
   *   A list of route names.
   */
  protected function getActionFormRoutes() {
    $routes = [];
    foreach ($this->actionFormsManager->getAllRouteConfigs() as $config) {
      $routes[] = $config->get('route');
    }
    return $routes;
  }

}
