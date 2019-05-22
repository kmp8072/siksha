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

require_once($CFG->dirroot . '/lib/phpunit/classes/advanced_testcase.php');
require_once($CFG->dirroot . '/shezar/program/classes/observer.php');

/**
 * Class shezar_program_observer_testcase
 *
 * Tests functions found within the shezar_program_observer class.
 */
class shezar_program_observer_testcase extends advanced_testcase {

    /** @var testing_data_generator */
    private $generator;

    /** @var shezar_program_generator*/
    private $program_generator;

    /** @var stdClass */
    private $course1, $course2, $course3;

    /** @var program */
    private $program1, $program2;

    public function setUp() {
        $this->resetAfterTest(true);
        parent::setUp();
        global $DB;

        $this->generator = $this->getDataGenerator();
        $this->program_generator = $this->generator->get_plugin_generator('shezar_program');

        $this->course1 = $this->generator->create_course();
        $this->course2 = $this->generator->create_course();
        $this->course3 = $this->generator->create_course();
        $this->program1 = $this->program_generator->create_program();
        $this->program2 = $this->program_generator->create_program();

        // Reload courses. Otherwise when we compare the courses with the returned courses,
        // we get subtle differences in some values such as cacherev and sortorder.
        // Todo: Investigate whether we can improve the generator to fix this.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
    }

    public function reload_course($course) {
        global $DB;
        return $DB->get_record('course', array('id' => $course->id));
    }

    /**
     * Test that the results of the course_deleted static function in the shezar_program_observer
     * deletes the correct records and only the correct records.
     *
     * This creates several course sets across 2 programs and then triggers the course_deleted event
     * for one course.
     */
    public function test_course_deleted() {
        $this->resetAfterTest(true);
        global $DB;

        // Set up program1.

        $progcontent1 = new prog_content($this->program1->id);
        $progcontent1->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent1->add_set(CONTENTTYPE_MULTICOURSE);
        $progcontent1->add_set(CONTENTTYPE_COMPETENCY);

        /** @var course_set[] $coursesets */
        $coursesets = $progcontent1->get_course_sets();

        // For program 1, it will have 3 course sets:
        // Course set 1: Multi-course set with course1 only.
        // Course set 2: Multi-course set with course1 and course2.
        // Course set 3: Competency course set where the competency has course 3 linked.

        $coursedata = new stdClass();
        $coursedata->{$coursesets[0]->get_set_prefix() . 'courseid'} = $this->course1->id;
        $progcontent1->add_course(1, $coursedata);

        $progcontent1->add_course(2, $coursedata);
        $coursedata->{$coursesets[1]->get_set_prefix() . 'courseid'} = $this->course2->id;
        $progcontent1->add_course(2, $coursedata);

        /** @var shezar_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->generator->get_plugin_generator('shezar_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for course 3 will be assigned to this competency.
        $course3evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->course3);

        // Add a competency to the competency courseset.
        $compdata = new stdClass();
        $compdata->{$coursesets[2]->get_set_prefix() . 'competencyid'} = $competency->id;
        $progcontent1->add_competency(3, $compdata);

        $progcontent1->save_content();

        // Set up program2.

        $progcontent2 = new prog_content($this->program2->id);
        $progcontent2->add_set(CONTENTTYPE_RECURRING);

        /** @var course_set[] $coursesets */
        $coursesets = $progcontent2->get_course_sets();

        // Program2 contains a single recurring course set with course1.

        $coursesets[0]->course = $this->course1;
        $progcontent2->save_content();

        // Multi course set which contains course1.
        $prog1courseset1 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 1));
        // Multi course set which contains course1 and course2.
        $prog1courseset2 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 2));
        // Competency course set which contains competency 1 which links to course3.
        $prog1courseset3 = $DB->get_record('prog_courseset', array('programid' => $this->program1->id, 'sortorder' => 3));
        // Recurring course which contains course1.
        $prog2courseset1 = $DB->get_record('prog_courseset', array('programid' => $this->program2->id, 'sortorder' => 1));

        // We create the course_deleted event, deleting course1.
        $context = context_system::instance();
        $event = \core\event\course_deleted::create(array(
            'objectid' => $this->course1->id,
            'contextid' => $context->id,
            'other' => array(
                'fullname' => $this->course1->fullname
            )));
        $event->trigger();

        // The prog_courseset records that were only directly linked to course1 should have been deleted.
        // The others should still be there.
        $this->assertFalse($DB->record_exists('prog_courseset', array('id' => $prog1courseset1->id)));
        $this->assertTrue($DB->record_exists('prog_courseset', array('id' => $prog1courseset2->id)));
        $this->assertTrue($DB->record_exists('prog_courseset', array('id' => $prog1courseset3->id)));
        $this->assertFalse($DB->record_exists('prog_courseset', array('id' => $prog2courseset1->id)));

        // There should be no records left for course1 in prog_courseset_course.
        // But other records should still be there.
        $this->assertFalse($DB->record_exists('prog_courseset_course', array('courseid' => $this->course1->id)));
        $this->assertTrue($DB->record_exists('prog_courseset_course', array('courseid' => $this->course2->id)));

        // Call the component courses of the remaining course sets to ensure that still works following
        // the deletion of other data. And make sure they are in the order we expect.
        // We'll reload prog_content beforehand.
        unset($progcontent1);
        $progcontent1 = new prog_content($this->program1->id);
        $coursesets = $progcontent1->get_course_sets();

        $this->assertEquals($prog1courseset2->id, $coursesets[0]->id);
        $this->assertEquals(array($this->course2), $coursesets[0]->get_courses());
        $this->assertEquals(1, $coursesets[0]->sortorder);

        $this->assertEquals($prog1courseset3->id, $coursesets[1]->id);
        $this->assertEquals(array($this->course3->id => $this->course3), $coursesets[1]->get_courses());
        $this->assertEquals(2, $coursesets[1]->sortorder);
    }
}