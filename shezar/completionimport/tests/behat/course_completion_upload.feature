@totara @totara_completion_upload @javascript @_file_upload
Feature: Verify course completion data can be successfully uploaded.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Bob1       | Learner1  | learner1@example.com |

    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | C1        | 1        |

  Scenario: Verify an empty course completion upload fails.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "No records were imported"

  Scenario: Verify an course completion with no username fails.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1a.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "1 Records with data errors - these were ignored"
    And I should see "1 Records in total"
    And I follow "Course import report"
    Then I should see "Blank user name" in the "1" "table_row"

  Scenario: Verify a successful course completion with no courseshortname.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1b.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "1 Records successfully imported as courses"
    And I should see "1 Records in total"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Complete via rpl" in the "Course 1" "table_row"

  Scenario: Verify a successful course completion with no courseidnumber.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1c.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "1 Records successfully imported as courses"
    And I should see "1 Records in total"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Complete via rpl" in the "Course 1" "table_row"

  Scenario: Verify an course completion with no completiondate fails.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1d.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "1 Records with data errors - these were ignored"
    And I should see "1 Records in total"
    And I follow "Course import report"
    Then I should see "Blank completion date" in the "1" "table_row"

  Scenario: Verify an course completion with no grade fails.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1e.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "1 Records with data errors - these were ignored"
    And I should see "1 Records in total"
    And I follow "Course import report"
    Then I should see "Blank grade" in the "1" "table_row"


  Scenario: Verify a successful course completion upload.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_2.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "1 Records successfully imported as courses"
    And I should see "1 Records created as evidence"
    And I should see "2 Records in total"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Complete via rpl" in the "Course 1" "table_row"

    When I follow "Other Evidence"
    And I click on "Completed course : thisisevidence" "link" in the "tbody" "css_element"
    Then I should see "Completed course : thisisevidence"
    And I should see "Course ID number : notacourse"
    And I should see "Grade : 100"
    And I should see "Date completed : 1 January 2015"

  Scenario: Course completions can be successfully uploaded with a file that uses CR for line endings
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_CR_line_endings.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "Course data imported successfully"
    And I should see "1 Records successfully imported as courses"
    And I should see "1 Records created as evidence"
    And I should see "2 Records in total"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Complete via rpl" in the "Course 1" "table_row"

    When I follow "Other Evidence"
    And I click on "Completed course : thisisevidence" "link" in the "tbody" "css_element"
    Then I should see "Completed course : thisisevidence"
    And I should see "Course ID number : notacourse"
    And I should see "Grade : 100"
    And I should see "Date completed : 1 January 2015"