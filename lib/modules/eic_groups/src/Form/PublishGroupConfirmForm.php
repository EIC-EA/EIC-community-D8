<?php

namespace Drupal\eic_groups\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelperInterface;
use Drupal\eic_groups\GroupsModerationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form before publishing the group.
 */
class PublishGroupConfirmForm extends ConfirmFormBase {

  /**
   * Current group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  private $group;

  /**
   * PublishGroupConfirmForm constructor.
   *
   * @param \Drupal\eic_groups\EICGroupsHelperInterface $eic_groups_helper
   *   The EIC Groups helper service.
   */
  public function __construct(EICGroupsHelperInterface $eic_groups_helper) {
    $this->group = $eic_groups_helper->getGroupFromRoute();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_groups.helper')
    );
  }

  /**
   * Gets the group entity object.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group entity.
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_groups_publish_group_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getQuestion();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to publish the @group-type %label?',
      [
        '@group-type' => $this->group->bundle(),
        '%label' => $this->group->label(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->group->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $current_group = \Drupal::entityTypeManager()->getStorage('group')->load($this->group->id());

    $moderation_state = $current_group->get('moderation_state')->value;

    // We need to make sure the group state hasn't change in the meantime.
    // Otherwise the group cannot be published.
    if ($moderation_state !== GroupsModerationHelper::GROUP_DRAFT_STATE) {
      $form_state->setError(
        $form,
        $this->t('The group can only be published when is in draft state. The current state is set to %moderation_state.',
          [
            '%moderation_state' => $moderation_state,
          ]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $publish_url_options = [];

    $request_query = $this->getRequest()->query;

    // Check if destination is in the URL query and if so, we add it to the
    // group publish url options.
    if ($request_query->has('destination')) {
      $publish_url_options = [
        'query' => [
          'destination' => $request_query->get('destination'),
        ],
      ];
    }

    // The logic to publish the group is done in 'eic_groups.group.publish'
    // route. Therefore, we redirect the user.
    $publish_url = Url::fromRoute('eic_groups.group.publish', ['group' => $this->group->id()], $publish_url_options);
    $response = new RedirectResponse($publish_url->toString());
    $response->send();
  }

}
