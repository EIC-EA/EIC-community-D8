<?php

declare(strict_types = 1);

namespace OpenEuropa\Site\Tests\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;

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
    $this->getSession()->resizeWindow(1024, 2100, 'current');
  }

  /**
   * @When I switch to tab :index
   * @return void
   */
  public function iSwitchTabs($index)
  {
    $this->getSession()->getPage()->find('css', 'li.horizontal-tab-button-' . $index)->click();
  }

  /**
   * @When I switch to the CKEditor iframe and fill it with :text
   */
  public function iSwitchToCKEditorIframe($text)
  {
    // Switch to the CKEditor iframe.
    $this->getSession()->switchToIFrame(0);
    $bodyElement = $this->getSession()->getPage()->find('css', 'body.cke_editable');
    // Fill it with the text given.
    $bodyElement->setValue($text);
    // Switch back.
    $this->getSession()->switchToIFrame();
  }

  /**
   * @Then I should not see the :content author info
   * @return void
   */
  public function iShouldNotSeeTheAuthorInfo($content) {
    // Find the author elements on the page
    $author = $this->getSession()->getPage()->find('css', '.ecl-author');
    if ($author->isVisible()) {
      throw new \exception('Author name is visible to anonymous user');
    }
  }

}
