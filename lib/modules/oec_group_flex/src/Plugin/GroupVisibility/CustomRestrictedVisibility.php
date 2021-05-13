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
use Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface;
use Drupal\oec_group_flex\GroupVisibilityRecordInterface;
use Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager;
use Drupal\oec_group_flex\Plugin\GroupVisibilityOptionsInterface;
use Drupal\oec_group_flex\Plugin\RestrictedGroupVisibilityBase;
use Drupal\user\Entity\User;
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
   * The option name to restrict group visibility for specific users.
   */
  const VISIBILITY_OPTION_RESTRICTED_USERS = 'restricted_users';

  /**
   * The option name to restrict group visibility for specific email domains.
   */
  const VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS = 'restricted_email_domains';

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
   * @var \Drupal\oec_group_flex\Plugin\CustomRestrictedVisibilityManager
   */
  private $customRestrictedVisibilityManager;

  /**
   * @var array
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
   * @param \Drupal\oec_group_flex\GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage
   *   The group visibility storage service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage, EmailValidatorInterface $email_validator, CustomRestrictedVisibilityManager $customRestrictedVisibilityManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $configFactory);
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
    ];

    $group_visibility_record = NULL;
    if (!$group->isNew()) {
      $group_visibility_record = $this->groupVisibilityStorage->load($group->id());
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
      $status_field = $id . '_status';
      if ($status_value = $form_state->getValue($status_field)) {
        $group_visibility_options[$status_field] = $status_value;
        $conf_field = $id . '_conf';
        $group_visibility_options[$conf_field] = $form_state->getValue($conf_field);
      }
    }
    return $group_visibility_options;
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
    if ($operation == 'view') {
      if (!$entity->getMember($account)) {
        if ($entity->hasPermission('view group', $account)) {

          $group_visibility_record = $this->groupVisibilityStorage->load($entity->id());
          $has_options = FALSE;

          foreach ($group_visibility_record->getOptions() as $key => $option) {
            if (!empty($option)) {
              $has_options = TRUE;
              switch ($key) {
                case self::VISIBILITY_OPTION_RESTRICTED_USERS:
                  // Allow access if user is referenced in restricted_users
                  // option.
                  foreach ($option as $restricted_user_id) {
                    if ($account->id() == $restricted_user_id['target_id']) {
                      return GroupAccessResult::allowed()
                        ->addCacheableDependency($account)
                        ->addCacheableDependency($entity);
                    }
                  }
                  break;

                case self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS:
                  $email_domains = explode(',', $option);
                  $account_email_domain = explode('@', $account->getEmail())[1];

                  // Allow access if user's email domain is one of the
                  // restricted ones.
                  foreach ($email_domains as $email_domain) {
                    if ($account_email_domain === $email_domain) {
                      return GroupAccessResult::allowed()
                        ->addCacheableDependency($account)
                        ->addCacheableDependency($entity);
                    }
                  }
                  break;

              }
            }
          }

          // If the group visibility has options and the current user's account
          // doesn't meet any of those options, we return access forbidden.
          if ($has_options) {
            return GroupAccessResult::forbidden()
              ->addCacheableDependency($account)
              ->addCacheableDependency($entity);
          }
        }
      }
    }

    return GroupAccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public static function getPluginFormElementsFieldNames() {
    // @todo make dynamic
    return [
      'email_domains_status' => 'email_domains_status',
      'email_domains_conf' => 'email_domains_conf',
      'restricted_users_status' => 'restricted_users_status',
      'restricted_users_conf' => 'restricted_users_conf',
    ];
  }

}
