@_file_upload @javascript @tool @tool_totara_sync @totara @totara_job
Feature: Use user source to import empty job assignments data in HR sync
  In order to test HR import of users with job assignments with empty data
  I must log in as an admin and import from a CSV file
  # We only need to test when linking to first job assignment, because linking to id number requires an id number and is
  # tested in the main job_assignments feature.

  Scenario: Upload CSV with empty job assignment data
    # Site data.
    Given I log in as "admin"
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Organisation FW | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname      | idnumber |
      | OFWX          | OrganisationX | orgx     |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname  | idnumber |
      | PFWX          | PositionX | posx     |

    # Pre-create some user and job assignment data, to test update (and not-update).
    And the following "users" exist:
      | username   | idnumber   | firstname | lastname | email                  | totarasync |
      | managerx   | managerx   | manx      | manx     | managerx@example.com   | 0          |
      | appraiserx | appraiserx | appx      | appx     | appraiserx@example.com | 0          |
      | username11 | id11       | first11   | last11   | e11@example.com        | 1          |
    And the following job assignments exist:
      | user       | idnumber      | fullname | shortname | startdate  | enddate    | organisation | position | manager  | managerjaidnumber | appraiser  |
      | managerx   | managerjaidx  | fullx    |           |            |            |              |          |          |                   |            |
      | username11 | jaidx         | fullx    | short11   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |

    # User source setup.
    When I navigate to "General settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File Access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source                 | CSV                                |
      | Empty string behaviour | Empty strings erase existing data  |
      | Link job assignments   | to the user's first job assignment |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"

    # Enable all job assignment fields.
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I set the following fields to these values:
      | Job assignment full name  | 1 |
      | Job assignment start date | 1 |
      | Job assignment end date   | 1 |
      | Organisation              | 1 |
      | Position                  | 1 |
      | Manager                   | 1 |
      | Appraiser                 | 1 |
    And I press "Save changes"
    Then I should see "\"jobassignmentfullname\""
    And I should see "\"jobassignmentstartdate\""
    And I should see "\"jobassignmentenddate\""
    And I should see "\"orgidnumber\""
    And I should see "\"posidnumber\""
    And I should see "\"manageridnumber\""
    And I should see "\"appraiseridnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_without_jaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"

    # User 10 - empty string import, no existing => create default job assignment with no data.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    And I click on "Unnamed job assignment (ID: 1)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: 1) |
      | Short name         |                                |
      | ID Number          | 1                              |
      | startdate[enabled] | 0                              |
      | enddate[enabled]   | 0                              |

    # User 11 - empty string import, has existing => update all job assignment fields to empty.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    And I click on "Unnamed job assignment (ID: jaidx)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: jaidx) |
      | Short name         | short11                            |
      | ID Number          | jaidx                              |
      | startdate[enabled] | 0                                  |
      | enddate[enabled]   | 0                                  |
    And I should not see "PositionX"
    And I should not see "OrganisationX"
    And I should not see "manx manx (managerx@example.com) - fullx"
    And I should not see "appx appx"
