<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_content\EICContentHelper;
use Drupal\eic_messages\ActivityStreamOperationTypes;
use Drupal\eic_messages\Service\GroupContentMessageCreator;
use Drupal\eic_messages\Util\ActivityStreamMessageTemplates;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormOperations implements ContainerInjectionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The EIC content helper service.
   *
   * @var \Drupal\eic_content\EICContentHelper
   */
  protected $eicContentHelper;

  /**
   * The EIC content helper service.
   *
   * @var \Drupal\eic_messages\Service\GroupContentMessageCreator
   */
  protected $groupContentMessageCreator;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_content\EICContentHelper $content_helper
   *   The EIC content helper service.
   * @param \Drupal\eic_messages\Service\GroupContentMessageCreator $group_content_message_creator
   *   The GroupContent Message Creator service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EICContentHelper $content_helper,
    GroupContentMessageCreator $group_content_message_creator
  ) {
    $this->routeMatch = $route_match;
    $this->eicContentHelper = $content_helper;
    $this->groupContentMessageCreator = $group_content_message_creator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_content.helper'),
      $container->get('class_resolver')->getInstanceFromDefinition(GroupContentMessageCreator::class)
    );
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $this->handleFieldPostActivity($form, $form_state, $form_id);
  }

  /**
   * Handles the field_post_activity.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   * @param string $form_id
   *   The form ID.
   */
  protected function handleFieldPostActivity(
    array &$form,
    FormStateInterface $form_state,
    string $form_id
  ) {
    // All types that is by default unchecked.
    $field_disable_by_default_types = [
      'document',
      'video',
      'gallery',
    ];

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $is_group_content = FALSE;
    $is_new_content = FALSE;

    // Check if we are adding a content into a group.
    if ($this->routeMatch->getRouteName() == 'entity.group_content.create_form') {
      $is_group_content = TRUE;
      $is_new_content = TRUE;
    }
    // Check we are updating a node which has an associated GroupContent entity.
    if ($node = $form_state->getFormObject()->getEntity()) {
      if (!$node->isNew() && $this->eicContentHelper->getGroupContentByEntity($node)) {
        $is_group_content = TRUE;
      }
    }

    // Test if we are creating or editing a group content.
    if ($is_group_content && $form_state->get('form_display')
      ->getComponent('field_post_activity')) {

      // We show the field_post_activity if the node has an Activity message
      // template.
      if (ActivityStreamMessageTemplates::hasTemplate($node)) {
        $form['field_post_activity'] = [
          '#title' => $this->t('Post message in the activity stream'),
          '#type' => 'checkbox',
          '#default_value' => $is_new_content && !in_array($entity->bundle(), $field_disable_by_default_types),
        ];
        $form['actions']['submit']['#submit'][] = [$this, 'postActivitySubmit'];
      }
    }
  }

  /**
   * Handles the node form submit for the field_post_activity.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   */
  public function postActivitySubmit(array $form, FormStateInterface $form_state) {
    // If field doesn't exist or value is 0 or empty.
    if (empty($form_state->getValue('field_post_activity'))) {
      return;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $group = $this->routeMatch->getParameter('group');
    if (!$group instanceof GroupInterface) {
      $group_content = $this->eicContentHelper->getGroupContentByEntity($entity);
      if (empty($group_content)) {
        return;
      }

      $group_content = reset($group_content);
      $group = $group_content->getGroup();
    }

    $operation = $form_state->getFormObject()->getOperation() === 'edit'
      ? ActivityStreamOperationTypes::UPDATED_ENTITY
      : ActivityStreamOperationTypes::NEW_ENTITY;

    $this->groupContentMessageCreator->createGroupContentActivity(
      $entity,
      $group,
      $operation
    );
  }

}
