@core @core_user @javascript
Feature: Create users with custom profile fields
  In order to use custom profile fields
  As an admin
  I need to be able to create multiple users without providing a value for the custom fields

  Scenario: Can create multiple users without specifying value for unique required custom field
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users > Accounts"
    And I set the following fields to these values:
      | datatype | text |
    #redirect
    And I set the following fields to these values:
      | Short name                      | requiredfield     |
      | Name                            | Required Field    |
      | Is this field required          | Yes               |
      | Is this field locked            | Yes               |
      | Should the data be unique       | Yes               |
      | Who is this field visible to    | Not visible       |
    And I press "Save changes"
    When I navigate to "Add a new user" node in "Site administration > Users > Accounts"
    And I set the following fields to these values:
      | Username                        | user1            |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | a1@b.com          |
    And I press "Create user"
    Then the following should exist in the "users" table:
    | First name / Surname | Email address |
    | User One  | a1@b.com |
    When I navigate to "Add a new user" node in "Site administration > Users > Accounts"
    And I set the following fields to these values:
      | Username                        | user2            |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | Two               |
      | Email address                   | a2@b.com          |
    And I press "Create user"
    Then the following should exist in the "users" table:
    | First name / Surname | Email address |
    | User One  | a1@b.com |
    | User Two  | a2@b.com |
