<?php

namespace Drupal\eic_user_login\Exception;

/**
 * Login exception class for SMED user login process.
 */
class SmedUserLoginException extends \Exception {

  /**
   * The SMED defined user status.
   *
   * @var string
   */
  protected $userStatus;

  /**
   * A user message when login is not authorised by the SMED.
   *
   * @var \Drupal\Component\Render\MarkupInterface|string
   */
  protected $userMessage;

  /**
   * Sets a user status when login failed.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $status
   *   The user status.
   */
  public function setUserStatus($status) {
    $this->userStatus = $status;
  }

  /**
   * Returns the user status if login failed.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The user status.
   */
  public function getUserStatus() {
    return $this->userStatus;
  }

  /**
   * Sets a user message when login failed.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $message
   *   A user message to be set along with the exception.
   */
  public function setUserMessage($message) {
    $this->userMessage = $message;
  }

  /**
   * Returns the user message if login failed.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The reason why login failed, if any.
   */
  public function getUserMessage() {
    return $this->userMessage;
  }

}
