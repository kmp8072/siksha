@_file_upload @javascript @tool @tool_totara_sync @totara @totara_job
Feature: Use user source to import job assignments data in HR sync
  In order to test HR import of users with job assignments
  I must log in as an admin and import from a CSV file

  Background:
    # Site data.
    Given I log in as "admin"
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Organisation FW | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname      | idnumber |
      | OFW001        | Organisation1 | org1     |
      | OFWX          | OrganisationX | orgx     |
      | OFWY          | OrganisationY | orgy     |
      | OFWZ          | OrganisationZ | orgz     |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname  | idnumber |
      | PFW001        | Position1 | pos1     |
      | PFWX          | PositionX | posx     |
      | PFWY          | PositionY | posy     |
      | PFWZ          | PositionZ | posz     |

    # Pre-create some user and job assignment data, to test update (and not-update).
    And the following "users" exist:
      | username   | idnumber   | firstname | lastname | email                  | totarasync |
      | managerx   | managerx   | manx      | manx     | managerx@example.com   | 0          |
      | managery   | managery   | many      | many     | managery@example.com   | 0          |
      | managerz   | managerz   | manz      | manz     | managerz@example.com   | 0          |
      | manager3   | manager3   | man3      | man3     | manager3@example.com   | 0          |
      | manager4   | manager4   | man4      | man4     | manager4@example.com   | 0          |
      | manager5   | manager5   | man5      | man5     | manager5@example.com   | 1          |
      | appraiserx | appraiserx | appx      | appx     | appraiserx@example.com | 0          |
      | appraisery | appraisery | appy      | appy     | appraisery@example.com | 0          |
      | appraiserz | appraiserz | appz      | appz     | appraiserz@example.com | 0          |
      | username11 | id11       | first11   | last11   | e11@example.com        | 1          |
      | username13 | id13       | first13   | last13   | e13@example.com        | 1          |
      | username14 | id14       | first14   | last14   | e14@example.com        | 1          |
      | username16 | id16       | first16   | last16   | e16@example.com        | 1          |
      | username17 | id17       | first17   | last17   | e17@example.com        | 1          |
      | username18 | id18       | first18   | last18   | e18@example.com        | 1          |
      | username19 | id19       | first19   | last19   | e19@example.com        | 1          |
      | username20 | id20       | first20   | last20   | e20@example.com        | 1          |
      | username27 | id27       | first27   | last27   | e27@example.com        | 1          |
    And the following job assignments exist:
      | user       | idnumber      | fullname | shortname | startdate  | enddate    | organisation | position | manager  | managerjaidnumber | appraiser  |
      | managerx   | managerjaidx  | fullx    |           |            |            |              |          |          |                   |            |
      | managery   | managerjaidy  | fully    |           |            |            |              |          |          |                   |            |
      | managerz   | managerjaidz  | fullz    |           |            |            |              |          |          |                   |            |
      | manager3   | manager3jaid1 | full3    |           |            |            |              |          |          |                   |            |
      | manager5   | manager5jaid1 | full5a   |           |            |            |              |          |          |                   |            |
      | manager5   | manager5jaid2 | full5b   |           |            |            |              |          |          |                   |            |
      | username11 | jaidx         | fullx    | short11   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username13 | jaidx         | fullx    | short13   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username14 | matchingjaid  | fullx    | short14   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username16 | jaidx         | fullx    | short16   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username16 | jaidy         | fully    | short16   | 1426820400 | 1434772800 | orgy         | posy     | managery | managerjaidy      | appraisery |
      | username17 | jaidx         | fullx    | short17   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username18 | matchingjaid  | fullx    | short18   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username19 | jaidx         | fullx    | short19   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username20 | matchingjaid  | fullx    | short20   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username27 | jaidx         | fullx    | short27   | 1426820400 | 1434772800 | orgx         | posx     | managerx | managerjaidx      | appraiserx |
      | username27 | jaidy         | fully    | short27   | 1426820400 | 1434772800 | orgy         | posy     | managery | managerjaidy      | appraisery |
      | username27 | jaidz         | fullz    | short27   | 1426820400 | 1434772800 | orgz         | posz     | managerz | managerjaidz      | appraiserz |

    # User source setup.
    When I navigate to "General settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File Access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source | CSV |
    And I press "Save changes"

    # Enable all job assignment fields.
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I set the following fields to these values:
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
    And I should see "\"appraiseridnumber\""

  Scenario: Upload CSV, link to first job assignment, multiple jobs disabled / not allowed, empty string ignored / don't erase existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 0 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings are ignored          |
      | Link job assignments   | to the user's first job assignment |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should not see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_without_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # User 26 => failed to import due to duplicates.
    And I should see "Duplicate users with idnumber id26. Skipped user id26"
    And I should see "Duplicate users with username username26. Skipped user id26"
    And I should see "Duplicate users with email e26@example.com. Skipped user id26"

    # User 27 => failed because it tried to update the first job assignment record to same id as the second.
    And I should see "Cannot create job assignment (user: id27): Tried to update job assignment to an idnumber which is not unique for this user"

    # User 10 - no import, no existing => no job assignments.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => don't change existing.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => update existing first ja (jaid only).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short13 |
      | ID Number          | newjaid |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 14 - import ja, existing matching jaid => update existing first ja (jaid only).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short14      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 15 - import without jaid, no existing => create ja with default jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should not see "Add job assignment"
    And I click on "no jaid no existing" "link"
    Then the following fields match these values:
      | Full name          | no jaid no existing |
      | Short name         |                     |
      | ID Number          | 1                   |
      | startdate[enabled] | 1                   |
      | startdate[year]    | 2016                |
      | startdate[month]   | April               |
      | startdate[day]     | 15                  |
      | enddate[enabled]   | 1                   |
      | enddate[year]      | 2016                |
      | enddate[month]     | May                 |
      | enddate[day]       | 15                  |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"

    # User 16 - import without jaid, with existing => update first ja (except jaid).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should not see "Add job assignment"
    And I click on "no jaid with existing" "link"
    Then the following fields match these values:
      | Full name          | no jaid with existing |
      | Short name         | short16               |
      | ID Number          | jaidx                 |
      | startdate[enabled] | 1                     |
      | startdate[year]    | 2016                  |
      | startdate[month]   | April                 |
      | startdate[day]     | 15                    |
      | enddate[enabled]   | 1                     |
      | enddate[year]      | 2016                  |
      | enddate[month]     | May                   |
      | enddate[day]       | 15                    |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully        |
      | Short name         | short16      |
      | ID Number          | jaidy        |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should not see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data |
      | Short name         | short17   |
      | ID Number          | newjaid   |
      | startdate[enabled] | 1         |
      | startdate[year]    | 2016      |
      | startdate[month]   | April     |
      | startdate[day]     | 15        |
      | enddate[enabled]   | 1         |
      | enddate[year]      | 2016      |
      | enddate[month]     | May       |
      | enddate[day]       | 15        |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"

    # User 18 - import matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should not see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short18      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 21 - manager id only manager has no assignment => create default manager job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should see "manager1 manager1 (manager1@example.com) - Unnamed job assignment (ID: 1)"

    # User 22 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should see "manager2 manager2 (manager2@example.com) - full2"

    # User 23 - manager id only manager has no assignment => create default manager job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: 1)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: 1) |
      | Short name         |                                |
      | ID Number          | 1                              |
      | startdate[enabled] | 0                              |
      | enddate[enabled]   | 0                              |
    And I should see "manager1 manager1 (manager1@example.com) - Unnamed job assignment (ID: 1)"

    # User 24 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: 1)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: 1) |
      | Short name         |                                |
      | ID Number          | 1                              |
      | startdate[enabled] | 0                              |
      | enddate[enabled]   | 0                              |
    And I should see "manager2 manager2 (manager2@example.com) - full2"

    # User 27 - jaid matches second ja => failed because it tried to update the first jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short27 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"

  Scenario: Upload CSV, link to first job assignment, multiple jobs disabled / not allowed, empty string erases existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 0 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings erase existing data  |
      | Link job assignments   | to the user's first job assignment |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should not see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_without_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # User 10 and 11 => fail due to empty job assignment id number.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id10"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id11"

    # User 15 and 16 => fail because of missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id15"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id16"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # User 26 => failed to import due to duplicates.
    And I should see "Duplicate users with idnumber id26. Skipped user id26"
    And I should see "Duplicate users with username username26. Skipped user id26"
    And I should see "Duplicate users with email e26@example.com. Skipped user id26"

    # User 23 and 24 => fail because of missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id23"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id24"

    # User 27 => failed because it tried to update the first job assignment record to same id as the second.
    And I should see "Cannot create job assignment (user: id27): Tried to update job assignment to an idnumber which is not unique for this user"

    # User 10 - no import, no existing => fail to create job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => fail to update job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => update existing first ja (all fields).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: newjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: newjaid) |
      | Short name         | short13                              |
      | ID Number          | newjaid                              |
      | startdate[enabled] | 0                                    |
      | enddate[enabled]   | 0                                    |
    And I should not see "PositionX"
    And I should not see "OrganisationX"
    And I should not see "manx manx (managerx@example.com) - fullx"
    And I should not see "appx appx"

    # User 14 - import ja, existing matching jaid => update existing first ja (all fields).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: matchingjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: matchingjaid) |
      | Short name         | short14                                   |
      | ID Number          | matchingjaid                              |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "PositionX"
    And I should not see "OrganisationX"
    And I should not see "manx manx (managerx@example.com) - fullx"
    And I should not see "appx appx"

    # User 15 - import without jaid, no existing => fail because jaid cannot be empty.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 16 - import without jaid, with existing => fail because jaid cannot be empty.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short16 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short16 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should not see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data |
      | Short name         | short17   |
      | ID Number          | newjaid   |
      | startdate[enabled] | 1         |
      | startdate[year]    | 2016      |
      | startdate[month]   | April     |
      | startdate[day]     | 15        |
      | enddate[enabled]   | 1         |
      | enddate[year]      | 2016      |
      | enddate[month]     | May       |
      | enddate[day]       | 15        |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"

    # User 18 - import matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should not see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short18      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 21 - manager id only manager has no assignment => create default manager job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should see "manager1 manager1 (manager1@example.com) - Unnamed job assignment (ID: 1)"

    # User 22 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should see "manager2 manager2 (manager2@example.com) - full2"

    # User 23 - manager id only manager has no assignment => fail due to missing job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 24 - manager id only manager has existing assignment => fail due to missing job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 27 - jaid matches second ja => failed because it tried to update the first jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short27 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"

  Scenario: Upload CSV, link to matching ja idnumber, multiple jobs disabled / not allowed, empty string ignored / don't erase existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 0 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings are ignored                 |
      | Link job assignments   | using the user's job assignment ID number |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_with_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # Users 13 and 17 => can't create another ja.
    And I should see "Tried to create a job assignment but multiple job assignments site setting is disabled and a job assignment already exists. Skipped job assignment for user id13"
    And I should see "Tried to create a job assignment but multiple job assignments site setting is disabled and a job assignment already exists. Skipped job assignment for user id17"

    # Users 15 and 16 => missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id15"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id16"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # Users 21 and 22 => manager is missing jaid.
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id21"
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id22"

    # User 23 => manager's job assignment must exist or be in the import.
    And I should see "Manager's job assignment must already exist in database or be in the import. Skipped manager assignment for user id23"

    # User 26 => failed to import due to duplicates.
    And I should see "Duplicate users with idnumber id26. Skipped user id26"
    And I should see "Duplicate users with username username26. Skipped user id26"
    And I should see "Duplicate users with email e26@example.com. Skipped user id26"

    # User 10 - no import, no existing => no job assignments.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => don't change existing.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => fail can't create second ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short13 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 14 - import ja, existing matching jaid => update matching ja (but nothing changes because no data).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short14      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 15 - import without jaid, no existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 16 - import without jaid, with existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short16 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short16 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => fail can't create second ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short17 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 18 - import matching jaid => update matching ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should not see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short18      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 21 - manager id only manager has no assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "manager1 manager1 (manager1@example.com)"

    # User 22 - manager id only manager has existing assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should not see "manager2 manager2 (manager2@example.com)"

    # User 23 - manager with job assignment id manager new second => no manager - manager's job assignment must already exist.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should not see "Add job assignment"
    And I click on "manager with job assignment id manager new second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager new second |
      | Short name         |                                                   |
      | ID Number          | jaid                                              |
      | startdate[enabled] | 0                                                 |
      | enddate[enabled]   | 0                                                 |
    And I should not see "man4"

    # User 24 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should not see "Add job assignment"
    And I click on "manager with job assignment id manager match second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager match second |
      | Short name         |                                                     |
      | ID Number          | jaid                                                |
      | startdate[enabled] | 0                                                   |
      | enddate[enabled]   | 0                                                   |
    And I should see "man5 man5 (manager5@example.com) - full5b"

    # User 27 - jaid matches second ja => update matching ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "match second jaid" "link"
    Then the following fields match these values:
      | Full name          | match second jaid |
      | Short name         | short27           |
      | ID Number          | jaidy             |
      | startdate[enabled] | 1                 |
      | startdate[year]    | 2016              |
      | startdate[month]   | April             |
      | startdate[day]     | 15                |
      | enddate[enabled]   | 1                 |
      | enddate[year]      | 2016              |
      | enddate[month]     | May               |
      | enddate[day]       | 15                |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"

  Scenario: Upload CSV, link to matching ja idnumber, multiple jobs disabled / not allowed, empty string erases existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 0 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings erase existing data         |
      | Link job assignments   | using the user's job assignment ID number |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_with_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # User 10 and 11 => fail due to empty job assignment id number.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id10"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id11"

    # Users 13 and 17 => can't create another ja.
    And I should see "Tried to create a job assignment but multiple job assignments site setting is disabled and a job assignment already exists. Skipped job assignment for user id13"
    And I should see "Tried to create a job assignment but multiple job assignments site setting is disabled and a job assignment already exists. Skipped job assignment for user id17"

    # Users 15 and 16 => missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id15"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id16"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # Users 21 and 22 => manager is missing jaid.
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id21"
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id22"

    # User 23 => manager's job assignment must exist or be in the import.
    And I should see "Manager's job assignment must already exist in database or be in the import. Skipped manager assignment for user id23"

    # User 26 => failed to import due to duplicates.
    And I should see "Duplicate users with idnumber id26. Skipped user id26"
    And I should see "Duplicate users with username username26. Skipped user id26"
    And I should see "Duplicate users with email e26@example.com. Skipped user id26"

    # User 10 - no import, no existing => fail to create job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => fail to update job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => fail can't create second ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short13 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 14 - import ja, existing matching jaid => update matching ja (erase data).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should not see "Add job assignment"
    And I click on "Unnamed job assignment (ID: matchingjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: matchingjaid) |
      | Short name         | short14                                   |
      | ID Number          | matchingjaid                              |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "PositionX"
    And I should not see "OrganisationX"
    And I should not see "manx manx (managerx@example.com) - fullx"
    And I should not see "appx appx"

    # User 15 - import without jaid, no existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 16 - import without jaid, with existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short16 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short16 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => fail can't create second ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short17 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 18 - import matching jaid => update matching ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should not see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data         |
      | Short name         | short18           |
      | ID Number          | matchingjaid      |
      | startdate[enabled] | 1                 |
      | startdate[year]    | 2016              |
      | startdate[month]   | April             |
      | startdate[day]     | 15                |
      | enddate[enabled]   | 1                 |
      | enddate[year]      | 2016              |
      | enddate[month]     | May               |
      | enddate[day]       | 15                |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 21 - manager id only manager has no assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "manager1 manager1 (manager1@example.com)"

    # User 22 - manager id only manager has existing assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should not see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should not see "manager2 manager2 (manager2@example.com)"

    # User 23 - manager with job assignment id manager new second => no manager - manager's job assignment must already exist.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should not see "Add job assignment"
    And I click on "manager with job assignment id manager new second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager new second |
      | Short name         |                                                   |
      | ID Number          | jaid                                              |
      | startdate[enabled] | 0                                                 |
      | enddate[enabled]   | 0                                                 |
    And I should not see "man4"

    # User 24 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should not see "Add job assignment"
    And I click on "manager with job assignment id manager match second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager match second |
      | Short name         |                                                     |
      | ID Number          | jaid                                                |
      | startdate[enabled] | 0                                                   |
      | enddate[enabled]   | 0                                                   |
    And I should see "man5 man5 (manager5@example.com) - full5b"

    # User 27 - jaid matches second ja => update matching ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should not see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "match second jaid" "link"
    Then the following fields match these values:
      | Full name          | match second jaid |
      | Short name         | short27           |
      | ID Number          | jaidy             |
      | startdate[enabled] | 1                 |
      | startdate[year]    | 2016              |
      | startdate[month]   | April             |
      | startdate[day]     | 15                |
      | enddate[enabled]   | 1                 |
      | enddate[year]      | 2016              |
      | enddate[month]     | May               |
      | enddate[day]       | 15                |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"

  Scenario: Upload CSV, link to first job assignment, multiple jobs enabled, empty string ignored / don't erase existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings are ignored          |
      | Link job assignments   | to the user's first job assignment |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should not see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_without_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # User 26 => failed to import due to duplicates.
    And I should see "Duplicate users with idnumber id26. Skipped user id26"
    And I should see "Duplicate users with username username26. Skipped user id26"
    And I should see "Duplicate users with email e26@example.com. Skipped user id26"

    # User 27 => failed because it tried to update the first job assignment record to same id as the second.
    And I should see "Cannot create job assignment (user: id27): Tried to update job assignment to an idnumber which is not unique for this user"

    # User 10 - no import, no existing => no job assignments.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => don't change existing.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => update existing first ja (jaid only).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short13 |
      | ID Number          | newjaid |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 14 - import ja, existing matching jaid => update existing first ja (jaid only).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short14      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 15 - import without jaid, no existing => create ja with default jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should see "Add job assignment"
    And I click on "no jaid no existing" "link"
    Then the following fields match these values:
      | Full name          | no jaid no existing |
      | Short name         |                     |
      | ID Number          | 1                   |
      | startdate[enabled] | 1                   |
      | startdate[year]    | 2016                |
      | startdate[month]   | April               |
      | startdate[day]     | 15                  |
      | enddate[enabled]   | 1                   |
      | enddate[year]      | 2016                |
      | enddate[month]     | May                 |
      | enddate[day]       | 15                  |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"

    # User 16 - import without jaid, with existing => update first ja (except jaid).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should see "Add job assignment"
    And I click on "no jaid with existing" "link"
    Then the following fields match these values:
      | Full name          | no jaid with existing |
      | Short name         | short16               |
      | ID Number          | jaidx                 |
      | startdate[enabled] | 1                     |
      | startdate[year]    | 2016                  |
      | startdate[month]   | April                 |
      | startdate[day]     | 15                    |
      | enddate[enabled]   | 1                     |
      | enddate[year]      | 2016                  |
      | enddate[month]     | May                   |
      | enddate[day]       | 15                    |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short16 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data |
      | Short name         | short17   |
      | ID Number          | newjaid   |
      | startdate[enabled] | 1         |
      | startdate[year]    | 2016      |
      | startdate[month]   | April     |
      | startdate[day]     | 15        |
      | enddate[enabled]   | 1         |
      | enddate[year]      | 2016      |
      | enddate[month]     | May       |
      | enddate[day]       | 15        |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"

    # User 18 - import matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short18      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 21 - manager id only manager has no assignment => create default manager job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should see "manager1 manager1 (manager1@example.com) - Unnamed job assignment (ID: 1)"

    # User 22 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should see "manager2 manager2 (manager2@example.com) - full2"

    # User 23 - manager with job assignment id manager new first => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should see "manager2 manager2 (manager2@example.com) - full2"

    # User 23 - manager id only manager has no assignment => create default manager job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: 1)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: 1) |
      | Short name         |                                |
      | ID Number          | 1                              |
      | startdate[enabled] | 0                              |
      | enddate[enabled]   | 0                              |
    And I should see "manager1 manager1 (manager1@example.com) - Unnamed job assignment (ID: 1)"

    # User 24 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: 1)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: 1) |
      | Short name         |                                |
      | ID Number          | 1                              |
      | startdate[enabled] | 0                              |
      | enddate[enabled]   | 0                              |
    And I should see "manager2 manager2 (manager2@example.com) - full2"

    # User 27 - jaid matches second ja => failed because it tried to update the first jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short27 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"

  Scenario: Upload CSV, link to first job assignment, multiple jobs enabled, empty string erases existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings erase existing data  |
      | Link job assignments   | to the user's first job assignment |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should not see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_without_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # User 10 and 11 => fail due to empty job assignment id number.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id10"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id11"

    # User 15 and 16 => fail because of missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id15"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id16"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # User 23 and 24 => fail because of missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id23"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id24"

    # User 26 => failed to import due to duplicates.
    And I should see "Duplicate users with idnumber id26. Skipped user id26"
    And I should see "Duplicate users with username username26. Skipped user id26"
    And I should see "Duplicate users with email e26@example.com. Skipped user id26"

    # User 27 => failed because it tried to update the first job assignment record to same id as the second.
    And I should see "Cannot create job assignment (user: id27): Tried to update job assignment to an idnumber which is not unique for this user"

    # User 10 - no import, no existing => fail to create job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => fail to create job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => update existing first ja (all fields).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: newjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: newjaid) |
      | Short name         | short13                              |
      | ID Number          | newjaid                              |
      | startdate[enabled] | 0                                    |
      | enddate[enabled]   | 0                                    |
    And I should not see "PositionX"
    And I should not see "OrganisationX"
    And I should not see "manx manx (managerx@example.com) - fullx"
    And I should not see "appx appx"

    # User 14 - import ja, existing matching jaid => update existing first ja (all fields).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: matchingjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: matchingjaid) |
      | Short name         | short14                                   |
      | ID Number          | matchingjaid                              |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "PositionX"
    And I should not see "OrganisationX"
    And I should not see "manx manx (managerx@example.com) - fullx"
    And I should not see "appx appx"

    # User 15 - import without jaid, no existing => fail because jaid cannot be empty.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 16 - import without jaid, with existing => fail because jaid cannot be empty.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short16 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short16 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short17      |
      | ID Number          | newjaid      |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"

    # User 18 - import matching jaid => update first ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short18      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 21 - manager id only manager has no assignment => create default manager job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should see "manager1 manager1 (manager1@example.com) - Unnamed job assignment (ID: 1)"

    # User 22 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should see "manager2 manager2 (manager2@example.com) - full2"

    # User 23 - manager id only manager has no assignment => fail due to missing job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 24 - manager id only manager has existing assignment => fail due to missing job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 27 - jaid matches second ja => failed because it tried to update the first jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short27 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"

  Scenario: Upload CSV, link to matching ja idnumber, multiple jobs enabled, empty string ignored / don't erase existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings are ignored                 |
      | Link job assignments   | using the user's job assignment ID number |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_with_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # User 15 and 16 => fail because of missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id15"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id16"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # Users 21 and 22 => manager is missing jaid.
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id21"
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id22"

    # User 23 => manager's job assignment must exist or be in the import.
    And I should see "Manager's job assignment must already exist in database or be in the import. Skipped manager assignment for user id23"

    # User 10 - no import, no existing => no job assignments.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => don't change existing.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: newjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: newjaid) |
      | Short name         |                                      |
      | ID Number          | newjaid                              |
      | startdate[enabled] | 0                                    |
      | enddate[enabled]   | 0                                    |
    And I click on "first13 last13" "link"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short13 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 14 - import ja, existing matching jaid => update matching ja (jaid only).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short14      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 15 - import without jaid, no existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 16 - import without jaid, with existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short16 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short16 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data |
      | Short name         |           |
      | ID Number          | newjaid   |
      | startdate[enabled] | 1         |
      | startdate[year]    | 2016      |
      | startdate[month]   | April     |
      | startdate[day]     | 15        |
      | enddate[enabled]   | 1         |
      | enddate[year]      | 2016      |
      | enddate[month]     | May       |
      | enddate[day]       | 15        |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first17 last17" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short17 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 18 - import matching jaid => update matching ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short18      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 21 - manager id only manager has no assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "manager1 manager1 (manager1@example.com)"

    # User 22 - manager id only manager has existing assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should not see "manager2 manager2 (manager2@example.com)"

    # User 23 - manager with job assignment id manager new second => no manager - manager's job assignment must already exist.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should see "Add job assignment"
    And I click on "manager with job assignment id manager new second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager new second |
      | Short name         |                                                   |
      | ID Number          | jaid                                              |
      | startdate[enabled] | 0                                                 |
      | enddate[enabled]   | 0                                                 |
    And I should not see "man4"

    # User 24 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should see "Add job assignment"
    And I click on "manager with job assignment id manager match second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager match second |
      | Short name         |                                                     |
      | ID Number          | jaid                                                |
      | startdate[enabled] | 0                                                   |
      | enddate[enabled]   | 0                                                   |
    And I should see "man5 man5 (manager5@example.com) - full5b"

    # User 26 - two jas => both imported
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first26 last26" "link"
    Then I should see "Add job assignment"
    And I click on "multiple import #1 for one user" "link"
    Then the following fields match these values:
      | Full name          | multiple import #1 for one user |
      | Short name         |                                 |
      | ID Number          | jaid1                           |
      | startdate[enabled] | 1                               |
      | startdate[year]    | 2016                            |
      | startdate[month]   | April                           |
      | startdate[day]     | 15                              |
      | enddate[enabled]   | 1                               |
      | enddate[year]      | 2016                            |
      | enddate[month]     | May                             |
      | enddate[day]       | 15                              |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first26 last26" "link"
    And I click on "multiple import #2 for one user" "link"
    Then the following fields match these values:
      | Full name          | multiple import #2 for one user |
      | Short name         |                                 |
      | ID Number          | jaid2                           |
      | startdate[enabled] | 1                               |
      | startdate[year]    | 2016                            |
      | startdate[month]   | April                           |
      | startdate[day]     | 15                              |
      | enddate[enabled]   | 1                               |
      | enddate[year]      | 2016                            |
      | enddate[month]     | May                             |
      | enddate[day]       | 15                              |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 27 - jaid matches second ja => update matching ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "match second jaid" "link"
    Then the following fields match these values:
      | Full name          | match second jaid |
      | Short name         | short27           |
      | ID Number          | jaidy             |
      | startdate[enabled] | 1                 |
      | startdate[year]    | 2016              |
      | startdate[month]   | April             |
      | startdate[day]     | 15                |
      | enddate[enabled]   | 1                 |
      | enddate[year]      | 2016              |
      | enddate[month]     | May               |
      | enddate[day]       | 15                |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"

  Scenario: Upload CSV, link to matching ja idnumber, multiple jobs enabled, empty string erases existing data
    # Configure.
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings erase existing data         |
      | Link job assignments   | using the user's job assignment ID number |
    And I press "Save changes"
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "\"managerjobassignmentidnumber\""

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users_ja_with_managerjaid.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I should see "Running HR Import cron...Done! However, there have been some problems"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 1    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "HR Import finished" in the "#totarasynclog" "css_element"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    # User 10 and 11 => fail due to empty job assignment id number.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id10"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id11"

    # User 15 and 16 => fail because of missing jaid.
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id15"
    And I should see "Job assignment id number cannot be empty. Skipped job assignment for user id16"

    # User 19 and 20 => check that the data failed.
    And I should see "Position pos2 does not exist. Skipped user id19"
    And I should see "Position pos2 does not exist. Skipped user id20"
    And I should see "Organisation org2 does not exist. Skipped user id19"
    And I should see "Organisation org2 does not exist. Skipped user id20"
    And I should see "Manager managerxyz does not exist. Skipped user id19"
    And I should see "Manager managerxyz does not exist. Skipped user id20"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id19"
    And I should see "Appraiser appraiseridx does not exist. Skipped user id20"

    # Users 21 and 22 => manager is missing jaid.
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id21"
    And I should see "Manager job assignment idnumber is required when manager job assignment is provided. Skipped manager assignment for user id22"

    # User 23 => manager's job assignment must exist or be in the import.
    And I should see "Manager's job assignment must already exist in database or be in the import. Skipped manager assignment for user id23"

    # User 21 - manager id only manager has no assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first21 last21" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has no assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has no assignment |
      | Short name         |                                           |
      | ID Number          | jaid                                      |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "manager1 manager1 (manager1@example.com)"

    # User 22 - manager id only manager has existing assignment => job assignment is created but manager is not set.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first22 last22" "link"
    Then I should see "Add job assignment"
    And I click on "manager id only manager has existing assignment" "link"
    Then the following fields match these values:
      | Full name          | manager id only manager has existing assignment |
      | Short name         |                                                 |
      | ID Number          | jaid                                            |
      | startdate[enabled] | 0                                               |
      | enddate[enabled]   | 0                                               |
    And I should not see "manager2 manager2 (manager2@example.com)"

    # User 23 - manager with job assignment id manager new second => no manager - manager's job assignment must already exist.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first23 last23" "link"
    Then I should see "Add job assignment"
    And I click on "manager with job assignment id manager new second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager new second |
      | Short name         |                                                   |
      | ID Number          | jaid                                              |
      | startdate[enabled] | 0                                                 |
      | enddate[enabled]   | 0                                                 |
    And I should not see "man4"

    # User 24 - manager id only manager has existing assignment => link to manager's existing job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first24 last24" "link"
    Then I should see "Add job assignment"
    And I click on "manager with job assignment id manager match second" "link"
    Then the following fields match these values:
      | Full name          | manager with job assignment id manager match second |
      | Short name         |                                                     |
      | ID Number          | jaid                                                |
      | startdate[enabled] | 0                                                   |
      | enddate[enabled]   | 0                                                   |
    And I should see "man5 man5 (manager5@example.com) - full5b"

    # User 10 - no import, no existing => fail to create job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first10 last10" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 11 - no import, has existing => fail to update job assignment due to empty job assignment id number.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first11 last11" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short11 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 12 - import job assignment, no existing => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first12 last12" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: onlyjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: onlyjaid) |
      | Short name         |                                       |
      | ID Number          | onlyjaid                              |
      | startdate[enabled] | 0                                     |
      | enddate[enabled]   | 0                                     |

    # User 13 - import ja, existing ja with different jaid => create new ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first13 last13" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: newjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: newjaid) |
      | Short name         |                                      |
      | ID Number          | newjaid                              |
      | startdate[enabled] | 0                                    |
      | enddate[enabled]   | 0                                    |
    And I click on "first13 last13" "link"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short13 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 14 - import ja, existing matching jaid => update matching ja (erase other fields).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first14 last14" "link"
    Then I should see "Add job assignment"
    And I click on "Unnamed job assignment (ID: matchingjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: matchingjaid) |
      | Short name         | short14                                   |
      | ID Number          | matchingjaid                              |
      | startdate[enabled] | 0                                         |
      | enddate[enabled]   | 0                                         |
    And I should not see "PositionX"
    And I should not see "OrganisationX"
    And I should not see "manx manx (managerx@example.com) - fullx"
    And I should not see "appx appx"

    # User 15 - import without jaid, no existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first15 last15" "link"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 16 - import without jaid, with existing => fail needs jaid.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first16 last16" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short16 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first16 last16" "link"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         | short16 |
      | ID Number          | jaidy   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationY"
    And I should see "many many (managery@example.com) - fully"
    And I should see "appy appy"

    # User 17 - import non-matching jaid => create ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first17 last17" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data |
      | Short name         |           |
      | ID Number          | newjaid   |
      | startdate[enabled] | 1         |
      | startdate[year]    | 2016      |
      | startdate[month]   | April     |
      | startdate[day]     | 15        |
      | enddate[enabled]   | 1         |
      | enddate[year]      | 2016      |
      | enddate[month]     | May       |
      | enddate[day]       | 15        |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first17 last17" "link"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short17 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 18 - import matching jaid => update matching ja (whole record).
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first18 last18" "link"
    Then I should see "Add job assignment"
    And I click on "good data" "link"
    Then the following fields match these values:
      | Full name          | good data    |
      | Short name         | short18      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2016         |
      | startdate[month]   | April        |
      | startdate[day]     | 15           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2016         |
      | enddate[month]     | May          |
      | enddate[day]       | 15           |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "manager2 manager2 (manager2@example.com) - full2"
    And I should see "appraiser1 appraiser1"

    # User 19 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first19 last19" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short19 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 20 - bad data non-matching jaid => nothing imported.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first20 last20" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx        |
      | Short name         | short20      |
      | ID Number          | matchingjaid |
      | startdate[enabled] | 1            |
      | startdate[year]    | 2015         |
      | startdate[month]   | March        |
      | startdate[day]     | 20           |
      | enddate[enabled]   | 1            |
      | enddate[year]      | 2015         |
      | enddate[month]     | June         |
      | enddate[day]       | 20           |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 26 - two jas => both imported
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first26 last26" "link"
    Then I should see "Add job assignment"
    And I click on "multiple import #1 for one user" "link"
    Then the following fields match these values:
      | Full name          | multiple import #1 for one user |
      | Short name         |                                 |
      | ID Number          | jaid1                           |
      | startdate[enabled] | 1                               |
      | startdate[year]    | 2016                            |
      | startdate[month]   | April                           |
      | startdate[day]     | 15                              |
      | enddate[enabled]   | 1                               |
      | enddate[year]      | 2016                            |
      | enddate[month]     | May                             |
      | enddate[day]       | 15                              |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first26 last26" "link"
    And I click on "multiple import #2 for one user" "link"
    Then the following fields match these values:
      | Full name          | multiple import #2 for one user |
      | Short name         |                                 |
      | ID Number          | jaid2                           |
      | startdate[enabled] | 1                               |
      | startdate[year]    | 2016                            |
      | startdate[month]   | April                           |
      | startdate[day]     | 15                              |
      | enddate[enabled]   | 1                               |
      | enddate[year]      | 2016                            |
      | enddate[month]     | May                             |
      | enddate[day]       | 15                              |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"

    # User 27 - jaid matches second ja => update matching ja.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "first27 last27" "link"
    Then I should see "Add job assignment"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short27 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "manx manx (managerx@example.com) - fullx"
    And I should see "appx appx"
    And I click on "first27 last27" "link"
    And I click on "match second jaid" "link"
    Then the following fields match these values:
      | Full name          | match second jaid |
      | Short name         | short27           |
      | ID Number          | jaidy             |
      | startdate[enabled] | 1                 |
      | startdate[year]    | 2016              |
      | startdate[month]   | April             |
      | startdate[day]     | 15                |
      | enddate[enabled]   | 1                 |
      | enddate[year]      | 2016              |
      | enddate[month]     | May               |
      | enddate[day]       | 15                |
    And I should see "Position1"
    And I should see "Organisation1"
    And I should see "man3 man3 (manager3@example.com) - full3"
    And I should see "appraiser1 appraiser1"
    And I click on "first27 last27" "link"
    And I click on "fullz" "link"
    Then the following fields match these values:
      | Full name          | fullz   |
      | Short name         | short27 |
      | ID Number          | jaidz   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should see "manz manz (managerz@example.com) - fullz"
    And I should see "appz appz"
