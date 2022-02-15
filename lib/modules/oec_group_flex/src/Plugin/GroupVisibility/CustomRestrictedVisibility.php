<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager;
use Drupal\oec_group_flex\Plugin\GroupVisibilityOptionsInterface;
use Drupal\oec_group_flex\Plugin\RestrictedGroupVisibilityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'custom_restricted' group visibility.
 *
 * @GroupVisibility(
 *  id = "custom_restricted",
 *  label = @Translation("Custom restriction"),
 *  weight = -88
 * )
 */
class CustomRestrictedVisibility extends RestrictedGroupVisibilityBase implements GroupVisibilityOptionsInterface {

  /**
   * The group visibility storage service.
   *
   * @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface
   */
  protected $groupVisibilityStorage;

  /**
   * The email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The custom restricted visibility manager.
   *
   * @var \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager
   */
  private $customRestrictedVisibilityManager;

  /**
   * The custom restricted visibility plugins.
   *
   * @var \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityInterface[]
   */
  private $plugins;

  /**
   * Constructs a new RestrictedVisibility plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\group\GroupRoleSynchronizer $groupRoleSynchronizer
   *   The group role synchronizer.
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage
   *   The group visibility storage service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   * @param \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager $customRestrictedVisibilityManager
   *   The custom restricted visibility manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, GroupRoleSynchronizer $groupRoleSynchronizer, GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage, EmailValidatorInterface $email_validator, CustomRestrictedVisibilityManager $customRestrictedVisibilityManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $configFactory, $groupRoleSynchronizer);
    $this->groupVisibilityStorage = $groupVisibilityStorage;
    $this->emailValidator = $email_validator;
    $this->customRestrictedVisibilityManager = $customRestrictedVisibilityManager;
    $this->plugins = $this->customRestrictedVisibilityManager->getAllAsArray();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('group_role.synchronizer'),
      $container->get('oec_group_flex.group_visibility.storage'),
      $container->get('email.validator'),
      $container->get('plugin.manager.custom_restricted_visibility')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginOptionsForm(FormStateInterface $form_state) {
    $form = [];
    $form_object = $form_state->getFormObject();

    if (!$form_object instanceof EntityFormInterface) {
      return $form;
    }

    $group = $form_object->getEntity();

    if (!$group instanceof GroupInterface) {
      return $form;
    }

    $form_fields_base_name = 'oec_group_visibility_option';
    $form_fields_container = $form_fields_base_name . '_container';

    $form[$form_fields_container] = [
      '#type' => 'container',
      '#element_validate' => [
        [$this, 'validateGroupVisibilityOptions'],
      ],
    ];

    $group_visibility_record = FALSE;
    if (!$group->isNew()) {
      $group_visibility_record = $this->groupVisibilityStorage->load($group->id());
    }

    // We force the group visibility to NULL if not found.
    if (!$group_visibility_record) {
      $group_visibility_record = NULL;
    }

    /** @var \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityBase $pluginInstance */
    foreach ($this->plugins as $id => $pluginInstance) {
      foreach ($pluginInstance->getPluginForm() as $pluginForm) {
        $form[$form_fields_container][$id] = $pluginInstance->setDefaultFormValues($pluginForm, $group_visibility_record);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormStateValues(FormStateInterface $form_state) {
    $group_visibility_options = [];
    foreach ($this->plugins as $id => $plugin) {
      $status_field = $plugin->getStatusKey();
      if ($status_value = $form_state->getValue($status_field)) {
        $group_visibility_options[$id] = [];
        $group_visibility_options[$id][$status_field] = $status_value;
        $conf_field = $id . '_conf';
        $group_visibility_options[$id][$conf_field] = $form_state->getValue($conf_field);
      }
    }
    return $group_visibility_options;
  }

  /**
   * Validate the group visibility options.
   */
  public function validateGroupVisibilityOptions(array &$element, FormStateInterface $form_state) {
    $selectedGroupVisibility = $form_state->getValue('group_visibility');
    if ($selectedGroupVisibility === 'custom_restricted') {
      foreach ($this->plugins as $plugin) {
        if ($form_state->getValue($plugin->getStatusKey()) === 1) {
          return;
        }
      }

      $form_state->setError($element, $this->t('Please select a visibility option.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Custom restriction');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('This means the restricted group will be visible to each of the trusted users that comply with any of the following restrictions.');
  }

  /**
   * {@inheritdoc}
   */
  public function groupAccess(GroupInterface $entity, $operation, AccountInterface $account) {
    $neutral = GroupAccessResult::neutral();
    if ($this->skipAccessCheck($entity, $operation, $account)) {
      return $neutral;
    }

    $group_visibility_record = $this->groupVisibilityStorage->load($entity->id());

    // Loop through all of the options, they are keyed by pluginId.
    // If we have a match and the plugin returns not neutral we
    // return the access result as well.
    foreach ($group_visibility_record->getOptions() as $pluginId => $groupPluginOptions) {
      /** @var \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityBase $plugin */
      $plugin = isset($this->plugins[$pluginId]) ? $this->plugins[$pluginId] : NULL;
      if ($plugin instanceof CustomRestrictedVisibilityInterface) {
        $pluginAccess = $plugin->hasViewAccess($entity, $account, $group_visibility_record);
        if (!$pluginAccess->isNeutral()) {
          return $pluginAccess;
        }
      }
    }

    // If the group visibility has options and the current users account
    // doesn't meet any of those options, we return access forbidden.
    if (!empty($group_visibility_record->getOptions())) {
      return GroupAccessResult::forbidden()
        ->addCacheableDependency($account)
        ->addCacheableDependency($entity);
    }

    return $neutral;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginFormElementsFieldNames() {
    $fieldNames = [];
    foreach ($this->plugins as $plugin) {
      $pluginFieldNames = $plugin->getFormFieldNames();
      foreach ($pluginFieldNames as $fieldName) {
        $fieldNames[$fieldName] = $fieldNames;
      }
    }
    return $fieldNames;
  }

  /**
   * Checks if the access check can be skipped.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   *   The group entity to check for skipping access.
   * @param string $operation
   *   The operation on the entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The performing account.
   *
   * @return bool
   *   True when the skip access check can be skipped.
   */
  private function skipAccessCheck(GroupInterface $entity, $operation, AccountInterface $account) {
    return ($operation !== 'view' || !$entity->isPublished() || $entity->getMember($account) || !$entity->hasPermission('view group', $account));
  }

  /**
   * Gets the custom restricted visibility plugins.
   *
   * @return \Drupal\oec_group_flex\Plugin\GroupVisibility\CustomRestrictedVisibilityInterface[]
   *   Array of custom restricted visibility plugins.
   */
  public function getCustomRestrictedPlugins() {
    return $this->plugins;
  }

}
