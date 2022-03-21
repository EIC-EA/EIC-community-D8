<?php

namespace Drupal\eic_events\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_events\Constants\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FormOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formGroupFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $this->handleEventTypeField($form, $form_state, $form_id);
  }

  /**
   * Handles the field_vocab_event_type of the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param string $form_id
   *   The form ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function handleEventTypeField(array &$form, FormStateInterface $form_state, string $form_id) {
    if (!isset($form['field_vocab_event_type'])) {
      return;
    }

    // Get the entity.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    // Check if it is an event.
    if ($entity->bundle() != 'event') {
      return;
    }

    // We need to hide terms that are not to be displayed to end users.
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(
      ['vid' => Event::GROUP_EVENT_TYPE_VOCABULARY_NAME]
    );
    foreach ($terms as $term) {
      // We only hide the term if it's not to be displayed and is not being
      // used already.
      if ($term->field_display_to_users->value != 1
          && !in_array($term->id(), $form['field_vocab_event_type']['widget']['#default_value'])) {
        unset($form['field_vocab_event_type']['widget']['#options'][$term->id()]);
      }
    }
  }

}
