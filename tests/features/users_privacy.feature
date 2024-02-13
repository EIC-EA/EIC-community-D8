@api
@javascript

Feature: Author information should not be visible to anonymous users.

  Scenario: Create a wiki page.
    Given I am logged in as a user with the "administrator" role
    And I fill in "First name" with "behat_user"
    And I fill in "Last name" with "programmatically_created"
    When I switch to tab "1"
    And I select "Greece" from "Country"
    When I switch to the CKEditor iframe and fill it with "Behat User's content"
    And I fill in "City" with "Thessaloniki"
    When I switch to tab "0"
    And I press "Save"
    When I visit "http://eic-community.ddev.site/node/add/wiki_page"
    And I fill in "Title" with "Behat Wiki Page"
    When I switch to the CKEditor iframe and fill it with "Wiki page Content"
    And I select "Published" from "Save as"
    And I press "Save"

  Scenario: Wiki page author's info should not be accessible to anonymous users.
    Given I am an anonymous user
    And I visit "http://eic-community.ddev.site/topics/business-development"
    Then I should see the text "Related wiki pages"
    And I save screenshot
    Then I should not see the "wiki page" author info




