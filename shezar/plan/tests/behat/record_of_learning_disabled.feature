@totara @totara_plan @totara_rol
Feature: Check Record of Learning feature visibility
  In order to control access to RoL
  As an admin
  I need to be able to enable and disable it

  Scenario: Verify Record of Learning appears in the Totara menu and reportbuilder if enabled
    Given I am on a totara site
    And I log in as "admin"

    When I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should see "Record of Learning" in the "#totaramenutable" "css_element"
    And I should see "Record of Learning" in the totara menu

    When I navigate to "Manage reports" node in "Site administration > Reports > Report builder"
    Then I should see "Record of Learning: Certifications" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Competencies" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Courses" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Evidence" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Objectives" in the ".generaltable" "css_element"
    And I should see "My Current Courses" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Previous Certifications" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Previous Course Completions" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Programs" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Recurring Programs" in the ".generaltable" "css_element"
    And I should see "Record of Learning: Programs Completion History " in the ".generaltable" "css_element"
    And the "Source" select box should contain "Record of Learning: Certifications"
    And the "Source" select box should contain "Record of Learning: Competencies"
    And the "Source" select box should contain "Record of Learning: Courses"
    And the "Source" select box should contain "Record of Learning: Evidence"
    And the "Source" select box should contain "Record of Learning: Objectives"
    And the "Source" select box should contain "Record of Learning: Previous Certifications"
    And the "Source" select box should contain "Record of Learning: Previous Course Completions"
    And the "Source" select box should contain "Record of Learning: Programs"
    And the "Source" select box should contain "Record of Learning: Recurring Programs"

  Scenario: Verify Record of Learning does not appear in the Totara menu and reportbuilder if disabled
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Record of Learning" to "Disable"
    And I press "Save changes"

    When I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should not see "Record of Learning" in the "#totaramenutable" "css_element"
    And I should not see "Record of Learning" in the totara menu

    When I navigate to "Manage reports" node in "Site administration > Reports > Report builder"
    Then I should not see "Record of Learning: Certifications" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Competencies" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Courses" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Evidence" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Objectives" in the ".generaltable" "css_element"
    And I should not see "My Current Courses" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Previous Certifications" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Previous Course Completions" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Programs" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Recurring Programs" in the ".generaltable" "css_element"
    And I should not see "Record of Learning: Programs Completion History " in the ".generaltable" "css_element"
    And the "Source" select box should not contain "Record of Learning: Certifications"
    And the "Source" select box should not contain "Record of Learning: Competencies"
    And the "Source" select box should not contain "Record of Learning: Courses"
    And the "Source" select box should not contain "Record of Learning: Evidence"
    And the "Source" select box should not contain "Record of Learning: Objectives"
    And the "Source" select box should not contain "Record of Learning: Previous Certifications"
    And the "Source" select box should not contain "Record of Learning: Previous Course Completions"
    And the "Source" select box should not contain "Record of Learning: Programs"
    And the "Source" select box should not contain "Record of Learning: Recurring Programs"
