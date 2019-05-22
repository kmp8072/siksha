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
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/current_learning/tests/fixtures/block_current_learning_testcase_base.php');

class block_current_learning_plan_data_testcase extends block_current_learning_testcase_base {

    private $generator;
    private $plan_generator;

    private $user1, $user2;
    private $course1, $course2;
    private $planrecord1, $planrecord2;

    protected function setUp() {
        global $CFG;
        parent::setUp();

        $this->setAdminUser();

        $this->generator = $this->getDataGenerator();
        $this->plan_generator = $this->generator->get_plugin_generator('shezar_plan');

        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        // Create some users.
        $this->user1 = $this->generator->create_user();
        $this->user2 = $this->generator->create_user();

        // Create some courses.
        $this->course1 = $this->generator->create_course();
        $this->course2 = $this->generator->create_course();

        // Create a learning plan.
        $this->planrecord1 = $this->plan_generator->create_learning_plan(array('userid' => $this->user1->id));
    }

    public function test_plans_disabled() {
        global $CFG;

        $plan = new development_plan($this->planrecord1->id);

        // Add a course to the plan.
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);

        // Approve the plan.
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);

        // The course should appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Now lets disable plan.
        $CFG->enablelearningplans = 3;

        // The course should not appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

    public function test_courses_from_plan() {

        // All courses added to any of the learner's active learning plans (i.e., learning plans that are not draft or
        // complete) and approved (i.e. the courses are not pending or declined) within those learning plans should be
        // displayed.

        $plan = new development_plan($this->planrecord1->id);

        // Add a course to the plan.
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);

        // Plan approved.
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Plan not approved.
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Plan completed.
        $plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

    public function test_courses_duplication() {

        // If a user in enrolled into a course directly and also via a plan, the course should only de displayed once.

        // Enroll user directly into course1.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Now add the same course to an approved learning plan.
        $plan = new development_plan($this->planrecord1->id);
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);

        // Get the new learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // The course should only appear once.
        $count = 0;
        foreach ($learning_data['learningitems'] as $item) {
            if ($item->id == $this->course1->id && $item->type == 'course') {
                $count++;
            }
        }
        $this->assertEquals(1, $count);
    }
}
