@local @local_wb_news @javascript

Feature: Cohort-based visibility restrictions on news items.
  News items can be restricted so they are only visible to members of
  selected cohorts. A user without the required cohort membership must NOT
  see the item; a member must see it. Users with the manage capability
  bypass all restrictions regardless of cohort membership.

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname |
      | student_member    | Student   | Member   |
      | student_nonmember | Student   | Other    |
      | news_manager      | News      | Manager  |
    And the following "cohorts" exist:
      | name        | idnumber  |
      | Members VIP | cohort_vip |
    And the following "cohort members" exist:
      | user           | cohort     |
      | student_member | cohort_vip |
    And the following "system role assigns" exist:
      | user         | role    |
      | news_manager | manager |
    And the following "local_wb_news > news instances" exist:
      | name      | template                   | columns | contexts  |
      | MainNews  | local_wb_news/wb_news_grid | 4       | System wide |
    And the following "local_wb_news > news items" exist:
      | instance | headline              | description              |
      | MainNews | Public Post           | Everyone can see this.   |
      | MainNews | VIP Members Only      | Cohort members only.     |
    And the news item "VIP Members Only" has a cohort restriction for "Members VIP" with match mode "any"

  @javascript
  Scenario: A student not in the cohort cannot see the restricted news item
    Given I log in as "student_nonmember"
    When I visit "/local/wb_news/index.php"
    And I click on "button[data-target^='#instance-']" "css_element"
    Then I should see "Public Post" in the ".wb-news-container" "css_element"
    And I should not see "VIP Members Only" in the ".wb-news-container" "css_element"

  @javascript
  Scenario: A student who is a cohort member can see the restricted news item
    Given I log in as "student_member"
    When I visit "/local/wb_news/index.php"
    And I click on "button[data-target^='#instance-']" "css_element"
    Then I should see "Public Post" in the ".wb-news-container" "css_element"
    And I should see "VIP Members Only" in the ".wb-news-container" "css_element"

  @javascript
  Scenario: A user with manage capability bypasses cohort restriction
    Given I log in as "news_manager"
    When I visit "/local/wb_news/index.php"
    And I click on "button[data-target^='#instance-']" "css_element"
    Then I should see "Public Post" in the ".wb-news-container" "css_element"
    And I should see "VIP Members Only" in the ".wb-news-container" "css_element"

  @javascript
  Scenario: A student removed from the cohort loses access to the restricted item
    Given I log in as "admin"
    And I visit "/cohort/members.php?id=1"
    And the following "cohort members" do not exist:
      | user           | cohort     |
      | student_member | cohort_vip |
    And I log out
    Given I log in as "student_member"
    When I visit "/local/wb_news/index.php"
    And I click on "button[data-target^='#instance-']" "css_element"
    Then I should see "Public Post" in the ".wb-news-container" "css_element"
    And I should not see "VIP Members Only" in the ".wb-news-container" "css_element"

  @javascript
  Scenario: MATCH_ALL mode requires membership of both cohorts
    Given the following "cohorts" exist:
      | name       | idnumber   |
      | Premium A  | cohort_a   |
      | Premium B  | cohort_b   |
    And the following "cohort members" exist:
      | user              | cohort   |
      | student_member    | cohort_a |
    And the following "local_wb_news > news items" exist:
      | instance | headline         | description         |
      | MainNews | Dual Cohort Post | Both cohorts needed |
    And the news item "Dual Cohort Post" has a cohort restriction for "Premium A,Premium B" with match mode "all"
    # student_member is in cohort_a but NOT cohort_b → must NOT see the item
    Given I log in as "student_member"
    When I visit "/local/wb_news/index.php"
    And I click on "button[data-target^='#instance-']" "css_element"
    Then I should not see "Dual Cohort Post" in the ".wb-news-container" "css_element"
