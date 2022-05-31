<?php

namespace Drupal\eic_digest_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * @param \Drupal\eic_subscription_digest\Service\DigestManager $manager
   */
  public function __construct(DigestManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   **/
  public static function create(ContainerInterface $container) {
    return new static($container->get('eic_subscription_digest.manager'));
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

}
