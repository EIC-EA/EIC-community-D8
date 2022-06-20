<?php

namespace Drupal\eic_digest_test\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\eic_subscription_digest\Constants\DigestTypes;
use Drupal\eic_subscription_digest\Service\DigestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DigestTestUI
 *
 * @package Drupal\eic_digest\Form
 */
class DigestTestUI extends FormBase {

  /**
   * @var \Drupal\eic_subscription_digest\Service\DigestManager
   */
  private $manager;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * @param \Drupal\eic_subscription_digest\Service\DigestManager $manager
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(DigestManager $manager, StateInterface $state) {
    $this->manager = $manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   **/
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_subscription_digest.manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_digest_test_ui';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Concerned user'),
      '#target_type' => 'user',
      '#size' => 30,
      '#default_value' => $form_state->getValue('user'),
      '#required' => TRUE,
    ];
    $form['digest_trigger_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Digest trigger date'),
      '#default_value' => $form_state->getValue('digest_trigger_date'),
      '#required' => TRUE,
    ];
    $form['digest_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Digest type'),
      '#default_value' => $form_state->getValue('digest_type'),
      '#options' => array_combine(DigestTypes::getAll(), array_map('ucfirst', DigestTypes::getAll())),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Trigger digest'),
    ];
    $form['reset_states'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset digest sent times'),
      '#attributes' => [
        'title' => t("Allows you to re-trigger digests for users by resetting saved values for the last trigger."),
      ],
      '#ajax' => [
        'callback' => [$this, 'resetDigestStates'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!in_array($form_state->getValue('digest_type'), DigestTypes::getAll())) {
      $form_state->setErrorByName(
        'digest_type',
        $this->t('Digest type is invalid')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->manager->sendUserDigest([
      'digest_type' => $form_state->getValue('digest_type'),
      'uid' => $form_state->getValue('user'),
      'trigger_date' => $form_state->getValue('digest_trigger_date'),
    ]);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function resetDigestStates(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    foreach (DigestTypes::getAll() as $digest_type) {
      $this->state->delete('eic_subscription_digest_' . $digest_type . '_time');
    }

    $response->addCommand(
      new MessageCommand($this->t('Digest states have been reset'))
    );
    return $response;
  }

}
