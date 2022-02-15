<?php

namespace Drupal\eic_webservices\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure EIC Webservices settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the EIC Webservices config form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
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
  public function getFormId() {
    return 'eic_webservices_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eic_webservices.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // API Key.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webservice API Key'),
      '#default_value' => $this->config('eic_webservices.settings')->get('api_key'),
      '#description' => $this->t('Defines the key required for the webservices.'),
    ];

    // SMED ID field.
    $form['smed_id_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMED field ID'),
      '#default_value' => $this->config('eic_webservices.settings')->get('smed_id_field'),
      '#required' => TRUE,
      '#description' => $this->t('Defines the field being used to match the SMED ID. This field should be the same for all entity types and bundles.'),
    ];

    // Webservice user account.
    $ws_user_account_id = $this->config('eic_webservices.settings')->get('webservice_user_account');
    $form['webservice_user_account'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Webservice user account'),
      '#target_type' => 'user',
      '#default_value' => $ws_user_account_id ? $this->entityTypeManager->getStorage('user')->load(($ws_user_account_id)) : NULL,
      '#required' => TRUE,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'filter' => [
          'roles' => ['service_authentication'],
        ],
      ],
      '#description' => $this->t('Select the user account which will be used to perform all operations. The account must have the %role_name role.', ['%role_name' => 'service_authentication']),
    ];

    // Group author.
    $author_id = $this->config('eic_webservices.settings')->get('group_author');
    $form['group_author'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Group author'),
      '#target_type' => 'user',
      '#default_value' => $author_id ? $this->entityTypeManager->getStorage('user')->load(($author_id)) : NULL,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'filter' => [
          'status' => 1,
        ],
      ],
      '#description' => $this->t('Select the user account which will be the author for new group entities created through the webservice.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('eic_webservices.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('smed_id_field', $form_state->getValue('smed_id_field'))
      ->set('webservice_user_account', $form_state->getValue('webservice_user_account'))
      ->set('group_author', $form_state->getValue('group_author'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
