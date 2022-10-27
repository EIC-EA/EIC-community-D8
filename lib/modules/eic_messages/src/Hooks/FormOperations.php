<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
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
   * The Content moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $contentModerationInfo;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_content\EICContentHelper $content_helper
   *   The EIC content helper service.
   * @param \Drupal\eic_messages\Service\GroupContentMessageCreator $group_content_message_creator
   *   The GroupContent Message Creator service.
   * @param \Drupal\content_moderation\ModerationInformation $content_moderation_info
   *   The Content moderation information service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EICContentHelper $content_helper,
    GroupContentMessageCreator $group_content_message_creator,
    ModerationInformation $content_moderation_info
  ) {
    $this->routeMatch = $route_match;
    $this->eicContentHelper = $content_helper;
    $this->groupContentMessageCreator = $group_content_message_creator;
    $this->contentModerationInfo = $content_moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_content.helper'),
      $container->get('class_resolver')->getInstanceFromDefinition(GroupContentMessageCreator::class),
      $container->get('content_moderation.moderation_information')
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
      if (
        !$node->isNew() &&
        $this->eicContentHelper->getGroupContentByEntity($node, [], ["group_node:{$node->bundle()}"])
      ) {
        $is_group_content = TRUE;
      }
    }

    // Test if we are creating or editing a group content.
    if ($is_group_content && $form_state->get('form_display')
      ->getComponent('field_post_activity')) {

      // Check if the current node is the default revision and is in draft
      // state. If that's the case, it means there is no published or archived
      // version and therefore the post activity checkbox should be checked by
      // default.
      if (
        !$is_new_content &&
        $entity->revision_default->value &&
        $entity->get('moderation_state')->value === DefaultContentModerationStates::DRAFT_STATE
      ) {
        $is_new_content = TRUE;
      }

      // We show the field_post_activity if the node has an Activity message
      // template.
      if (ActivityStreamMessageTemplates::hasTemplate($node)) {
        $form['field_post_activity'] = [
          '#title' => $this->t('Post message in the activity stream'),
          '#type' => 'checkbox',
          '#default_value' => $is_new_content && !in_array($entity->bundle(), $field_disable_by_default_types),
        ];
        array_unshift(
          $form['actions']['submit']['#submit'],
          [$this, 'setActivityStreamOperation']
        );
        $form['actions']['submit']['#submit'][] = [$this, 'postActivitySubmit'];
      }
    }
  }

  /**
   * Submit handler to set the activity stream operation.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   */
  public function setActivityStreamOperation(array $form, FormStateInterface $form_state) {
    // If field doesn't exist or value is 0 or empty.
    if (empty($form_state->getValue('field_post_activity'))) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    // If entity is new or the current state is unpublished, then we set the
    // activity stream operation to "created".
    if (
      $entity->isNew() ||
      !$this->contentModerationInfo->isDefaultRevisionPublished($entity)
    ) {
      $form_state->set('activity_stream_operation', ActivityStreamOperationTypes::NEW_ENTITY);
    }
    else {
      $form_state->set('activity_stream_operation', ActivityStreamOperationTypes::UPDATED_ENTITY);
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
    if (
      empty($form_state->getValue('field_post_activity')) ||
      empty($form_state->get('activity_stream_operation'))
    ) {
      return;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    // If moderation state is set to DRAFT, we don't create activity stream
    // message.
    if (
      in_array(
        $entity->get('moderation_state')->value,
        [
          DefaultContentModerationStates::DRAFT_STATE,
        ]
      )
    ) {
      return;
    }

    $group = $this->routeMatch->getParameter('group');
    if (!$group instanceof GroupInterface) {
      $group_content = $this->eicContentHelper->getGroupContentByEntity($entity, [], ["group_node:{$entity->bundle()}"]);
      if (empty($group_content)) {
        return;
      }

      $group_content = reset($group_content);
      $group = $group_content->getGroup();
    }

    $operation = $form_state->get('activity_stream_operation');

    $this->groupContentMessageCreator->createGroupContentActivity(
      $entity,
      $group,
      $operation
    );
  }

}
