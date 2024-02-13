@api
@javascript

Feature: Make sure user information is not accessible to anonymous users

  Scenario: Create a wiki page
    Given I am logged in as a user with the "administrator" role
    And I fill in "First name" with "behat_user"
    And I fill in "Last name" with "programmatically_created"
    When I click on the text "Your profile"
    And I select "Greece" from "Country"
    And I fill the textarea "cke_189_contents" with "This is The bio of .."
    And I fill in "City" with "Thessaloniki"
    And I check the box "Administrator"
    And I check the box "English"
    And I press "Save"
    And I save screenshot
#    Given I am not logged in
    And I click "Manage"
    When I visit "Content"
    And I press "Add content"
    And I visit "Wiki Page"


