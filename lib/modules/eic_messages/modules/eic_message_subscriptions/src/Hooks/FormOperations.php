<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_content\Constants\DefaultContentModerationStates;
use Drupal\eic_content\EICContentHelper;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Drupal\eic_message_subscriptions\MessageSubscriptionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class FormOperations.
 *
 * Implementations for form hooks.
 *
 * @package Drupal\eic_message_subscriptions\Hooks
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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_content\EICContentHelper $content_helper
   *   The EIC content helper service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EICContentHelper $content_helper,
    StateInterface $state,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->routeMatch = $route_match;
    $this->eicContentHelper = $content_helper;
    $this->state = $state;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_content.helper'),
      $container->get('state'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  public function entityFormAlter(&$form, FormStateInterface $form_state, $form_id) {
    $this->handleFieldSendNotification($form, $form_state, $form_id);
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
  protected function handleFieldSendNotification(
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

    // All content type which have the notification field hidden.
    $disable_content_types = [
      'book',
    ];

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $show_notification_field = FALSE;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        // If node doesn't have notification we don't need to show the field.
        if (in_array($entity->bundle(), $disable_content_types)) {
          break;
        }

        $show_notification_field = TRUE;
        break;

    }

    if (
      $show_notification_field &&
      $form_state->get('form_display')
        ->getComponent('field_send_notification')
    ) {
      $form_state->set('previous_state', $entity->get('moderation_state')->value);

      if (!$entity->isNew()) {
        $latest_version = \Drupal::entityTypeManager()->getStorage('node')->load($entity->id());
        $form_state->set('entity_is_published', $latest_version->isPublished());
      }

      $is_new_content = $entity->isNew();
      // Check if the current node is the default revision and is in draft
      // state. If that's the case, it means there is no published or archived
      // version and therefore the "Send notification" checkbox should be
      // checked by default.
      if (
        !$is_new_content &&
        $entity->revision_default->value &&
        $entity->get('moderation_state')->value === DefaultContentModerationStates::DRAFT_STATE
      ) {
        $is_new_content = TRUE;
      }

      // We set the current publish status of the entity.
      $form_state->set('entity_is_published', $is_new_content ? FALSE : $entity->isPublished());

      $form['field_send_notification'] = [
        '#title' => $this->t('Send notifications to members.'),
        '#type' => 'checkbox',
        '#default_value' => $is_new_content && !in_array($entity->bundle(), $field_disable_by_default_types),
        '#description' => t('Notifications will not be sent as long your content is in Draft state.'),
      ];
      $form['actions']['submit']['#submit'][] = [
        $this,
        'sendNotificationSubmit',
      ];
    }
  }

  /**
   * Handles the node form submit for the field_send_notification.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   */
  public function sendNotificationSubmit(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    // If field is empty we do nothing.
    if (empty($form_state->getValue('field_send_notification'))) {
      return;
    }

    $form_id = $form_state->getFormObject()->getFormId();
    $route_name = $this->routeMatch->getRouteName();

    switch ($entity->getEntityTypeId()) {
      case 'node':
        // We don't create message subscription if node is in DRAFT or ARCHIVED
        // state.
        if (
          in_array(
            $entity->get('moderation_state')->value,
            [
              DefaultContentModerationStates::DRAFT_STATE,
              DefaultContentModerationStates::ARCHIVED_STATE,
            ]
          )
        ) {
          break;
        }

        // Node is not published and therefore we don't send any notification.
        if (!$entity->isPublished()) {
          break;
        }

        $group_contents = $this->eicContentHelper->getGroupContentByEntity($entity, [], ["group_node:{$entity->bundle()}"]);
        if (empty($group_contents)) {
          // If we are creating a new group content, we handle the notification
          // at a later stage because at this point we don't have the group
          // content ID that is associated with this node.
          if ($form_id === "node_{$entity->bundle()}_form") {

            // We make sure we are on the group content create form route.
            if ($route_name === 'entity.group_content.create_form') {
              // State cache ID that represents a new group content
              // creation.
              $state_key = MessageSubscriptionHelper::GROUP_CONTENT_CREATED_STATE_KEY;
              // Increments entity type and entity ID to the state cache ID.
              $state_key .= ":{$entity->getEntityTypeId()}:{$entity->id()}";
              // Adds the item to the state cache.
              $this->state->set($state_key, TRUE);
              break;
            }
          }
        }

        // Instantiate MessageSubscriptionEvent.
        $event = new MessageSubscriptionEvent($entity);

        // Dispatch the event to trigger message subscription notification
        // about new content published when content changes from
        if (
          $form_state->get('entity_is_published') === FALSE &&
          $entity->isPublished() &&
          empty($group_contents)
        ) {
          // Node is not part of a group content so we dispatch the message
          // subscription event for node creation.
          $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::NODE_INSERT);
          break;
        }

        // Dispatch the event to trigger message subscription notification
        // about group content updated.
        $this->eventDispatcher->dispatch(
          $event,
          $form_state->get('previous_state') === DefaultContentModerationStates::DRAFT_STATE
            ? MessageSubscriptionEvents::GROUP_CONTENT_INSERT
            : MessageSubscriptionEvents::GROUP_CONTENT_UPDATE
        );
        break;

    }
  }

}
