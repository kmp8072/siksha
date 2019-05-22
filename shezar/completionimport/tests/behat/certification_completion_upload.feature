@totara @totara_completion_upload @javascript @_file_upload
Feature: Verify certification completion data can be successfully uploaded.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Bob1       | Learner1  | learner1@example.com |

    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | idnumber |
      | Certification 1 | Cert1     | 1        |

  Scenario: Verify a successful simple certification completion upload.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_1.csv" file to "Choose certification file to upload" filemanager
    And I set the field "Import action" to "Certify uncertified users"
    And I click on "Upload" "button" in the "#mform2" "css_element"
    Then I should see "CSV import completed"
    And I should see "Certification data imported successfully"
    And I should see "1 Records successfully imported as certifications"
    And I should see "1 Records created as evidence"
    And I should see "2 Records in total"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Certifications" tab
    Then I should see "Certified" in the "Certification 1" "table_row"

    When I follow "Other Evidence"
    And I follow "Completed certification : thisisevidence"
    Then I should see "Certification Short name : thisisevidence"
    And I should see "Certification ID number : notacertification"
    And I should see "Date completed : 1 January 2015"

