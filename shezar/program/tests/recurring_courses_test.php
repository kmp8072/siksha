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
 * @author Brendan Cox <brendan.cox@shezarlms.com>
 * @package shezar_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Tests relating to the recurring courses feature within programs.
 */
class shezar_program_recurring_courses_testcase extends reportcache_advanced_testcase {

    /**
     * Adds a recurring course to a program.
     *
     * @param stdClass|program $program
     * @param stdClass $course
     */
    public function add_recurring_courseset($program, $course) {
        $recurringcourseset = new recurring_course_set($program->id);
        $recurringcourseset->course = $course;
        $recurringcourseset->save_set();
    }

    public function test_copy_recurring_courses_task() {
        $this->resetAfterTest(true);
        global $DB;

        $generator = $this->getDataGenerator();
        /** @var shezar_program_generator $programgenerator */
        $programgenerator = $generator->get_plugin_generator('shezar_program');

        $course = $generator->create_course();

        $program = $programgenerator->create_program();
        $this->add_recurring_courseset($program, $course);

        // The 2 courses at this stage will be the course created in this test and the 'site' course.
        $this->assertEquals(2, $DB->count_records('course'));
        $this->setAdminUser();

        ob_start();
        $task = new shezar_program\task\copy_recurring_courses_task();
        $task->execute();
        ob_end_clean();

        // The courses table should now include a record for the newly restored course as well as the previous courses.
        $this->assertEquals(3, $DB->count_records('course'));
    }
}