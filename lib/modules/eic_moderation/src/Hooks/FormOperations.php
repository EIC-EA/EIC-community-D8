<?php

namespace Drupal\eic_moderation\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormOperations.
 *
 * Implementations for form hooks.
 */
class FormOperations implements ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs a new FormOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The Moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_information) {
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $entity = $form_state->getFormObject()->getEntity();
    if (!$this->moderationInformation->isModeratedEntity($entity)) {
      return;
    }

    if ($this->moderationInformation->getWorkflowForEntity($entity)->id() !== EICContentModeration::MACHINE_NAME) {
      return;
    }

    // Move the revision information for better visibility.
    if (isset($form['revision_information'])) {
      unset($form['revision_information']['#group']);
      $form['revision_information']['#weight'] = 100;
    }

    // Add our custom validation handler.
    $form['#validate'][] = [$this, 'eicModerationFormNodeFormValidate'];
  }

  /**
   * Custom validation handler for node forms.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function eicModerationFormNodeFormValidate(array &$form, FormStateInterface $form_state) {
    $mandatory_log_triggers = [
      'edit-moderation-state-waiting-for-approval',
      'edit-moderation-state-needs-review',
      'edit-moderation-state-published',
    ];

    if (in_array($form_state->getTriggeringElement()['#id'], $mandatory_log_triggers)) {
      if (empty($form_state->getValue('revision_log')[0]['value'])) {
        $form_state->setErrorByName('revision_log', $this->t('Please provide a log message.'));
      }
    }
  }

}
