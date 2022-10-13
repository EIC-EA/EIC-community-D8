<?php

namespace Drupal\eic_user_login\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\cas\Service\CasUserManager;
use Drupal\eic_user\UserHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook implementations around forms.
 *
 * @package Drupal\eic_user_login\Hooks
 */
class FormAlter implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * The cas user manager.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * The eic_user_login temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\cas\Service\CasUserManager $cas_user_manager
   *   The cas user manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The Private temp store factory.
   */
  public function __construct(CasUserManager $cas_user_manager, PrivateTempStoreFactory $temp_store_factory) {
    $this->casUserManager = $cas_user_manager;
    $this->tempStore = $temp_store_factory->get('eic_user_login');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cas.user_manager'),
      $container->get('tempstore.private')
    );
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formUserFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $form_state->getFormObject()->getEntity();

    $is_power_user = UserHelper::isPowerUser(\Drupal::currentUser());
    $is_cas_account = $this->casUserManager->getCasUsernameForAccount($account->id());

    // Remove access to SMED related fields for non-power users.
    if (!$is_power_user) {
      $form['field_user_status']['#access'] = FALSE;
      $form['field_message_from_service']['#access'] = FALSE;
      $form['field_updated_profile_by_service']['#access'] = FALSE;
      $form['field_updated_profile_by_user']['#access'] = FALSE;
    }

    // Disable field_updated_profile_by_service and
    // field_updated_profile_by_user.
    $form['field_updated_profile_by_service']['#disabled'] = TRUE;
    $form['field_updated_profile_by_user']['#disabled'] = TRUE;

    // Disable access to fields if user is managed by cas.
    if ($is_cas_account && !$is_power_user) {
      $form['field_first_name']['#disabled'] = TRUE;
      $form['field_last_name']['#disabled'] = TRUE;
    }

    $form['actions']['submit']['#submit'][] = [$this, 'formUserFormSubmit'];
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formProfileFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $form['actions']['submit']['#submit'][] = [$this, 'formProfileFormSubmit'];
  }

  /**
   * Custom submit callback for the user form.
   *
   * @param array $form
   *   The form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function formUserFormSubmit(array $form, FormStateInterface $form_state) {
    if (empty($form['member_profiles']['widget'][0]['entity'])) {
      return;
    }
    // Check if profile is completed.
    if ($form['member_profiles']['widget'][0]['entity']['#validated'] === TRUE) {
      $this->tempStore->delete('is_profile_completed');
    }
  }

  /**
   * Custom submit callback for the profile form.
   *
   * @param array $form
   *   The form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function formProfileFormSubmit(array $form, FormStateInterface $form_state) {
    if ($form_state->getFormObject()->getEntity()->validate()) {
      $this->tempStore->delete('is_profile_completed');
    }
  }

}
