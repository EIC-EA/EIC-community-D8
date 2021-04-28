<?php

namespace Drupal\oec_group_flex\Plugin\GroupVisibility;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface;
use Drupal\oec_group_flex\GroupVisibilityRecord;
use Drupal\oec_group_flex\Plugin\GroupVisibilityOptionsInterface;
use Drupal\oec_group_flex\Plugin\RestrictedGroupVisibilityBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'flexible_restricted_role' group visibility.
 *
 * @GroupVisibility(
 *  id = "flexible_restricted_role",
 *  label = @Translation("Flexible Restricted (visible by members and trusted users)"),
 *  weight = -88
 * )
 */
class FlexibleRestrictedRoleVisibility extends RestrictedGroupVisibilityBase implements GroupVisibilityOptionsInterface {

  /**
   * The group visibility storage service.
   *
   * @var \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface
   */
  protected $groupVisibilityStorage;

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
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage
   *   The group visibility storage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $configFactory);
    $this->groupVisibilityStorage = $groupVisibilityStorage;
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
      $container->get('oec_group_flex.group_visibility.storage')
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

    $form['oec_group_visibility_option_restricted_users'] = [
      '#title' => ('Select trusted users'),
      '#type' => 'entity_autocomplete',
      '#tags' => TRUE,
      '#target_type' => 'user',
      '#required' => FALSE,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'filter' => [
          'role' => ['trusted_user'],
        ],
      ],
    ];

    if (!$group->isNew()) {
      $group_visibility_record = $this->groupVisibilityStorage->load($group->id());

      $this->setFormDefaultRestrictedUsers($form, $form_state, $group_visibility_record);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormStateValues(FormStateInterface $form_state) {
    $groupVisibilityOptions = [
      'restricted_users' => $form_state->getValue('oec_group_visibility_option_restricted_users'),
    ];
    return $groupVisibilityOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return $this->t('Flexible Restricted (The @group_type_name will be viewed by group members and trusted users)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return $this->t('The @group_type_name will be viewed by group members and trusted users', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function groupAccess(GroupInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      if (!$entity->getMember($account)) {
        if ($entity->hasPermission('view group', $account)) {
          // @todo Check if user has been referenced.
          return GroupAccessResult::allowed()
            ->addCacheableDependency($account)
            ->addCacheableDependency($entity);
        }
      }
    }

    return GroupAccessResult::neutral();
  }

  /**
   * Set default values for restricted_users field.
   *
   * @param array $form
   *   The form renderable array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\oec_group_flex\GroupVisibilityRecord $group_visibility_record
   *   The Group visibility record object.
   */
  private function setFormDefaultRestrictedUsers(array &$form, FormStateInterface $form_state, GroupVisibilityRecord $group_visibility_record) {
    if ($restricted_users = $form_state->getValue('oec_group_visibility_option_restricted_users')) {
      $load_users = [];
      foreach ($restricted_users as $user) {
        $load_users[] = $user['target_id'];
      }
      $form['oec_group_visibility_option_restricted_users']['#default_value'] = User::loadMultiple($load_users);
    }
    elseif (array_key_exists('restricted_users', $group_visibility_record->getOptions())) {
      $restricted_users = $group_visibility_record->getOptions()['restricted_users'];
    }
    if ($restricted_users) {
      foreach ($restricted_users as $user) {
        $form['oec_group_visibility_option_restricted_users']['#default_value'][] = User::load($user['target_id']);
      }
    }
  }

}
