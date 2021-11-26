<?php

namespace Drupal\eic_group_membership\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eic_groups\EICGroupsHelper;
use Drupal\eic_user\UserHelper;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form before transfering group ownership.
 */
class TransferGroupOwnershipConfirmForm extends ConfirmFormBase {

  /**
   * Current group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  private $group;

  /**
   * Current group entity.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  private $groupContent;

  /**
   * The EIC User helper service.
   *
   * @var \Drupal\eic_user\UserHelper
   */
  private $eicUserHelper;

  /**
   * TransferOwnershipConfirmForm constructor.
   *
   * @param \Drupal\eic_user\UserHelper $eic_user_helper
   *   The EIC User helper service.
   */
  public function __construct(UserHelper $eic_user_helper) {
    $this->eicUserHelper = $eic_user_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eic_user.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL, GroupContentInterface $group_content = NULL) {
    $this->group = $group;
    $this->groupContent = $group_content;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Gets the user entity object from group content.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity from the group content.
   */
  public function getMembershipUser() {
    return $this->groupContent->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eic_transfer_group_ownership_confirm_form';
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
    $new_owner = $this->getMembershipUser();
    $previous_owner = EICGroupsHelper::getGroupOwner($this->group);
    return $this->t('<p>Are you sure you want to transfer ownership to %new_owner?</p>
      <p>If you confirm, the previous owner %previous_owner will turn into a group admin.</p>',
      [
        '%new_owner' => $this->eicUserHelper->getFullName($new_owner),
        '%previous_owner' => $this->eicUserHelper->getFullName($previous_owner),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Generates cancel URL based on EIC group members view.
    $cancel_url = Url::fromRoute(
      'view.eic_group_members.eic_group_members',
      [
        'group' => $this->group->id(),
      ]
    );
    return $cancel_url;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transfer_ownership_url_options = [];

    $request_query = $this->getRequest()->query;

    // Check if destination is in the URL query and if so, we add it to the
    // transfer ownership url options.
    if ($request_query->has('destination')) {
      $transfer_ownership_url_options = [
        'query' => [
          'destination' => $request_query->get('destination'),
        ],
      ];
    }

    // The logic to publish the group is done in
    // "eic_group_membership.transfer_ownership" route. Therefore, we redirect
    // the user.
    $transfer_ownership_url = Url::fromRoute(
      'eic_group_membership.transfer_ownership',
      [
        'group' => $this->group->id(),
        'group_content' => $this->groupContent->id(),
      ],
      $transfer_ownership_url_options
    );
    $response = new RedirectResponse($transfer_ownership_url->toString());
    $response->send();
  }

}
