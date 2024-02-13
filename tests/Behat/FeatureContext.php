<?php

declare(strict_types = 1);

namespace OpenEuropa\Site\Tests\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use InvalidArgumentException;

/**
 * Defines step definitions that are generally useful for the project.
 */
class FeatureContext extends RawDrupalContext {

  /**
   * Checks that a 403 Access Denied error occurred.
   *
   * @Then I should get an access denied error
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when a different HTTP response code was returned.
   */
  public function assertAccessDenied(): void {
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Checks that a 200 OK response occurred.
   *
   * @Then I should get a valid web page
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when a different HTTP response code was returned.
   */
  public function assertSuccessfulResponse(): void {
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Keep desktop screen dimensions, so as not to get hamburger menu.
   *
   * @BeforeStep
   */
  public function resizeWindowStep()
  {
    $is_session = $this->getMink()->isSessionStarted();
    if (!$is_session) {
      $this->getMink()->getSession()->start();
    }
    $this->getSession()->resizeWindow(1024, 1400, 'current');
  }

  /**
   * @When I click on the text :arg1
   *
   * @param $arg1
   * @return void
   */
  public function iClickOnTheText2($arg1)
  {
    $session = $this->getSession();
    $element = $session->getPage()->findLink("Your profile");
    if (null == $element) {
      throw new InvalidArgumentException(sprintf('Cannot find text: "%s"', $arg1));
    }

    $element->click();
  }

  /**
   * Sets the specified multi-line value to the field
   *
   * @Given I fill the textarea :field with :value
   */
  public function iFillTextarea($field, $value) {
    $session = $this->getSession();
    $element = $session->getPage()->findById($field);
    $element->setValue($value);
  }
}
