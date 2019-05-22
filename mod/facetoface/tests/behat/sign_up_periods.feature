@javascript @mod @mod_facetoface @totara
Feature: Seminar sign-up periods
  In order to verify seminar sign-up periods
  As a f2fadmin
  I need to set various dates

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student1 | Stu       | Dent     | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"

  Scenario Outline: Sign up students regardless of sign in period status
    Given I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | registrationtimestart[enabled]  | <periodopen>  |
      | registrationtimestart[day]      | 30            |
      | registrationtimestart[month]    | June          |
      | registrationtimestart[year]     | <startyear>   |
      | registrationtimestart[hour]     | 01            |
      | registrationtimestart[minute]   | 00            |
      | registrationtimestart[timezone] | <startzone>   |
      | registrationtimefinish[enabled] | <periodclose> |
      | registrationtimefinish[day]     | 30            |
      | registrationtimefinish[month]   | June          |
      | registrationtimefinish[year]    | <endyear>     |
      | registrationtimefinish[hour]    | 01            |
      | registrationtimefinish[minute]  | 00            |
      | registrationtimefinish[timezone]| <endzone>     |
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I set the field "f2f-actions" to "Add users"
    And I click on "student@example.com" "option"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    And I switch to "Wait-list" tab
    And I should see "Stu Dent"

  Examples:
    | periodopen | startyear | startzone        | periodclose | endyear | endzone          |
    | 1          | 2014      | Pacific/Auckland | 1           | 2015    | Pacific/Auckland |
    | 1          | 2014      | Pacific/Auckland | 1           | 2030    | Pacific/Auckland |
    | 1          | 2029      | Pacific/Auckland | 1           | 2030    | Pacific/Auckland |
    | 1          | 2029      | Pacific/Honolulu | 1           | 2030    | Pacific/Fiji     |
    | 0          | 2029      | Pacific/Auckland | 0           | 2030    | Pacific/Auckland |
    | 1          | 2029      | Pacific/Auckland | 0           | 2030    | Pacific/Auckland |
    | 0          | 2029      | Pacific/Auckland | 1           | 2030    | Pacific/Auckland |

  Scenario Outline: Test sign-up period validation
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | registrationtimestart[enabled]   | 1                  |
      | registrationtimestart[day]       | <periodstartday>   |
      | registrationtimestart[month]     | June               |
      | registrationtimestart[year]      | 2030               |
      | registrationtimestart[hour]      | <periodstarthour>  |
      | registrationtimestart[minute]    | 00                 |
      | registrationtimestart[timezone]  | <periodstartzone>  |
      | registrationtimefinish[enabled]  | 1                  |
      | registrationtimefinish[day]      | <periodendday>     |
      | registrationtimefinish[month]    | June               |
      | registrationtimefinish[year]     | 2030               |
      | registrationtimefinish[hour]     | <periodendhour>    |
      | registrationtimefinish[minute]   | 00                 |
      | registrationtimefinish[timezone] | <periodendzone>    |
    And I click on "Edit date" "link"
    And I set the following fields to these values:
      | timestart[day]       | <sessionstartday>  |
      | timestart[month]     | June               |
      | timestart[year]      | 2030               |
      | timestart[hour]      | <sessionstarthour> |
      | timestart[minute]    | 00                 |
      | timestart[timezone]  | <sessionstartzone> |
      | timefinish[day]      | <sessionendday>    |
      | timefinish[month]    | June               |
      | timefinish[year]     | 2030               |
      | timefinish[hour]     | <sessionendhour>   |
      | timefinish[minute]   | 00                 |
      | timefinish[timezone] | Pacific/Auckland   |
    And I press "OK"
    And I press "Save changes"
    Then I should see "<message>"

  Examples:
    | periodstartday | periodstarthour | periodstartzone  | periodendday | periodendhour | periodendzone    | sessionstartday | sessionstarthour | sessionstartzone | sessionendday | sessionendhour | message                                                             | description unused                       |
    | 1              | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Upcoming events                                                     | Normal case                              |
    | 16             | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Clear start sign-up > end sign-up        |
    | 15             | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Start sign-up = End Sign-up              |
    | 1              | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 10              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period closing time must be on or before session start time | session date inside sign-up range        |
    | 12             | 01              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 10              | 09               | Pacific/Auckland | 10            | 10             | Sign-up period opening time must be before session start time       | Clear session start before sign-up start |
    | 10             | 09              | Pacific/Auckland | 15           | 01            | Pacific/Auckland | 10              | 09               | Pacific/Auckland | 10            | 10             | Sign-up period opening time must be before session start time       | Sign-up start = session start            |
    | 1              | 01              | Pacific/Auckland | 20           | 09            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Upcoming events                                                     | End sign-up = session start              |
    # And now for some timezone fun
    | 15             | 01              | Europe/London    | 15           | 13            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Upcoming events                                                     | Normal case                              |
    | 15             | 02              | Europe/London    | 15           | 13            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Start sign-up = End Sign-up              |
    | 15             | 03              | Europe/London    | 15           | 13            | Pacific/Auckland | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Clear start sign-up > end sign-up        |
    | 15             | 01              | Europe/London    | 15           | 23            | Pacific/Auckland | 20              | 12               | Pacific/Auckland | 20            | 13             | Upcoming events                                                     | Normal case                              |
    | 15             | 02              | Europe/London    | 15           | 23            | Pacific/Auckland | 15              | 12               | Pacific/Auckland | 20            | 13             | Sign-up period opening time must be before session start time       | Start sign-up = start session            |
    | 15             | 03              | Europe/London    | 15           | 23            | Pacific/Auckland | 15              | 12               | Pacific/Auckland | 20            | 13             | Sign-up period opening time must be before session start time       | Start sign-up > start session            |
    | 15             | 13              | Pacific/Auckland | 15           | 01            | Europe/London    | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Normal case                              |
    | 15             | 14              | Pacific/Auckland | 15           | 01            | Europe/London    | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Start sign-up = End Sign-up              |
    | 15             | 15              | Pacific/Auckland | 15           | 01            | Europe/London    | 20              | 09               | Pacific/Auckland | 20            | 10             | Sign-up period start time must be before sign-up finish time        | Clear start sign-up > end sign-up        |
    | 15             | 11              | Pacific/Auckland | 15           | 12            | Pacific/Auckland | 15              | 01               | Europe/London    | 20            | 10             | Upcoming events                                                     | Normal case                              |
    | 15             | 12              | Pacific/Auckland | 20           | 01            | Pacific/Auckland | 15              | 01               | Europe/London    | 20            | 10             | Sign-up period opening time must be before session start time       | Sign-up start = session start            |
    | 15             | 13              | Pacific/Auckland | 20           | 01            | Pacific/Auckland | 15              | 01               | Europe/London    | 20            | 10             | Sign-up period opening time must be before session start time       | Sign-up start > session start            |

  Scenario Outline: Check the correct text is displayed in various states when there is a sign-up period
    Given I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | registrationtimestart[enabled]   | <periodopen>  |
      | registrationtimestart[day]       | 30            |
      | registrationtimestart[month]     | June          |
      | registrationtimestart[year]      | <startyear>   |
      | registrationtimestart[hour]      | 01            |
      | registrationtimestart[minute]    | 00            |
      | registrationtimestart[timezone]  | <startzone>   |
      | registrationtimefinish[enabled]  | <periodclose> |
      | registrationtimefinish[day]      | 30            |
      | registrationtimefinish[month]    | June          |
      | registrationtimefinish[year]     | <endyear>     |
      | registrationtimefinish[hour]     | 01            |
      | registrationtimefinish[minute]   | 00            |
      | registrationtimefinish[timezone] | <endzone>     |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    Then I should see "<signupavailable>"

    When I follow "View all events"
    Then I should see "<bookingstatus>"
    And I should see "<signupperiod>"

  Examples:
    | periodopen | startyear | startzone        | periodclose | endyear | endzone         | signupavailable     | bookingstatus                | signupperiod                                                                 |
    | 1          | 2014      | Australia/Perth  | 1           | 2015    | Australia/Perth | Sign-up unavailable | Sign-up period is now closed | 30 June 2014 1:00 AM Australia/Perth to 30 June 2015 1:00 AM Australia/Perth |
    | 1          | 2014      | Australia/Perth  | 1           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | 30 June 2014 1:00 AM Australia/Perth to 30 June 2030 1:00 AM Australia/Perth |
    | 1          | 2029      | Australia/Perth  | 1           | 2030    | Australia/Perth | Sign-up unavailable | Sign-up period not open      | 30 June 2029 1:00 AM Australia/Perth to 30 June 2030 1:00 AM Australia/Perth |
    | 1          | 2029      | Pacific/Honolulu | 1           | 2030    | Pacific/Fiji    | Sign-up unavailable | Sign-up period not open      | 30 June 2029 7:00 PM Australia/Perth to 29 June 2030 9:00 PM Australia/Perth |
    | 0          | 2029      | Australia/Perth  | 0           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | Booking open                                                                 |
    | 1          | 2029      | Australia/Perth  | 0           | 2030    | Australia/Perth | Sign-up unavailable | Sign-up period not open      | After 30 June 2029 1:00 AM Australia/Perth                                   |
    | 0          | 2029      | Australia/Perth  | 1           | 2030    | Australia/Perth | Join waitlist       | Booking open                 | Before 30 June 2030 1:00 AM Australia/Perth                                  |




