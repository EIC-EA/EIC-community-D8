<?php

namespace Drupal\eic_groups\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a redirect to the user invitations page.
 *
 * @package Drupal\eic_groups\Controller
 */
class UserMyInvitationsController extends ControllerBase {

  /**
   * Redirects the user to the 'My invitations' page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   */
  public function build() {
    return $this->redirect('view.my_invitations.page_1', ['user' => $this->currentUser()->id()]);
  }

}
