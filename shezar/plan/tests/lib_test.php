<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
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
 * @author Rob Tyler <rob.tyler@shezarlms.com>
 * @package shezar_plan
 */

defined('MOODLE_INTERNAL') || die();

class shezar_plan_lib_testcase extends advanced_testcase {
    /** @var shezar_plan_generator $plangenerator */
    protected $plangenerator;

    protected function setUp() {
        global $CFG;
        parent::setUp();

        require_once($CFG->dirroot.'/shezar/plan/lib.php');
        require_once($CFG->dirroot.'/shezar/hierarchy/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $this->plangenerator = $this->getDataGenerator()->get_plugin_generator('shezar_plan');
    }

    /**
     * Test creating a learning plan and adding a course.
     */
    public function test_add_course_to_learning_plan() {
        $this->resetAfterTest(true);

        // Create a learning plan.
        $plan = $this->plangenerator->create_learning_plan();

        $course = $this->getDataGenerator()->create_course();

        // Add the course to the learning plan.
        $this->add_component_to_learning_plan($plan->id, 'course', $course->id);
    }

    /**
     * Test creating a learning plan and adding a competency.
     */
    public function test_add_competency_to_learning_plan() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a learning plan.
        $plan = $this->plangenerator->create_learning_plan();

        // Get a hierarchy generator.
        /** @var shezar_hierarchy_generator $hierarchygenerator */
        $hierarchy_gen = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');

        // Create a new competency framework and check it exists.
        $comp_fw = $hierarchy_gen->create_framework('competency');
        $exists = $DB->record_exists('comp_framework', array('id' => $comp_fw->id));
        // Assert the existence of the framework.
        $this->assertTrue($exists);

        // Create a new competency and check it exists.
        $comp = $hierarchy_gen->create_hierarchy($comp_fw->id, 'competency', array('fullname' => 'Test Competency'));
        $exists = $DB->record_exists('comp', array('id' => $comp->id));
        // Assert the existence of the competency.
        $this->assertTrue($exists);

        $this->add_component_to_learning_plan($plan->id, 'competency', $comp->id);
    }

    /**
     * Test creating a learning plan and adding a program.
     */
    public function test_add_program_to_learning_plan() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a learning plan.
        $plan = $this->plangenerator->create_learning_plan();

        /** @var shezar_plan_generator $plangenerator */
        $program_gen = $this->getDataGenerator()->get_plugin_generator('shezar_program');

        // Create a new program and check it exists
        $program = $program_gen->create_program();
        $exists = $DB->record_exists('prog', array('id' => $program->id));
        $this->assertTrue($exists);
        // Add program to learning plan.
        $this->add_component_to_learning_plan($plan->id, 'program', $program->id);
    }

    /**
     * Add a component to a competency framework.
     * @param int $planid
     * @param string $component
     * @param int $componentid
     */
    protected function add_component_to_learning_plan($planid, $component, $componentid) {
        global $DB;
        // Add the competency to the learning plan.
        $plan = new development_plan($planid);
        $componentobj = $plan->get_component($component);
        $componentobj->update_assigned_items(array($componentid));
        // Check the course has been assigned to the learning plan.
        $exists = $DB->record_exists('dp_plan_' . $component . '_assign', array('planid' => $planid, $component . 'id' => $componentid));
        // Assert the existence of the record.
        $this->assertTrue($exists);
    }
}
