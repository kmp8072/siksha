@javascript @tool @tool_totara_sync @totara @totara_job
Feature: Configure user source to import job assignment data in HR sync
  In order to test HR import of users with job assignments
  I must log in as an admin and configure the user source

  Background:
    Given I log in as "admin"
    And I navigate to "General settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File Access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source | CSV |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I should see "\"firstname\""
    And I should see "\"lastname\""
    And I should see "\"email\""
    And I should not see "\"jobassignmentidnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I press "Save changes"

  Scenario: Configure HR import source link jaidnumber off
    Given I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Link job assignments | 0 |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should not see "\"jobassignmentidnumber\""
    And I should not see "\"jobassignmentfullname\""
    And I should not see "\"jobassignmentstartdate\""
    And I should not see "\"jobassignmentenddate\""
    And I should not see "\"orgidnumber\""
    And I should not see "\"posidnumber\""
    And I should not see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should not see "\"appraiseridnumber\""
    When I set the following fields to these values:
      | Job assignment full name  | 1 |
      | Job assignment start date | 1 |
      | Job assignment end date   | 1 |
      | Organisation              | 1 |
      | Position                  | 1 |
      | Manager                   | 1 |
      | Appraiser                 | 1 |
    And I press "Save changes"
    Then I should not see "\"jobassignmentidnumber\""
    And I should see "\"jobassignmentfullname\""
    And I should see "\"jobassignmentstartdate\""
    And I should see "\"jobassignmentenddate\""
    And I should see "\"orgidnumber\""
    And I should see "\"posidnumber\""
    And I should see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should see "\"appraiseridnumber\""
    When I set the following fields to these values:
      | Job assignment ID number | 0 |
    And I press "Save changes"
    Then I should not see "\"jobassignmentidnumber\""
    And I should see "\"jobassignmentfullname\""
    And I should see "\"jobassignmentstartdate\""
    And I should see "\"jobassignmentenddate\""
    And I should see "\"orgidnumber\""
    And I should see "\"posidnumber\""
    And I should see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should see "\"appraiseridnumber\""
    And "#id_import_managerjobassignmentidnumber[type=checkbox][disabled=disabled]" "css_element" should not exist

  Scenario: Configure HR import source link jaidnumber on
    Given I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Link job assignments | 1 |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should not see "\"jobassignmentidnumber\""
    And I should not see "\"jobassignmentfullname\""
    And I should not see "\"jobassignmentstartdate\""
    And I should not see "\"jobassignmentenddate\""
    And I should not see "\"orgidnumber\""
    And I should not see "\"posidnumber\""
    And I should not see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should not see "\"appraiseridnumber\""
    And "#id_import_jobassignmentfullname[type=checkbox][disabled=disabled]" "css_element" should exist
    And "#id_import_jobassignmentstartdate[type=checkbox][disabled=disabled]" "css_element" should exist
    And "#id_import_jobassignmentenddate[type=checkbox][disabled=disabled]" "css_element" should exist
    And "#id_import_orgidnumber[type=checkbox][disabled=disabled]" "css_element" should exist
    And "#id_import_posidnumber[type=checkbox][disabled=disabled]" "css_element" should exist
    And "#id_import_manageridnumber[type=checkbox][disabled=disabled]" "css_element" should exist
    And "#id_import_managerjobassignmentidnumber[type=checkbox][disabled=disabled]" "css_element" should exist
    When I set the following fields to these values:
      | Job assignment ID number  | 1 |
      | Job assignment full name  | 1 |
      | Job assignment start date | 1 |
      | Job assignment end date   | 1 |
      | Organisation              | 1 |
      | Position                  | 1 |
      | Manager                   | 1 |
      | Appraiser                 | 1 |
    And I press "Save changes"
    Then I should see "\"jobassignmentidnumber\""
    And I should see "\"jobassignmentfullname\""
    And I should see "\"jobassignmentstartdate\""
    And I should see "\"jobassignmentenddate\""
    And I should see "\"orgidnumber\""
    And I should see "\"posidnumber\""
    And I should see "\"manageridnumber\""
    And I should see "\"managerjobassignmentidnumber\""
    And I should see "\"appraiseridnumber\""
    When I set the following fields to these values:
      | Manager | 0 |
    And I press "Save changes"
    Then I should not see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    When I set the following fields to these values:
      | Manager | 1 |
    And I press "Save changes"
    Then I should see "\"manageridnumber\""
    And I should see "\"managerjobassignmentidnumber\""
    When I set the following fields to these values:
      | Job assignment ID number | 0 |
    And I press "Save changes"
    Then I should not see "\"jobassignmentidnumber\""
    And I should not see "\"jobassignmentfullname\""
    And I should not see "\"jobassignmentstartdate\""
    And I should not see "\"jobassignmentenddate\""
    And I should not see "\"orgidnumber\""
    And I should not see "\"posidnumber\""
    And I should not see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should not see "\"appraiseridnumber\""
