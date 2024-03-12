<?php

declare(strict_types = 1);

namespace OpenEuropa\Site\Tests\Behat;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\profile\Entity\Profile;

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
   * Create profile for given user.
   *
   * @Given the user :name has profile with data:
   */
  public function createProfile($name, TableNode $usersTable)
  {
    $user = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $name]);
    $profile = \Drupal::entityTypeManager()
      ->getStorage('profile')
      ->loadByProperties([
        'uid' => key($user),
      ]);

    // Get all values of the table.
    $fields = $usersTable->getRow(0);
    $values = $usersTable->getRow(1);
    $table = array_combine($fields, $values);

    if (!$profile) {
      $profile = Profile::create([
        'type' => 'member',
        'uid' => key($user),
      ]);
      // Set field values for profile.
      $term_ids = [];
      foreach ($table as $key => $value) {
        if ($key == 'field_body') {
          $profile->set($key, $value);
        }
        else {
          $terms = explode(",", $value);
          foreach ($terms as $term) {
            $term_obj = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term]);
            $term_ids[] = key($term_obj);
          }
          $profile->set($key, $term_ids);
        }
      }
      // Set the country and city values.
      $address_values = [
        'locality' => 'Mountain View',
        'administrative_area' => 'CA',
        'country_code' => 'US',
      ];
      $profile->field_location_address->setValue($address_values);
      $profile->save();
    }
  }

  /**
   * Get the id of a user.
   *
   * @Given I get the :user_name uid
   */
  public function iGetTheUid ($user_name) {
    $user = user_load_by_name($user_name);
    if ($user) {
      return $user->id();
    }
    return NULL;
  }

}
