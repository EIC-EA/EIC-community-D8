<?php

namespace Drupal\eic_webservices\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\eic_webservices\Utility\EicWsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormOperations implements ContainerInjectionInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The list of fields per bundle to disable if entity has been created through SMED.
   *
   * @var string[]
   */
  protected array $smedFields = [
    'event' => [
      'label',
      'field_tag_line',
      'field_location',
      'field_link',
      'field_social_links',
      'field_vocab_event_type',
      'field_date_range',
    ],
    'organisation' => [
      'label',
      'field_social_links',
    ],
  ];

  /**
   * List of fields only allowed editing by a Power User.
   *
   * @var array|array[]
   */
  protected array $powerUserFields = [
    'event' => [
      'field_body'
    ]
  ];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The EIC Webservices helper class.
   *
   * @var \Drupal\eic_webservices\Utility\EicWsHelper
   */
  protected $wsHelper;

  /**
   * Constructs a new FormOperations object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\eic_webservices\Utility\EicWsHelper $eic_ws_helper
   *   The EIC Webservices helper class.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(AccountProxyInterface $current_user, EicWsHelper $eic_ws_helper, ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->wsHelper = $eic_ws_helper;
    foreach ($this->smedFields as $bundle => &$fields) {
      $fields[] = $config_factory->get('eic_webservices.settings')->get('smed_id_field');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('eic_webservices.ws_helper'),
      $container->get('config.factory')
    );
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formUserFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Hide the SMED field if user is not allowed.
    if (isset($form[$this->wsHelper->getSmedIdFieldName()]) && !UserHelper::isPowerUser($this->currentUser)) {
      $form[$this->wsHelper->getSmedIdFieldName()]['#access'] = FALSE;
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formGroupFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Get the entity.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    // Hide the SMED field if user is not allowed.
    if (isset($form[$this->wsHelper->getSmedIdFieldName()]) && !UserHelper::isPowerUser($this->currentUser)) {
      $form[$this->wsHelper->getSmedIdFieldName()]['#access'] = FALSE;
    }

    if (!EICGroupsHelper::userIsGroupAdmin($entity, $this->currentUser)) {
      foreach ($this->powerUserFields[$entity->bundle()] as $powerUserField) {
        $form[$powerUserField]['#access'] = FALSE;
      }
    }

    // Disable SMED fields.
    if ($this->wsHelper->isCreatedThroughSmed($entity)) {
      $this->disableSmedFedFields($form, $form_state, $form_id);
    }
  }

  /**
   * Disable fields that are fed by SMED.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param string $form_id
   *   The form ID.
   */
  public function disableSmedFedFields(array &$form, FormStateInterface $form_state, string $form_id) {
    // Get the entity.
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $is_disabled = FALSE;
    if (isset($this->smedFields[$entity->bundle()])) {
      foreach ($this->smedFields[$entity->bundle()] as $field_name) {
        if (isset($form[$field_name])) {
          $form[$field_name]['#disabled'] = TRUE;
          $is_disabled = TRUE;
        }
      }
    }

    // Add a message to inform users why fields are disabled and point them to
    // SMED.
    if ($is_disabled) {
      $smed_url = $this->wsHelper->getSmedLink(
        'event-manage',
        $entity->get($this->wsHelper->getSmedIdFieldName())->value
      );

      $message = $this->t('The event was created via SME Dashboard, to modify the locked fields please go to: <a href="@smed_url" target="_blank">SME Dashboard Event Manager</a>', [
        '@smed_url' => $smed_url,
      ]);
      $this->messenger()->addStatus($message);
    }
  }

}
