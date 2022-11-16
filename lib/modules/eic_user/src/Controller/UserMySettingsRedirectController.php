<?php

namespace Drupal\eic_user\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a redirect to the user settings page.
 *
 * @package Drupal\eic_user\Controller
 */
class UserMySettingsRedirectController extends ControllerBase {

  /**
   * Redirects the user to the 'My settings' page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   */
  public function build() {
    return $this->redirect('eic_user.my_settings', ['user' => $this->currentUser()->id()]);
  }

}
