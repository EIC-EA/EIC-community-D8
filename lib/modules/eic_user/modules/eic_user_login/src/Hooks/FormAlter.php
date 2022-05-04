<?php

namespace Drupal\eic_user_login\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook implementations around forms.
 *
 * @package Drupal\eic_user_login\Hooks
 */
class FormAlter implements ContainerInjectionInterface {

  /**
   * The eic_user_login temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The Private temp store factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('eic_user_login');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formProfileFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $form['actions']['submit']['#submit'][] = [$this, 'formProfileFormSubmit'];
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
