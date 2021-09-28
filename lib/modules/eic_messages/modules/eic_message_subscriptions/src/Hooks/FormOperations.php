<?php

namespace Drupal\eic_message_subscriptions\Hooks;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_content\EICContentHelper;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvent;
use Drupal\eic_message_subscriptions\Event\MessageSubscriptionEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_content\EICContentHelper $content_helper
   *   The EIC content helper service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EICContentHelper $content_helper,
    EventDispatcherInterface $event_dispatcher,
    CacheBackendInterface $cache_backend
  ) {
    $this->routeMatch = $route_match;
    $this->eicContentHelper = $content_helper;
    $this->eventDispatcher = $event_dispatcher;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_content.helper'),
      $container->get('event_dispatcher'),
      $container->get('cache.default')
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
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $show_notification_field = FALSE;

    switch ($entity->getEntityTypeId()) {
      case 'node':
        $show_notification_field = TRUE;
        break;

    }

    if ($show_notification_field) {
      $form['field_send_notification'] = [
        '#title' => $this->t('Send notification'),
        '#type' => 'checkbox',
        '#default_value' => $entity->isNew(),
      ];
      $form['actions']['submit']['#submit'][] = [
        $this,
        'sendNotificationSubmit',
      ];
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
        $group_contents = $this->eicContentHelper->getGroupContentByEntity($entity);

        if (empty($group_contents)) {
          // If we are creating a new group content, we handle the notification
          // at a later stage because at this point we don't have the group
          // content ID that is associated with this node.
          if ($form_id === "node_{$entity->bundle()}_form") {

            if ($route_name === 'entity.group_content.create_form') {
              // Add new cache ID that identifies if an entity needs to trigger
              // a subscription notification.
              $cid = "eic_message_subscriptions:entity_notify:{$entity->getEntityTypeId()}:{$entity->id()}";
              // Cache the result.
              $this->cacheBackend->set($cid, TRUE);
              break;
            }

            // Instantiate MessageSubscriptionEvent.
            $event = new MessageSubscriptionEvent($entity);
            // Dispatch the event 'eic_message_subscriptions.node_insert'.
            $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::NODE_INSERT);
            break;
          }

          // Instantiate MessageSubscriptionEvent.
          $event = new MessageSubscriptionEvent($entity);
          // Dispatch the event 'eic_message_subscriptions.node_update'.
          $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::NODE_UPDATE);
          break;
        }

        // Instantiate MessageSubscriptionEvent.
        $event = new MessageSubscriptionEvent($entity);
        // Dispatch the event 'eic_message_subscriptions.group_content_update'.
        $this->eventDispatcher->dispatch($event, MessageSubscriptionEvents::GROUP_CONTENT_UPDATE);
        break;

    }
  }

}
