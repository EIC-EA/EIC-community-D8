<?php

namespace Drupal\eic_webservices\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
   * The list of fields to disable if event has been created through SMED.
   *
   * @var string[]
   */
  protected const EVENT_SMED_FIELDS = [
    'label',
    'field_body',
    'field_tag_line',
    'field_location',
    'field_link',
    'field_social_links',
    'field_vocab_event_type',
    'field_date_range',
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
   */
  public function __construct(AccountProxyInterface $current_user, EicWsHelper $eic_ws_helper) {
    $this->currentUser = $current_user;
    $this->wsHelper = $eic_ws_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('eic_webservices.ws_helper')
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
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $is_disabled = FALSE;
    foreach ($this::EVENT_SMED_FIELDS as $field_name) {
      if (isset($form[$field_name])) {
        $form[$field_name]['#disabled'] = TRUE;
        $is_disabled = TRUE;
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
