<?php

namespace Drupal\eic_groups\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrganisationEditDescriptionForm extends FormBase {

  private ?GroupInterface $group;

  /**
   * Constructs a new StatusUpdateForm.
   *
   */
  public function __construct() {
    $this->group = $this->getRequest()->get('group');
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'eic_groups_organisation_edit_form';
  }

  /**
   * Builds the publish group page title.
   */
  public function getFormTitle(GroupInterface $group) {
    return $this->t('Edit description of %label organisation',
      [
        '%label' => $group->label(),
      ]
    );
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ((!($this->group instanceof GroupInterface)) || $this->group->bundle() !== 'organisation') {
      throw new AccessDeniedHttpException();
    }

    $current_values = $this->group->get('field_body')->first()->getValue();

    $form['description'] = [
      '#type' => 'text_format',
      '#title' => 'Description',
      '#format' => $current_values['format'],
      '#default_value' => $current_values['value'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save description'),
      ],
    ];

    return $form;

  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->group
      ->set('field_body', $form_state->getValue('description'))
      ->save();
    $this->messenger()->addStatus($this->t('Description for %label has been updated', ['%label' => $this->group->label()]));
    $form_state->setRedirectUrl($this->group->toUrl());
  }

  public function access(AccountInterface $account) {
    if ((!($this->group instanceof GroupInterface)) || $this->group->bundle() !== 'organisation') {
      return AccessResult::forbidden('Only applicable for organisation group entities.');
    }
    $permission = 'edit organisation description';
    $membership = $this->group->getMember($account);
    if ($membership) {
      return AccessResult::allowedIf($membership->hasPermission($permission));
    }
    return AccessResult::forbidden("The '$permission' permission is required.");
  }

}
