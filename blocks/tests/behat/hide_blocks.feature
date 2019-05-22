@core @core_block
Feature: Block visibility
  In order to configure blocks visibility
  As a teacher
  I need to show and hide blocks on a page

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I follow "Turn editing on"

  @javascript
  Scenario: Hiding all blocks on the page should remove the column they're in
    Given I open the "Search forums" blocks action menu
    And I click on "Hide Search forums block" "link" in the ".block_search_forums .block-control-actions" "css_element"
    And I open the "Latest news" blocks action menu
    And I click on "Hide Latest news block" "link" in the ".block_news_items .block-control-actions" "css_element"
    And I open the "Upcoming events" blocks action menu
    And I click on "Hide Upcoming events block" "link" in the ".block_calendar_upcoming .block-control-actions" "css_element"
    And I open the "Recent activity" blocks action menu
    When I click on "Hide Recent activity block" "link" in the ".block_recent_activity .block-control-actions" "css_element"
    Then ".empty-region-side-post" "css_element" should not exist in the "body" "css_element"
    And I follow "Turn editing off"
    And ".empty-region-side-post" "css_element" should exist in the "body" "css_element"
