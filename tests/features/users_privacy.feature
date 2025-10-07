@api
@javascript

Feature: Personal user information should not be visible to not logged in users.

  Scenario: Check if information of "Wiki Page" author is visible to authorised and anonymous users.
    Given users:
      |name      | field_first_name         | field_last_name             |mail                   | roles         |
      |behat     | behat_user               | wiki_page_author_testing    |member@behat.localhost | administrator |
    Given the user "behat" has profile with data:
      |field_body         | field_vocab_topic_expertise    | field_vocab_topic_interest       |field_vocab_geo  |
      |behat profile body | Biotechnology,Energy,Finance   | Biotechnology,Energy,Finance     |203,204,205      |
    Given I am logged in as "behat"
    Given "wiki_page" content:
      |title             | field_body     | field_vocab_topics    | moderation_state | author | sticky | promote |
      |Behat Wiki Page   | wiki page body | Business development  | published        | behat  | 1      | 1       |
    And I run cron
    And I visit the "Business development" overview page
    And I should see the link "behat_user wiki_page_author_testing" in the "content" region
    Given I am not logged in
    Given I am an anonymous user
    And I visit the "Business development" overview page
    And I should not see the text "behat_user wiki_page_author_testing" in the "content" region

  Scenario: Check if information of "Document" author is visible to authorised and anonymous users.
    Given users:
      |name      | field_first_name         | field_last_name           |mail                   | roles         |
      |behat     | behat_user               | document_author_testing   |member@behat.localhost | administrator |
    Given the user "behat" has profile with data:
      |field_body           | field_vocab_topic_expertise    | field_vocab_topic_interest       |field_vocab_geo  |
      |behat profile body   | Biotechnology,Energy,Finance   | Biotechnology,Energy,Finance     |203,204,205      |
    Given I am logged in as "behat"
    Given "document" content:
      | name              | file       | author | title             |
      | My Behat Document | sample.pdf | behat  | My Behat Document |
    Given "document" content:
      | title          | field_body       | field_document_media    | field_vocab_topics    | author   | sticky | promote | moderation_state |
      | Behat Document | document body    | sample.pdf              | Business development  | behat    | 1      | 1       | published        |
    And I run cron
    And I visit the "Business development" overview page
    And I should see the link "behat_user document_author_testing" in the "content" region
    Given I am not logged in
    Given I am an anonymous user
    And I visit the "Business development" overview page
    And I should not see the text "behat_user document_author_testing" in the "content" region

  Scenario: Check if information of "Discussion" author is visible to authorised and anonymous users.
    Given users:
      |name      | field_first_name         | field_last_name             |mail                   | roles         |
      |behat     | behat_user               | discussion_author_testing   |member@behat.localhost | administrator |
    Given the user "behat" has profile with data:
      |field_body           | field_vocab_topic_expertise    | field_vocab_topic_interest       |field_vocab_geo  |
      |behat profile body   | Biotechnology,Energy,Finance   | Biotechnology,Energy,Finance     |203,204,205      |
    Given I am logged in as "behat"
    Given "discussion" content:
      | title            | field_body       | field_vocab_topics    | field_discussion_type | author   | moderation_state | sticky | promote |
      | Behat Discussion | discussion body  | Business development  | Information           | behat    | published        | 1      | 1       |
    And I run cron
    And I visit the "Business development" overview page
    And I should see the link "behat_user discussion_author_testing" in the "content" region
    Given I am not logged in
    Given I am an anonymous user
    And I visit the "Business development" overview page
    And I should not see the text "behat_user discussion_author_testing" in the "content" region

  Scenario: Check if information of "Group" author is visible to authorised and anonymous users.
    Given users:
      |name      | field_first_name         | field_last_name         |mail                   | roles         |
      |behat     | behat_user               | group_author_testing    |member@behat.localhost | administrator |
    Given the user "behat" has profile with data:
      |field_body         | field_vocab_topic_expertise    | field_vocab_topic_interest       |field_vocab_geo  |
      |behat profile body | Biotechnology,Energy,Finance   | Biotechnology,Energy,Finance     |203,204,205      |
    Given I am logged in as "behat"
    Given there is a group with data:
      |title        | field_body    | field_vocab_topics   | author   | moderation_state | visibility |
      |Behat Group  | group body    | Business development | behat    | published        | Public (The group and all its content will be viewed by anonymous users and logged in users) |
    And I run cron
    And I visit the "Business development" overview page
    And I should see the link "behat_user group_author_testing" in the "content" region
    Given I am not logged in
    Given I am an anonymous user
    And I visit the "Business development" overview page
    And I should not see the text "behat_user group_author_testing" in the "content" region

  Scenario: Check if information of "Story" author is visible to authorised and anonymous users.
    Given users:
      |name      | field_first_name         | field_last_name             |mail                   | roles         |
      |behat     | behat_user               | story_author_testing        |member@behat.localhost | administrator |
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
    And I run cron
    And I visit the "Business development" overview page
    And I should see the link "behat_user story_author_testing" in the "content" region
    Given I am not logged in
    Given I am an anonymous user
    And I visit the "Business development" overview page
    And I should not see the text "behat_user story_author_testing" in the "content" region
