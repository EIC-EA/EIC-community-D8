<?php

namespace Drupal\eic_admin\Form;

use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure action forms for this site.
 */
class ActionFormsForm extends ConfigFormBase {

  /**
   * The action forms manager service.
   *
   * @var \Drupal\eic_admin\Service\ActionFormsManager
   */
  protected $actionFormsManager;

  /**
   * Request stack.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->actionFormsManager = $container->get('eic_admin.action_forms_manager');
    $instance->routeProvider = $container->get('router.route_provider');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_admin_action_forms_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eic_admin.action_forms.'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $configs = $this->configFactory->loadMultiple($this->configFactory->listAll('eic_admin.action_forms.'));

    $form['routes'] = [
      '#type' => 'vertical_tabs',
    ];

    foreach ($configs as $config) {
      $route = $this->routeProvider->getRouteByName($config->get('route'));
      $route_name = $this->getConfigMachineName($config);
      $form[$route_name] = [
        '#type' => 'details',
        '#title' => empty($config->get('label')) ? $this->t('undefined') : $config->get('label'),
        '#group' => 'routes',
        '#tree' => TRUE,
      ];
      $form[$route_name]['route'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Route'),
        '#default_value' => $config->get('route'),
        '#description' => $this->t('Route name of the page.'),
        '#disabled' => TRUE,
        '#tree' => TRUE,
      ];
      $form[$route_name]['paths'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Paths'),
        '#default_value' => $config->get('paths'),
        '#description' => $this->t('Restrict to specific paths, one per line. Leave blank to match all paths for this route.'),
        '#required' => FALSE,
        '#tree' => TRUE,
      ];
      $form[$route_name]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $config->get('label'),
        '#description' => $this->t('Administrative label.'),
        '#required' => TRUE,
        '#tree' => TRUE,
      ];
      $form[$route_name]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $config->get('title'),
        '#description' => $this->t('Override page title. Leave blank to keep original title.'),
        '#tree' => TRUE,
      ];
      $form[$route_name]['description'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Description'),
        '#default_value' => $config->get('description.value'),
        '#description' => $this->t('Provide an additional description block.'),
        '#format' => $config->get('description.format'),
      ];
      $form[$route_name]['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $route->getDefault('_entity_types'),
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
        '#show_restricted' => FALSE,
        '#show_nested' => FALSE,
        '#recursion_limit' => 3,
        '#text' => NULL,
        '#options' => [],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->actionFormsManager->getAllRouteConfigs() as $config) {
      $config_values = $form_state->getValue($this->getConfigMachineName($config));
      if (empty($config_values)) {
        continue;
      }
      $config->set('paths', $config_values['paths']);
      $config->set('label', $config_values['label']);
      $config->set('title', $config_values['title']);
      $config->set('description.value', $config_values['description']['value']);
      $config->set('description.format', $config_values['description']['format']);
      $config->save();

      // @todo Invalidate cache for this specific route?
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns a 'machine name' for the given config.
   *
   * We assume that there is only one config with the same route name.
   * The returned machine name is hence a sanitized version of a route name.
   *
   * @param \Drupal\Core\Config\ConfigBase $config
   *   The config object.
   *
   * @return string|false
   *   The machine name of FALSE if route is not found.
   */
  protected function getConfigMachineName(ConfigBase $config) {
    // Since form elements don't seem to work well with keys including dots, we
    // sanitize the route name and use it as the machine name.
    if ($config->get('route')) {
      return str_replace('.', '__', $config->get('route'));
    }
    return FALSE;
  }

}
