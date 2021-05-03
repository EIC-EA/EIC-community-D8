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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, GroupVisibilityDatabaseStorageInterface $groupVisibilityStorage, EmailValidatorInterface $email_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $configFactory);
    $this->groupVisibilityStorage = $groupVisibilityStorage;
    $this->emailValidator = $email_validator;
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
      $container->get('email.validator')
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
        [$this, 'validatePluginForm'],
      ],
    ];

    // Specific email domains checkbox.
    $form[$form_fields_container][$form_fields_base_name . '_status_' . self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS] = [
      '#title' => ('Specific email domains'),
      '#type' => 'checkbox',
      '#weight' => 0,
    ];
    // Specific email domains textbox.
    $form[$form_fields_container][$form_fields_base_name . '_' . self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS] = [
      '#title' => ('Email domain'),
      '#description' => $this->t('Add multiple email domains but separating them with a comma'),
      '#type' => 'textfield',
      '#element_validate' => [
        [$this, 'validateEmailDomains'],
      ],
      '#states' => [
        'visible' => [
          ':input[name="' . $form_fields_base_name . '_status_' . self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS . '"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#weight' => 1,
    ];

    // Specific trusted users checkbox.
    $form[$form_fields_container][$form_fields_base_name . '_status_' . self::VISIBILITY_OPTION_RESTRICTED_USERS] = [
      '#title' => ('Specific trusted users'),
      '#type' => 'checkbox',
      '#weight' => 2,
    ];
    // Specific trusted users textbox.
    $form[$form_fields_container][$form_fields_base_name . '_' . self::VISIBILITY_OPTION_RESTRICTED_USERS] = [
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
      '#states' => [
        'visible' => [
          ':input[name="' . $form_fields_base_name . '_status_' . self::VISIBILITY_OPTION_RESTRICTED_USERS . '"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#weight' => 3,
    ];

    // Prepopulate fields with default values from database.
    if (!$group->isNew()) {
      if ($group_visibility_record = $this->groupVisibilityStorage->load($group->id())) {
        $this->setFormDefaultRestrictedUsers($form, $form_state, $group_visibility_record);
        $this->setFormDefaultRestrictedEmailDomains($form, $form_state, $group_visibility_record);
      }
    }
    else {
      // $this->setFormStateFromTempStore($form, $form_state);
      $this->setFormDefaultRestrictedUsers($form, $form_state);
      $this->setFormDefaultRestrictedEmailDomains($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormStateValues(FormStateInterface $form_state) {
    $form_fields_base_name = 'oec_group_visibility_option_';
    $group_visibility_options = [];
    $group_visibility_option_fields = [
      self::VISIBILITY_OPTION_RESTRICTED_USERS,
      self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS,
    ];

    foreach ($group_visibility_option_fields as $field) {
      if ($value = $form_state->getValue($form_fields_base_name . $field)) {
        $group_visibility_options[$field] = $value;
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
   * Form validation for the whole container element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validatePluginForm(array &$element, FormStateInterface $form_state) {
    $form_values = $this->getFormStateValues($form_state);

    if (empty($form_values)) {
      return;
    }

    foreach (array_keys($form_values) as $key) {
      // Clear form state value if visibility option checkbox is unchecked.
      if ($form_state->isValueEmpty('oec_group_visibility_option_status_' . $key)) {
        $form_state->setValue('oec_group_visibility_option_' . $key, NULL);
      }
    }

  }

  /**
   * Form element validation for restricted_email_domains field.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateEmailDomains(array &$element, FormStateInterface $form_state) {
    $form_values = $this->getFormStateValues($form_state);

    if (empty($form_values[self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS])) {
      return;
    }

    if ($form_state->isValueEmpty('oec_group_visibility_option_status_' . self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS)) {
      return;
    }

    $domain_names = explode(',', $form_values[self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS]);

    foreach ($domain_names as $domain_name) {
      if (!$this->emailValidator->isvalid("placeholder@$domain_name")) {
        return $form_state->setError($element, $this->t('One of the email domains is not valid.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getPluginFormElementsFieldNames() {
    $form_field_base_name = 'oec_group_visibility_option_';
    return [
      self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS => $form_field_base_name . self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS,
      self::VISIBILITY_OPTION_RESTRICTED_USERS => $form_field_base_name . self::VISIBILITY_OPTION_RESTRICTED_USERS,
    ];
  }

  /**
   * Set default values for restricted_users field.
   *
   * @param array $form
   *   The form renderable array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\oec_group_flex\GroupVisibilityRecordInterface $group_visibility_record
   *   The Group visibility record object.
   */
  private function setFormDefaultRestrictedUsers(array &$form, FormStateInterface $form_state, GroupVisibilityRecordInterface $group_visibility_record = NULL) {
    $form_field_checkbox = 'oec_group_visibility_option_status_' . self::VISIBILITY_OPTION_RESTRICTED_USERS;
    $form_field_name = 'oec_group_visibility_option_' . self::VISIBILITY_OPTION_RESTRICTED_USERS;
    $form_field_container = 'oec_group_visibility_option_container';

    if ($restricted_users = $form_state->getValue($form_field_name)) {
      $load_users = [];
      foreach ($restricted_users as $user) {
        $load_users[] = $user['target_id'];
      }
    }
    else {
      if (is_null($group_visibility_record)) {
        return;
      }

      if (array_key_exists(self::VISIBILITY_OPTION_RESTRICTED_USERS, $group_visibility_record->getOptions())) {
        $restricted_users = $group_visibility_record->getOptions()[self::VISIBILITY_OPTION_RESTRICTED_USERS];
      }
    }

    if ($restricted_users) {
      $form[$form_field_container][$form_field_checkbox]['#default_value'] = TRUE;
      foreach ($restricted_users as $user) {
        $form[$form_field_container][$form_field_name]['#default_value'][] = User::load($user['target_id']);
      }
    }
  }

  /**
   * Set default values for restricted_email_domains field.
   *
   * @param array $form
   *   The form renderable array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\oec_group_flex\GroupVisibilityRecordInterface $group_visibility_record
   *   The Group visibility record object.
   */
  private function setFormDefaultRestrictedEmailDomains(array &$form, FormStateInterface $form_state, GroupVisibilityRecordInterface $group_visibility_record = NULL) {
    $form_field_checkbox = 'oec_group_visibility_option_status_' . self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS;
    $form_field_name = 'oec_group_visibility_option_' . self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS;
    $form_field_container = 'oec_group_visibility_option_container';

    if ($restricted_email_domains = $form_state->getValue($form_field_name)) {
      $form[$form_field_container][$form_field_checkbox]['#default_value'] = TRUE;
      $form[$form_field_container][$form_field_name]['#default_value'] = $restricted_email_domains;
    }
    else {
      if (is_null($group_visibility_record)) {
        return;
      }

      if (array_key_exists(self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS, $group_visibility_record->getOptions())) {
        $form[$form_field_container][$form_field_checkbox]['#default_value'] = TRUE;
        $form[$form_field_container][$form_field_name]['#default_value'] = $group_visibility_record->getOptions()[self::VISIBILITY_OPTION_RESTRICTED_EMAIL_DOMAINS];
      }
    }
  }

}
