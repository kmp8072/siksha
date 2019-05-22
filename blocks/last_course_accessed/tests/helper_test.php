<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Tests for the Last Course Accessed block.
 *
 * @package block_last_course_accessed
 * @author Rob Tyler <rob.tyler@shezarlearning.com>
 */

defined('MOODLE_INTERNAL') || die();

class test_block_last_course_accessed extends advanced_testcase {

    private $compare_to;

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest();

        // Change the default timezone to London. We can then safely shift the timezone
        // within the EU without having issues as all countries clocks change simultaneously.
        $this->setTimezone('Europe/London', 'Europe/London');

        // Set a default time we can use to test we get the right output.
        $this->compare_to = strtotime('2016-05-17 15:45');
    }

    /**
     * Test the correct text is created for visiting a course within the last five minutes.
     */
    public function test_five_minutes() {

        $timestamp = $this->compare_to - (5 * 60);

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Within the last five minutes', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course within the last half-an-hour minutes.
     */
    public function test_half_hour() {

        $timestamp = $this->compare_to - (30 * 60);

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Within the last half-hour', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course within the last hour.
     */
    public function test_hour() {

        $timestamp = $this->compare_to - (60 * 60);

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Within the last hour', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course today and over the last hour ago.
     */
    public function test_today() {

        $timestamp = $this->compare_to - (61 * 60);

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Today at 02:44 PM', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course yesterday.
     */
    public function test_yesterday() {

        $timestamp = $this->compare_to - (60 * 60 * 24);

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Yesterday at 03:45 PM', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course within the last week but longer ago than yesterday.
     */
    public function test_day() {

        $timestamp = $this->compare_to - (60 * 60 * 24 * 7);

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Tuesday at 03:45 PM', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course longer than a week ago.
     */
    public function test_date() {

        $timestamp = $this->compare_to - (60 * 60) * 24 * 8;

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Monday, 09 May 2016 at 03:45 PM', $last_accessed);
    }

    /**
     * Check that when the timezone changes the system time is maintained and the helper method
     * displays a different time for different timezones.
     */
    public function test_timezone() {
        global $CFG, $DB, $USER;
        $this->setAdminUser();

        $yesterday_time = $this->compare_to - (60 * 60 * 24);

        // Create a last access record using the system timezone.
        $last_access = new stdClass();
        $last_access->userid = 2;
        $last_access->courseid = 2;
        // Shift the time by 24 hours so we can see the time in the text we want to check below.
        $last_access->timeaccess = $yesterday_time;
        $DB->insert_record('user_lastaccess', $last_access);

        // Check the record exists.
        $records = $DB->get_records('user_lastaccess');
        $this->assertCount(1, $records);
        // Use the record from the database.
        $last_access = reset($records);

        // Check the timestamp hasn't changed.
        $this->assertEquals($yesterday_time, $last_access->timeaccess);

        // Shift the timezone by an hour to check it updates the time when it's output.
        $USER->timezone = 'Europe/Paris';

        // Check the time has been created correctly. The time should be an hour ahead of our default time in $this->compare_to.
        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($last_access->timeaccess, $this->compare_to);
        $this->assertEquals('Yesterday at 04:45 PM', $last_accessed);

        // Check there's not been an update to the timestamp.
        $records = $DB->get_records('user_lastaccess');
        $this->assertCount(1, $records);
        // Use the record from the database.
        $last_access = reset($records);

        // Shift the timezone by a further hour to check it updates the time when it's output.
        $USER->timezone = 'Europe/Athens';

        // Check the time has been created correctly. The time should be an hour ahead of our default time in $this->compare_to.
        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($last_access->timeaccess, $this->compare_to);
        $this->assertEquals('Yesterday at 05:45 PM', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course within the last five minutes when the timezone changes.
     */
    public function test_timezone_five_minutes() {
        global $USER;

        $timestamp = $this->compare_to - (5 * 60);

        // A change in the timezone should not affect the output as it's relative to the system time.
        $USER->timezone = 'Europe/Berlin';

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Within the last five minutes', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course within the last half-an-hour minutes when the timezone changes.
     */
    public function test_timezone_half_hour() {
        global $USER;

        $timestamp = $this->compare_to - (30 * 60);

        // A change in the timezone should not affect the output as it's relative to the system time.
        $USER->timezone = 'Europe/Warsaw';

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Within the last half-hour', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course within the last hour when the timezone changes.
     */
    public function test_timezone_hour() {
        global $USER;

        $timestamp = $this->compare_to - (60 * 60);

        // A change in the timezone should not affect the output as it's relative to the system time.
        $USER->timezone = 'Europe/Stockholm';

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Within the last hour', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course today and over the last hour ago when the timezone changes.
     */
    public function test_timezone_today() {
        global $USER;

        $timestamp = $this->compare_to - (61 * 60);

        // A change in the timezone should not affect the output as it's relative to the system time.
        $USER->timezone = 'Europe/Dublin';

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Today at 02:44 PM', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course yesterday when the timezone changes.
     */
    public function test_timezone_yesterday() {
        global $USER;

        $timestamp = $this->compare_to - (60 * 60 * 24);

        // A change in the timezone should not affect the output as it's relative to the system time.
        $USER->timezone = 'Europe/Malta';

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Yesterday at 04:45 PM', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course within the last week but longer ago than yesterday when the timezone changes.
     */
    public function test_timezone_day() {
        global $USER;

        $timestamp = $this->compare_to - (60 * 60 * 24 * 7);

        // A change in the timezone should not affect the output as it's relative to the system time.
        $USER->timezone = 'Europe/Malta';

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Tuesday at 04:45 PM', $last_accessed);
    }

    /**
     * Test the correct text is created for visiting a course longer than a week ago when the timezone changes.
     */
    public function test_timezone_date() {
        global $USER;

        $timestamp = $this->compare_to - (60 * 60) * 24 * 8;

        // A change in the timezone should not affect the output as it's relative to the system time.
        $USER->timezone = 'Europe/Prague';

        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp, $this->compare_to);

        $this->assertEquals('Monday, 09 May 2016 at 04:45 PM', $last_accessed);
    }

}
