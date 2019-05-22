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
 * @author Simon Player <simon.player@shezarlearning.com>
 * @package core_course
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

class core_course_user_learning_item_testcase extends advanced_testcase {

    private $generator;
    private $completion_generator;
    private $course1, $course2, $course3, $course4, $course5, $course6;
    private $user1;

    public function setUp() {
        global $DB;

        $this->resetAfterTest(true);
        parent::setUp();

        $this->generator = $this->getDataGenerator();
        $this->completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');

        // Create some course.
        $this->course1 = $this->generator->create_course(array('shortname' => 'c1','fullname' => 'Course 1'));
        $this->course2 = $this->generator->create_course(array('shortname' => 'c2','fullname' => 'Course 2'));
        $this->course3 = $this->generator->create_course(array('shortname' => 'c3','fullname' => 'Course 3'));
        $this->course4 = $this->generator->create_course(array('shortname' => 'c4','fullname' => 'Course 4'));
        $this->course5 = $this->generator->create_course(array('shortname' => 'c5','fullname' => 'Course 5'));
        $this->course6 = $this->generator->create_course(array('shortname' => 'c6','fullname' => 'Course 6'));

        // Reload courses to get accurate data.
        // See note in shezar/program/tests/program_content_test.php for more info.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
        $this->course4 = $DB->get_record('course', array('id' => $this->course4->id));
        $this->course5 = $DB->get_record('course', array('id' => $this->course5->id));
        $this->course6 = $DB->get_record('course', array('id' => $this->course6->id));

        // Enable completion for courses.
        $this->completion_generator->enable_completion_tracking($this->course1);
        $this->completion_generator->enable_completion_tracking($this->course2);
        $this->completion_generator->enable_completion_tracking($this->course3);
        $this->completion_generator->enable_completion_tracking($this->course4);
        $this->completion_generator->enable_completion_tracking($this->course5);
        $this->completion_generator->enable_completion_tracking($this->course6);

        $this->user1 = $this->generator->create_user(array('fullname' => 'user1'));
    }

    public function test_all_courses() {
        // Enrolled user into three courses.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $this->generator->enrol_user($this->user1->id, $this->course2->id);
        $this->generator->enrol_user($this->user1->id, $this->course3->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(3, $learning_items);

        $results = array();
        foreach ($learning_items as $item) {
            $results[$item->shortname] = $item->fullname;
        }

        $this->assertEquals('Course 1', $results['c1']);
        $this->assertEquals('Course 2', $results['c2']);
        $this->assertEquals('Course 3', $results['c3']);
    }

    public function test_one_course() {
        // Enrolled user into three courses.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        // Ensure we get the correct course
        $this->assertEquals('Course 1', $learning_items->fullname);
    }

    public function test_all_course_future_enrol() {
        // Enrolled user with a future start date.
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual', time() + 604800);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(0, $learning_items);
    }

    public function test_all_course_past_enrol() {
        // Enrolled user where enrolment end date has past.
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual', time() - 864000, time() - 604800);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(0, $learning_items);
    }

    public function test_all_course_suspended_enrol() {
        // Enrolment suspended
        $this->generator->enrol_user($this->user1->id, $this->course1->id, null, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::all($this->user1->id);

        // Ensure we get the right number of courses.
        $this->assertCount(0, $learning_items);
    }

    public function test_ensure_completion_loaded() {
        global $CFG;

        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        $progress_canbecompleted = new ReflectionProperty('core_course\user_learning\item', 'progress_canbecompleted');
        $progress_canbecompleted->setAccessible(true);

        $progress_percentage = new ReflectionProperty('core_course\user_learning\item', 'progress_percentage');
        $progress_percentage->setAccessible(true);

        $progress_summary = new ReflectionProperty('core_course\user_learning\item', 'progress_summary');
        $progress_summary->setAccessible(true);

        // Check they are all empty.
        $this->assertEmpty($progress_canbecompleted->getValue($learning_items));
        $this->assertEmpty($progress_percentage->getValue($learning_items));
        $this->assertEmpty($progress_summary->getValue($learning_items));

        // Lets turn on completion and try again.
        $CFG->enablecompletion = true;

        $rm = new ReflectionMethod('core_course\user_learning\item', 'ensure_completion_loaded');
        $rm->setAccessible(true);

        $rm->invoke($learning_items);

        // We should have some values this time (even if there is no progress).
        $this->assertTrue($progress_canbecompleted->getValue($learning_items));
        $this->assertEquals(0, $progress_percentage->getValue($learning_items));
        $this->assertEquals('Not yet started', $progress_summary->getValue($learning_items));
    }

    public function test_export_for_template() {

        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        $info = $learning_items->export_for_template();

        $this->assertEquals($this->course1->id, $info->id);
        $this->assertEquals($this->course1->fullname, $info->fullname);
    }

    public function test_get_component() {
        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        // Test component name.
        $this->assertEquals('core_course', $learning_items->get_component());
    }

    public function test_get_type() {
        // Enrolled user to a course.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);

        // Get the users learning items.
        $learning_items = \core_course\user_learning\item::one($this->user1->id, $this->course1->id);

        // Test type.
        $this->assertEquals('course', $learning_items->get_type());
    }
}

