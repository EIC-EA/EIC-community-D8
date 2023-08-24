<?php

namespace Drupal\oec_group_flex\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the visibility settings form.
 */
class GroupVisibilitySettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'group_visibility_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'oec_group_flex.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('oec_group_flex.settings');

    $userRoles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $disallowedRoles = ['anonymous', 'authenticated'];
    $userRoleOptions = [];
    foreach ($userRoles as $role) {
      if (!in_array($role->id(), $disallowedRoles)) {
        $userRoleOptions[$role->id()] = $role->label();
      }
    }

    $defaultRoles = $config->get('oec_group_visibility_setings.restricted_community_members.internal_roles');
    $form['restricted_community_members_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Restricted community members roles'),
      '#description' => $this->t('Select roles for restricted community members visibility.'),
      '#options' => $userRoleOptions,
      '#default_value' => $defaultRoles ?: [],
      '#multiple' => TRUE,
    ];

    $defaultRoles = $config->get('oec_group_visibility_setings.custom_restricted.internal_roles');
    $form['custom_restricted_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Custom restricted roles'),
      '#description' => $this->t('Select roles for custom restricted visibility.'),
      '#options' => $userRoleOptions,
      '#default_value' => $defaultRoles ?: [],
      '#multiple' => TRUE,
    ];

    $defaultRoles = $config->get('oec_group_visibility_setings.sensitive.internal_roles');
    $form['sensitive_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Sensitive roles'),
      '#description' => $this->t('Select roles for sensitive visibility.'),
      '#options' => $userRoleOptions,
      '#default_value' => $defaultRoles ?: [],
      '#multiple' => TRUE,
    ];

    $defaultRoles = $config->get('oec_group_flex_admin_roles');
    $form['admin_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Drupal admin roles'),
      '#description' => $this->t('Select roles that are considered as admin.'),
      '#options' => $userRoleOptions,
      '#default_value' => $defaultRoles ?: [],
      '#multiple' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('oec_group_flex.settings')
      ->set('oec_group_visibility_setings.restricted_community_members.internal_roles', $form_state->getValue('restricted_community_members_roles'))
      ->set('oec_group_visibility_setings.sensitive.internal_roles', $form_state->getValue('sensitive_roles'))
      ->set('oec_group_visibility_setings.custom_restricted.internal_roles', $form_state->getValue('custom_restricted_roles'))
      ->set('oec_group_flex_admin_roles', $form_state->getValue('admin_roles'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
