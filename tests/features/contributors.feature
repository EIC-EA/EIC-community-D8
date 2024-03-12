@api
@javascript

Feature: Add contributors when creating content

  Scenario: Admins can be added as contributors when creating content.
    Given users:
      |name      | field_first_name         | field_last_name             |mail                   | roles         |
      |behat     | behat_user               | contributor_testing         |member@behat.localhost | administrator |
    And I get the "behat" uid
    And I save that into "USER"
    Given the user "behat" has profile with data:
      |field_body         | field_vocab_topic_expertise    | field_vocab_topic_interest       |field_vocab_geo  |
      |behat profile body | Biotechnology,Energy,Finance   | Biotechnology,Energy,Finance     |203,204,205      |
    Given I am logged in as "behat"
    And the following image:
      | name          | file                  | alt                            | title |
      | Cat Image     | cat.jpg               | Alternative text 1             | cat   |
    Given "story" content:
      | title         | field_body    | field_header_visual      | field_image  | field_introduction | field_vocab_program_type | field_vocab_story_type | field_vocab_topics    | author   | moderation_state |
      | Behat Story   | story body    | Cat Image                | Cat Image    | story introduction | EIC & EIT                | Events                 | Business development  | behat    | published        |
    And I visit "/stories/behat-story/edit"
    And I follow "Contributors"
    And I press the "Add Contributor" button
    And I select "Platform member" from "field_story_paragraphs[0][subform][paragraph_view_mode][0][value]"
    And I fill in "field_story_paragraphs[0][subform][field_user_ref][0][target_id]" with "behat_user contributor_testing (<<USER>>)"
    And I fill in "Revision message" with "revision message"
    And I press the "Publish the content" button
    And I should not see the text "This entity (user: <<USER>>) cannot be referenced."
