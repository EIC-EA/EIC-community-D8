<?php

namespace Drupal\eic_groups\Plugin\views\access;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\views\access\GroupPermission;
use Drupal\oec_group_features\GroupFeatureHelper;
use Drupal\views\Annotation\ViewsAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\workflows\WorkflowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides group moderation state + permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "eic_group_moderation_state_permission",
 *   title = @Translation("Group moderation state and permission"),
 *   help = @Translation("Access will be granted if group permissions and group moderation state matched.")
 * )
 */
class EICGroupPermissionWithContentModeration extends GroupPermission {

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GroupPermissionHandlerInterface $permission_handler,
    ModuleHandlerInterface $module_handler,
    ContextProviderInterface $context_provider
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $permission_handler, $module_handler, $context_provider);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('group.group_route_context')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Check if user has the correct permission in group and matches the allowed
   * moderation states.
   */
  public function access(AccountInterface $account) {
    if (!parent::access($account)) {
      return FALSE;
    }

    $moderation_state = $this->group->get('moderation_state')->value;
    return (
      isset($this->options['moderation_states'][$moderation_state]) &&
      $this->options['moderation_states'][$moderation_state]
    ) || empty($this->options['moderation_states']) || UserHelper::isPowerUser($account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_group_views_moderation_state_permission_access_check', $this->options['group_permission']);

    // Upcast any %group path key the user may have configured so the
    // '_group_permission' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    /** @var WorkflowInterface $workflow */
    $workflow = \Drupal::entityTypeManager()->getStorage('workflow')->load(GroupsModerationHelper::WORKFLOW_MACHINE_NAME);
    $options = [];
    foreach ($workflow->get('type_settings')['states'] as $key => $state) {
      $options[$key] = $state['label'];
    }
    $form['moderation_states'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Allowed moderation states'),
      '#default_value' => $this->options['moderation_states'],
      '#description' => $this->t('The moderation states allowed to access this view.'),
    ];
  }
}
