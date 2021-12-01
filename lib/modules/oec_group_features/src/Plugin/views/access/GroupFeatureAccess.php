<?php

namespace Drupal\oec_group_features\Plugin\views\access;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\oec_group_features\GroupFeatureHelper;
use Drupal\views\Annotation\ViewsAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group feature permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "group_features_permission",
 *   title = @Translation("Group and feature permission"),
 *   help = @Translation("Access will be granted if group permissions matched AND linked feature is enabled.")
 * )
 */
class GroupFeatureAccess extends AccessPluginBase {

  /**
   * The GroupFeatureHelper service.
   *
   * @var \Drupal\oec_group_features\GroupFeatureHelper
   */
  private $groupsFeatureHelper;

  /**
   * The group entity.
   *
   * @var GroupInterface
   */
  private $group;

  /**
   * The GroupPermissionHandlerInterface service.
   *
   * @var GroupPermissionHandlerInterface
   */
  private $permissionHandler;

  /**
   * The ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * GroupFeatureAccess constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\oec_group_features\GroupFeatureHelper $groups_feature_helper
   *   The GroupFeatureHelper service.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The ContextProviderInterface service.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $group_permission_handler
   *   The GroupPermissionHandlerInterface service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The ModuleHandler service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GroupFeatureHelper $groups_feature_helper,
    ContextProviderInterface $context_provider,
    GroupPermissionHandlerInterface $group_permission_handler,
    ModuleHandler $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->groupsFeatureHelper = $groups_feature_helper;
    $contexts = $context_provider->getRuntimeContexts(['group']);
    $context = $contexts['group'];
    $this->group = $context->getContextValue();
    $this->permissionHandler = $group_permission_handler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return \Drupal\oec_group_features\Plugin\views\access\GroupFeatureAccess|\Drupal\views\Plugin\views\PluginBase|static
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
      $container->get('oec_group_features.helper'),
      $container->get('group.group_route_context'),
      $container->get('group.permissions'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Check if user has the correct permission in group AND if linked feature is enabled.
   */
  public function access(AccountInterface $account) {
    if (empty($this->group)) {
      return FALSE;
    }

    $has_group_permission = $this->group->hasPermission($this->options['group_permission'], $account);
    $group_features = $this->group->get(GroupFeatureHelper::FEATURES_FIELD_NAME)->getValue();

    $existing_linked_group_feature = array_filter($group_features, function($feature) {
      return $this->options['linked_feature'] === $feature['value'];
    });

    return $has_group_permission && !empty($existing_linked_group_feature);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->options['group_permission'] . ' - ' . $this->options['linked_feature'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $features = $this->groupsFeatureHelper->getAllAvailableFeatures();

    // Get list of permissions.
    $permissions = [];

    foreach ($this->permissionHandler->getPermissions(TRUE) as $permission_name => $permission) {
      $display_name = $this->moduleHandler->getName($permission['provider']);
      $permissions[$display_name . ' : ' . $permission['section']][$permission_name] = strip_tags($permission['title']);
    }

    $form['group_permission'] = [
      '#type' => 'select',
      '#options' => $permissions,
      '#title' => $this->t('Group permission'),
      '#default_value' => $this->options['group_permission'],
      '#description' => $this->t('Only users with the selected group permission will be able to access this display.<br /><strong>Warning:</strong> This will only work if there is a {group} parameter in the route. If not, it will always deny access.'),
    ];

    $form['linked_feature'] = [
      '#type' => 'radios',
      '#options' => $features,
      '#title' => $this->t('Linked feature'),
      '#default_value' => $this->options['linked_feature'],
      '#description' => $this->t('The feature linked to the current view.'),
    ];
  }
}
