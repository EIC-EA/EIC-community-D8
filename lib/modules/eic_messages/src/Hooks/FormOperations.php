<?php

namespace Drupal\eic_messages\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eic_content\EICContentHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormAlter.
 *
 * Implementations for entity hooks.
 */
class FormOperations implements ContainerInjectionInterface {

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
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\eic_content\EICContentHelper $content_helper
   *   The EIC content helper service.
   */
  public function __construct(RouteMatchInterface $route_match, EICContentHelper $content_helper) {
    $this->routeMatch = $route_match;
    $this->eicContentHelper = $content_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('eic_content.helper')
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
  protected function handleFieldPostActivity(array &$form, FormStateInterface $form_state, string $form_id) {
    $is_group_content = FALSE;
    $is_new_content = FALSE;

    // Check if we are adding a content into a group.
    if ($this->routeMatch->getRouteName() == 'entity.group_content.create_form') {
      $is_group_content = TRUE;
      $is_new_content = TRUE;
    }
    // Check we are updating a node which has an associated GroupContent entity.
    elseif ($node = $form_state->getFormObject()->getEntity()) {
      if (!$node->isNew() && $this->eicContentHelper->getGroupContentByEntity($node)) {
        $is_group_content = TRUE;
      }
    }

    // Test if we are creating or editing a group content.
    if ($is_group_content && $form_state->get('form_display')->getComponent('field_post_activity')) {
      $form['field_post_activity'] = [
        '#title' => $this->t('Post message in the activity stream'),
        '#type' => 'checkbox',
        '#default_value' => $is_new_content,
      ];
    }
  }

}
