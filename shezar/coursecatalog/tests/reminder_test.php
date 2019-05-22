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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlearning.com>
 * @package shezar_coursecatalog
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/reminderlib.php');


class shezar_coursecatalog_reminder_testcase extends advanced_testcase {
    /**
     * Test that reminder substituion works correctly for course catalog
     */
    public function test_reminder_email_substitutions() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Terry', 'lastname' => 'Craig']);
        $course = $this->getDataGenerator()->create_course();
        $message = (object)['period' => '5 days', 'deadline' => '2 weeks'];

        $place[] = 'First name: [firstname]';
        $place[] = 'Last name: [lastname]';
        $place[] = 'Course URL: [coursepageurl]';
        $place[] = 'Course name: [coursename]';
        $place[] = 'Manager name: [managername]';
        $place[] = 'Period: [days counter up]';
        $place[] = 'Dead line: [days count down]';
        $content = implode(", ", $place);

        $newcontent = reminder_email_substitutions($content, $user, $course, $message, null);
        $this->assertContains("First name: Terry", $newcontent);
        $this->assertContains("Last name: Craig", $newcontent);
        $this->assertContains("Course URL: http://www.example.com/moodle/course/view.php?id={$course->id}", $newcontent);
        $this->assertContains("Course name: Test course 1", $newcontent);
        $this->assertContains("Manager name: (no manager set)", $newcontent);
        $this->assertContains("Period: 5 days", $newcontent);
        $this->assertContains("Dead line: 2 weeks", $newcontent);
    }
}