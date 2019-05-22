@totara @totara_reportbuilder @javascript
Feature: Full visibility of user report source custom field values
    Depending on the visibility settings for a user profile custom field,
    its value will be shown or masked in a report.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | agent86  | Maxwell   | Smart    | agent86@example.com |
      | agent99  | Agent     | 99       | agent99@example.com |
      | kaos     | Kaos      | Inc      | kaos@example.com    |
      | chief    | The       | Chief    | chief@example.com   |

    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users > Accounts"
    And I set the following fields to these values:
      | datatype | checkbox |
    And I set the following fields to these values:
      | Short name                   | TestCheckbox        |
      | Name                         | TestCheckbox        |
      | Is this field required       | No                  |
      | Is this field locked         | No                  |
      | Should the data be unique    | No                  |
      | Who is this field visible to | Visible to everyone |
      | Checked by default           | No                  |
    And I press "Save changes"

    Given I set the following fields to these values:
      | datatype | date |
    And I set the following fields to these values:
      | Short name                   | TestDate            |
      | Name                         | TestDate            |
      | Is this field required       | No                  |
      | Is this field locked         | No                  |
      | Should the data be unique    | No                  |
      | Who is this field visible to | Visible to everyone |
    And I press "Save changes"

    Given I set the following fields to these values:
      | datatype | datetime |
    And I set the following fields to these values:
      | Short name                   | TestDT              |
      | Name                         | TestDT              |
      | Is this field required       | No                  |
      | Is this field locked         | No                  |
      | Should the data be unique    | No                  |
      | Who is this field visible to | Visible to everyone |
      | Start year                   | 2000                |
    And I press "Save changes"

    Given I set the following fields to these values:
      | datatype | menu |
    And I set the following fields to these values:
      | Short name                   | TestMenu            |
      | Name                         | TestMenu            |
      | Is this field required       | No                  |
      | Is this field locked         | No                  |
      | Should the data be unique    | No                  |
      | Who is this field visible to | Visible to everyone |
      | Default value                | CCC                 |
    And I set the field "Menu options (one per line)" to multiline
      """
      AAA
      BBB
      CCC
      """
    And I press "Save changes"

    Given I set the following fields to these values:
      | datatype | textarea |
    And I set the following fields to these values:
      | Short name                   | TestTextArea             |
      | Name                         | TestTextArea             |
      | Is this field required       | No                       |
      | Is this field locked         | No                       |
      | Should the data be unique    | No                       |
      | Who is this field visible to | Visible to everyone      |
      | Default value                | TestTextArea default value |
    And I press "Save changes"

    Given I set the following fields to these values:
      | datatype | text |
    And I set the following fields to these values:
      | Short name                   | TestTextField               |
      | Name                         | TestTextField               |
      | Is this field required       | No                          |
      | Is this field locked         | No                          |
      | Should the data be unique    | No                          |
      | Who is this field visible to | Visible to everyone         |
      | Default value                | TestTextField default value |
    And I press "Save changes"

    Given I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Maxwell Smart"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | TestCheckbox                       | 1                              |
      | profile_field_TestDate[enabled]    | Yes                            |
      | profile_field_TestDate[day]        | 16                             |
      | profile_field_TestDate[month]      | 10                             |
      | profile_field_TestDate[year]       | 2005                           |
      | profile_field_TestDT[enabled]      | Yes                            |
      | profile_field_TestDT[day]          | 10                             |
      | profile_field_TestDT[month]        | 10                             |
      | profile_field_TestDT[year]         | 2008                           |
      | TestMenu                           | AAA                            |
      | TestTextArea                       | agent86 textarea value         |
      | TestTextField                      | agent86 text value             |
    And I press "Update profile"

    Given I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Agent 99"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | TestCheckbox                       | 0                              |
      | profile_field_TestDate[enabled]    | Yes                            |
      | profile_field_TestDate[day]        | 16                             |
      | profile_field_TestDate[month]      | 10                             |
      | profile_field_TestDate[year]       | 2015                           |
      | profile_field_TestDT[enabled]      | Yes                            |
      | profile_field_TestDT[day]          | 10                             |
      | profile_field_TestDT[month]        | 10                             |
      | profile_field_TestDT[year]         | 2010                           |
      | TestMenu                           | BBB                            |
      | TestTextArea                       | agent99 textarea value         |
      | TestTextField                      | agent99 text value             |
    And I press "Update profile"

    Given I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Kaos Inc"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Description | An international organization of evil bent on world domination |
    And I press "Update profile"


  Scenario: rb_source_user_customfield000: view report with full custom field visibility as various users
    Given I navigate to "Manage reports" node in "Site administration > Reports > Report builder"
    And I set the field "Report Name" to "Full visibility user report"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I follow "Columns"
    And I add the "TestCheckbox" column to the report
    And I add the "TestDate" column to the report
    And I add the "TestDT" column to the report
    And I add the "TestMenu" column to the report
    And I add the "TestTextArea" column to the report
    And I add the "TestTextField" column to the report

    Given I click on "Access" "link" in the ".tabtree" "css_element"
    And I click on "All users can view this report" "radio"
    And I press "Save changes"

    When I navigate to my "Full visibility user report" report
    Then I should see "Yes" in the "user_custom_field_1" report column for "Maxwell Smart"
    Then I should see "16 Oct 2005" in the "user_custom_field_2" report column for "Maxwell Smart"
    Then I should see "10 Oct 2008 at 00:00" in the "user_custom_field_3" report column for "Maxwell Smart"
    Then I should see "AAA" in the "user_custom_field_4" report column for "Maxwell Smart"
    Then I should see "agent86 textarea value" in the "user_custom_field_5" report column for "Maxwell Smart"
    Then I should see "agent86 text value" in the "user_custom_field_6" report column for "Maxwell Smart"

    Then I should see "No" in the "user_custom_field_1" report column for "Agent 99"
    Then I should see "16 Oct 2015" in the "user_custom_field_2" report column for "Agent 99"
    Then I should see "10 Oct 2010 at 00:00" in the "user_custom_field_3" report column for "Agent 99"
    Then I should see "BBB" in the "user_custom_field_4" report column for "Agent 99"
    Then I should see "agent99 textarea value" in the "user_custom_field_5" report column for "Agent 99"
    Then I should see "agent99 text value" in the "user_custom_field_6" report column for "Agent 99"

    Then I should see "No" in the "user_custom_field_1" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_2" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_3" report column for "Kaos Inc"
    Then I should see "CCC" in the "user_custom_field_4" report column for "Kaos Inc"
    Then I should see "TestTextArea default value" in the "user_custom_field_5" report column for "Kaos Inc"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "Kaos Inc"

    Then I should see "No" in the "user_custom_field_1" report column for "The Chief"
    Then I should see "" in the "user_custom_field_2" report column for "The Chief"
    Then I should see "" in the "user_custom_field_3" report column for "The Chief"
    Then I should see "CCC" in the "user_custom_field_4" report column for "The Chief"
    Then I should see "" in the "user_custom_field_5" report column for "The Chief"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "The Chief"

    Given I log out
    And I log in as "agent86"

    When I navigate to my "Full visibility user report" report
    Then I should see "Yes" in the "user_custom_field_1" report column for "Maxwell Smart"
    Then I should see "16 Oct 2005" in the "user_custom_field_2" report column for "Maxwell Smart"
    Then I should see "10 Oct 2008 at 00:00" in the "user_custom_field_3" report column for "Maxwell Smart"
    Then I should see "AAA" in the "user_custom_field_4" report column for "Maxwell Smart"
    Then I should see "agent86 textarea value" in the "user_custom_field_5" report column for "Maxwell Smart"
    Then I should see "agent86 text value" in the "user_custom_field_6" report column for "Maxwell Smart"

    Then I should see "No" in the "user_custom_field_1" report column for "Agent 99"
    Then I should see "16 Oct 2015" in the "user_custom_field_2" report column for "Agent 99"
    Then I should see "10 Oct 2010 at 00:00" in the "user_custom_field_3" report column for "Agent 99"
    Then I should see "BBB" in the "user_custom_field_4" report column for "Agent 99"
    Then I should see "agent99 textarea value" in the "user_custom_field_5" report column for "Agent 99"
    Then I should see "agent99 text value" in the "user_custom_field_6" report column for "Agent 99"

    Then I should see "No" in the "user_custom_field_1" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_2" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_3" report column for "Kaos Inc"
    Then I should see "CCC" in the "user_custom_field_4" report column for "Kaos Inc"
    Then I should see "TestTextArea default value" in the "user_custom_field_5" report column for "Kaos Inc"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "Kaos Inc"

    Then I should see "No" in the "user_custom_field_1" report column for "The Chief"
    Then I should see "" in the "user_custom_field_2" report column for "The Chief"
    Then I should see "" in the "user_custom_field_3" report column for "The Chief"
    Then I should see "CCC" in the "user_custom_field_4" report column for "The Chief"
    Then I should see "" in the "user_custom_field_5" report column for "The Chief"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "The Chief"

    Given I log out
    And I log in as "agent99"

    When I navigate to my "Full visibility user report" report
    Then I should see "Yes" in the "user_custom_field_1" report column for "Maxwell Smart"
    Then I should see "16 Oct 2005" in the "user_custom_field_2" report column for "Maxwell Smart"
    Then I should see "10 Oct 2008 at 00:00" in the "user_custom_field_3" report column for "Maxwell Smart"
    Then I should see "AAA" in the "user_custom_field_4" report column for "Maxwell Smart"
    Then I should see "agent86 textarea value" in the "user_custom_field_5" report column for "Maxwell Smart"
    Then I should see "agent86 text value" in the "user_custom_field_6" report column for "Maxwell Smart"

    Then I should see "No" in the "user_custom_field_1" report column for "Agent 99"
    Then I should see "16 Oct 2015" in the "user_custom_field_2" report column for "Agent 99"
    Then I should see "10 Oct 2010 at 00:00" in the "user_custom_field_3" report column for "Agent 99"
    Then I should see "BBB" in the "user_custom_field_4" report column for "Agent 99"
    Then I should see "agent99 textarea value" in the "user_custom_field_5" report column for "Agent 99"
    Then I should see "agent99 text value" in the "user_custom_field_6" report column for "Agent 99"

    Then I should see "No" in the "user_custom_field_1" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_2" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_3" report column for "Kaos Inc"
    Then I should see "CCC" in the "user_custom_field_4" report column for "Kaos Inc"
    Then I should see "TestTextArea default value" in the "user_custom_field_5" report column for "Kaos Inc"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "Kaos Inc"

    Then I should see "No" in the "user_custom_field_1" report column for "The Chief"
    Then I should see "" in the "user_custom_field_2" report column for "The Chief"
    Then I should see "" in the "user_custom_field_3" report column for "The Chief"
    Then I should see "CCC" in the "user_custom_field_4" report column for "The Chief"
    Then I should see "" in the "user_custom_field_5" report column for "The Chief"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "The Chief"

    Given I log out
    And I log in as "kaos"

    When I navigate to my "Full visibility user report" report
    Then I should see "Yes" in the "user_custom_field_1" report column for "Maxwell Smart"
    Then I should see "16 Oct 2005" in the "user_custom_field_2" report column for "Maxwell Smart"
    Then I should see "10 Oct 2008 at 00:00" in the "user_custom_field_3" report column for "Maxwell Smart"
    Then I should see "AAA" in the "user_custom_field_4" report column for "Maxwell Smart"
    Then I should see "agent86 textarea value" in the "user_custom_field_5" report column for "Maxwell Smart"
    Then I should see "agent86 text value" in the "user_custom_field_6" report column for "Maxwell Smart"

    Then I should see "No" in the "user_custom_field_1" report column for "Agent 99"
    Then I should see "16 Oct 2015" in the "user_custom_field_2" report column for "Agent 99"
    Then I should see "10 Oct 2010 at 00:00" in the "user_custom_field_3" report column for "Agent 99"
    Then I should see "BBB" in the "user_custom_field_4" report column for "Agent 99"
    Then I should see "agent99 textarea value" in the "user_custom_field_5" report column for "Agent 99"
    Then I should see "agent99 text value" in the "user_custom_field_6" report column for "Agent 99"

    Then I should see "No" in the "user_custom_field_1" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_2" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_3" report column for "Kaos Inc"
    Then I should see "CCC" in the "user_custom_field_4" report column for "Kaos Inc"
    Then I should see "TestTextArea default value" in the "user_custom_field_5" report column for "Kaos Inc"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "Kaos Inc"

    Then I should see "No" in the "user_custom_field_1" report column for "The Chief"
    Then I should see "" in the "user_custom_field_2" report column for "The Chief"
    Then I should see "" in the "user_custom_field_3" report column for "The Chief"
    Then I should see "CCC" in the "user_custom_field_4" report column for "The Chief"
    Then I should see "" in the "user_custom_field_5" report column for "The Chief"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "The Chief"

    Given I log out
    And I log in as "chief"

    When I navigate to my "Full visibility user report" report
    Then I should see "Yes" in the "user_custom_field_1" report column for "Maxwell Smart"
    Then I should see "16 Oct 2005" in the "user_custom_field_2" report column for "Maxwell Smart"
    Then I should see "10 Oct 2008 at 00:00" in the "user_custom_field_3" report column for "Maxwell Smart"
    Then I should see "AAA" in the "user_custom_field_4" report column for "Maxwell Smart"
    Then I should see "agent86 textarea value" in the "user_custom_field_5" report column for "Maxwell Smart"
    Then I should see "agent86 text value" in the "user_custom_field_6" report column for "Maxwell Smart"

    Then I should see "No" in the "user_custom_field_1" report column for "Agent 99"
    Then I should see "16 Oct 2015" in the "user_custom_field_2" report column for "Agent 99"
    Then I should see "10 Oct 2010 at 00:00" in the "user_custom_field_3" report column for "Agent 99"
    Then I should see "BBB" in the "user_custom_field_4" report column for "Agent 99"
    Then I should see "agent99 textarea value" in the "user_custom_field_5" report column for "Agent 99"
    Then I should see "agent99 text value" in the "user_custom_field_6" report column for "Agent 99"

    Then I should see "No" in the "user_custom_field_1" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_2" report column for "Kaos Inc"
    Then I should see "" in the "user_custom_field_3" report column for "Kaos Inc"
    Then I should see "CCC" in the "user_custom_field_4" report column for "Kaos Inc"
    Then I should see "TestTextArea default value" in the "user_custom_field_5" report column for "Kaos Inc"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "Kaos Inc"

    Then I should see "No" in the "user_custom_field_1" report column for "The Chief"
    Then I should see "" in the "user_custom_field_2" report column for "The Chief"
    Then I should see "" in the "user_custom_field_3" report column for "The Chief"
    Then I should see "CCC" in the "user_custom_field_4" report column for "The Chief"
    Then I should see "" in the "user_custom_field_5" report column for "The Chief"
    Then I should see "TestTextField default value" in the "user_custom_field_6" report column for "The Chief"
