<?php

namespace Drupal\eic_moderation\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_moderation\Constants\EICContentModeration;
use Drupal\eic_moderation\Service\ContentModerationManager;
use Drupal\group\Context\GroupRouteContextTrait;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormOperations.
 *
 * Implementations for form hooks.
 */
class FormOperations implements ContainerInjectionInterface {

  use DependencySerializationTrait;
  use GroupRouteContextTrait;
  use StringTranslationTrait;

  /**
   * The Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The Moderation information service.
   *
   * @var \Drupal\eic_moderation\Service\ContentModerationManager
   */
  protected $contentModerationManager;

  /**
   * The current route match.
   *
   * @var \Drupal\eic_groups\EICGroupsHelper
   */
  protected $groupsHelper;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new FormOperations object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The Moderation information service.
   * @param \Drupal\eic_moderation\Service\ContentModerationManager $content_moderation_manager
   *   The EIC Content Moderation manager.
   * @param \Drupal\eic_groups\EICGroupsHelper $groups_helper
   *   The EIC Groups helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current suer account.
   */
  public function __construct(
    ModerationInformationInterface $moderation_information,
    ContentModerationManager $content_moderation_manager,
    EICGroupsHelper $groups_helper,
    AccountProxyInterface $current_user
    ) {
    $this->moderationInformation = $moderation_information;
    $this->contentModerationManager = $content_moderation_manager;
    $this->groupsHelper = $groups_helper;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('eic_moderation.content_moderation_manager'),
      $container->get('eic_groups.helper'),
      $container->get('current_user')
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

    // If we are in a group context.
    if ($group = $this->hasGroupContext($entity)) {
      // Make sure we show valid transitions only inside groups.
      $this->filterFormElementTransitions($form['moderation_state'], $entity, $group, $this->currentUser);

    }
    else {
      // In case of a global content, add our custom validation handler.
      $form['#validate'][] = [$this, 'eicModerationFormNodeFormValidate'];
    }

    if (isset($form['moderation_state']['widget'][0]['#access'])) {
      $form['moderation_state']['#access'] = $form['moderation_state']['widget'][0]['#access'];
    }
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

  /**
   * Filters the allowed target states for moderation_state field.
   *
   * This will filter out non-allowed states based on the user's transitions
   * permissions.
   *
   * This function is needed since gcontent_moderation workflow also includes
   * global permissions. In our case we need group permissions only.
   * See https://www.drupal.org/project/gcontent_moderation/issues/3225717
   *
   * @param array $element
   *   The form element coming from $form.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being edited.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group context.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which we check permissions.
   */
  public function filterFormElementTransitions(array &$element, ContentEntityInterface $entity, GroupInterface $group, AccountInterface $account) {
    $target_states = [];
    /** @var \Drupal\workflows\TransitionInterface $transition */
    foreach ($this->contentModerationManager->getAllowedTransitions($entity, $group, $account) as $transition) {
      $target_states[] = $transition->to()->id();
    }

    // @todo Find a cleaner way to check the form widget being used.
    // In case of content_moderation widget.
    if (!empty($element['widget'][0]['state']['#options'])) {
      foreach ($element['widget'][0]['state']['#options'] as $state => $label) {
        if (!in_array($state, $target_states)) {
          unset($element['widget'][0]['state']['#options'][$state]);
        }
      }
    }

    // In case of workflow_buttons widget.
    if (!empty($element['widget'][0]['#options'])) {
      foreach ($element['widget'][0]['#options'] as $state => $label) {
        if (!in_array($state, $target_states)) {
          unset($element['widget'][0]['#options'][$state]);
        }
      }
    }
  }

  /**
   * Determines if we are editing the given entity in a group context.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\group\Entity\GroupInterface|false
   *   The group entity if we are in a group context, FALSE otherwise.
   */
  public function hasGroupContext(ContentEntityInterface $entity) {
    // New entities are not yet associated with a group, but if we are using the
    // wizard we can discover the group from the route parameters.
    if ($entity->isNew() && $group = $this->getGroupFromRoute()) {
      return $group;
    }

    if (!$entity->isNew() && $group = $this->groupsHelper->getOwnerGroupByEntity($entity)) {
      return $group;
    }

    return FALSE;
  }

}
