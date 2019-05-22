@core @core_webservice @javascript
Feature: Basic web service access
  In order to use webservices
  As a special web server user
  I need to configure and access each type of supported webservices

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | idnumber | username | firstname | lastname | email                |
      | u4  | student  | Sam1      | Student1 | student1@example.com |

  Scenario: Enable, configure and access web services
    Given I log in as "admin"

    # Enable services
    And I set the following administration settings values:
      | enablewebservices | 1 |
      | enablemobilewebservice | 1 |
    And I navigate to "Manage protocols" node in "Site administration > Plugins > Web services"
    And I "Enable" the "SOAP" web service protocol

    # Enable web services authentication
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Web services authentication" "table_row"

    # Configure web service
    And I navigate to "External services" node in "Site administration > Plugins > Web services"
    And I click on "Edit" "link" in the "Moodle mobile web service" "table_row"
    And I set the following fields to these values:
      | Enabled    | 1                   |
    And I press "Save changes"

    # Perform REST test
    When I navigate to "Web service test client" node in "Site administration > Development"
    And I set the following fields to these values:
      | Authentication method | simple                      |
      | Protocol              | REST protocol               |
      | Function              | core_user_get_users_by_field |
    And I press "Select"
    And I set the following fields to these values:
      | wsusername | admin           |
      | wspassword | admin           |
      | field      | idnumber        |
      | values[0]  | u4              |
    And I press "Execute"
    Then I should see "student1@example.com"
    And I should see "Sam1"

    # Perform SOAP test
    When I navigate to "Web service test client" node in "Site administration > Development"
    And I set the following fields to these values:
      | Authentication method | simple                      |
      | Protocol              | SOAP protocol               |
      | Function              | core_user_get_users_by_field |
    And I press "Select"
    And I set the following fields to these values:
      | wsusername | admin           |
      | wspassword | admin           |
      | field      | idnumber        |
      | values[0]  | u4              |
    And I press "Execute"
    Then I should see "student1@example.com"
    And I should see "Sam1"

    # Perform XML-RPC test
    When I navigate to "Web service test client" node in "Site administration > Development"
    And I set the following fields to these values:
      | Authentication method | simple                      |
      | Protocol              | XML-RPC protocol            |
      | Function              | core_user_get_users_by_field |
    And I press "Select"
    And I set the following fields to these values:
      | wsusername | admin           |
      | wspassword | admin           |
      | field      | idnumber        |
      | values[0]  | u4              |
    And I press "Execute"
    Then I should see "student1@example.com"
    And I should see "Sam1"
