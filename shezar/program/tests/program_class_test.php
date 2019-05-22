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
 * @author Brendan Cox <brendan.cox@shezarlearning.com>
 * @package shezar_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/shezar/program/program.class.php');

/**
 * Class shezar_program_program_class_testcase
 *
 * Tests the methods in the program class in program.class.php
 */
class shezar_program_program_class_testcase extends reportcache_advanced_testcase {

    /** @var shezar_reportbuilder_cache_generator $data_generator */
    private $data_generator;

    /** @var shezar_program_generator $program_generator */
    private $program_generator;

    /** @var shezar_hierarchy_generator $hierarchy_generator */
    private $hierarchy_generator;

    /** @var shezar_cohort_generator $cohort_generator */
    private $cohort_generator;

    /** @var shezar_plan_generator $plan_generator */
    private $plan_generator;

    /** @var phpunit_message_sink $messagesink */
    private $messagesink;

    private $orgframe, $posframe;
    private $users = array(), $organisations = array(), $positions = array(), $audiences = array(), $managers = array(), $managerjas= array();

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->messagesink = $this->redirectMessages();

        // Number of each assignment type to a program.
        $maxusers = 40;
        $maxorgs = 3;
        $maxpos = 3;
        $maxauds = 3;
        $maxmanagers = 3;

        $this->data_generator = $this->getDataGenerator();
        $this->program_generator = $this->data_generator->get_plugin_generator('shezar_program');
        $this->hierarchy_generator = $this->data_generator->get_plugin_generator('shezar_hierarchy');
        $this->cohort_generator = $this->data_generator->get_plugin_generator('shezar_cohort');
        $this->plan_generator = $this->data_generator->get_plugin_generator('shezar_plan');

        for($numuser = 0; $numuser < $maxusers; $numuser++) {
            // Important to remember that create_user also creates a job assignment for each user and assigns
            // a manager. When no manager is specified, admin is the manager. So this will be overwritten
            // in this test for users with a managerja type program assignment, the rest will still have
            // admin as manager.
            $this->users[$numuser] = $this->data_generator->create_user();
        }

        $this->orgframe = $this->hierarchy_generator->create_org_frame(array());
        for ($numorg = 0; $numorg < $maxorgs; $numorg++) {
            $this->organisations[$numorg] = $this->hierarchy_generator->create_org(array('frameworkid' => $this->orgframe->id));
        }

        $this->posframe = $this->hierarchy_generator->create_pos_frame(array());
        for ($numpos = 0; $numpos < $maxpos; $numpos++) {
            $this->positions[$numpos] = $this->hierarchy_generator->create_pos(array('frameworkid' => $this->posframe->id));
        }

        for ($numaud = 0; $numaud < $maxauds; $numaud++) {
            $this->audiences[$numaud] = $this->data_generator->create_cohort();
        }

        for($numman = 0; $numman < $maxmanagers; $numman++) {
            // This is really assignment via hierarchies based on the manager's job assignment rather than
            // the manager themselves. For our testing, the managers and their job assignments map onto each
            // other according to the keys in $this->managers and $this->managerjas.
            $this->managers[$numman] = $this->data_generator->create_user();
            $this->managerjas[$numman] = \shezar_job\job_assignment::create_default($this->managers[$numman]->id);
        }
    }

    protected function tearDown() {
        $this->messagesink->clear();
        $this->messagesink->close();
        $this->messagesink = null;
        parent::tearDown();
    }

    /**
     * Creates an array of arrays. Each component array represents a users data.
     * It includes its own index to help us track which user we are dealing with at any one time.
     *  - So array with index 2 will be the user in $this->users[2].
     * It then includes an array of methods by which that user will be assigned to a program.
     * It then includes an array of objects in the same order as the assignment types.
     *  - This means that if the 1st assignment method is ASSIGNTYPE_ORGANISATION,
     * then the first item of the other array should be the organisation to assign to.
     *
     */
    private function get_assignment_data() {
        $user_data_full =  array(
            array(0, array(ASSIGNTYPE_INDIVIDUAL), array($this->users[0])),
            array(1, array(ASSIGNTYPE_INDIVIDUAL), array($this->users[1])),
            array(2, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[0])),
            array(3, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[0])),
            array(4, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[1])),
            array(5, array(ASSIGNTYPE_ORGANISATION), array($this->organisations[1])),
            array(6, array(ASSIGNTYPE_POSITION), array($this->positions[0])),
            array(7, array(ASSIGNTYPE_POSITION), array($this->positions[0])),
            array(8, array(ASSIGNTYPE_POSITION), array($this->positions[1])),
            array(9, array(ASSIGNTYPE_POSITION), array($this->positions[1])),
            array(10, array(ASSIGNTYPE_COHORT), array($this->audiences[0])),
            array(11, array(ASSIGNTYPE_COHORT), array($this->audiences[0])),
            array(12, array(ASSIGNTYPE_COHORT), array($this->audiences[1])),
            array(13, array(ASSIGNTYPE_COHORT), array($this->audiences[1])),
            array(14, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[0])),
            array(15, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[0])),
            array(16, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[1])),
            array(17, array(ASSIGNTYPE_MANAGERJA), array($this->managerjas[1])),
            array(18, array(ASSIGNTYPE_ORGANISATION, ASSIGNTYPE_POSITION), array($this->organisations[0], $this->positions[1])),
            array(19, array(ASSIGNTYPE_ORGANISATION, ASSIGNTYPE_POSITION, ASSIGNTYPE_COHORT),
                array($this->organisations[1], $this->positions[1], $this->audiences[0])),
            array(20, array(ASSIGNTYPE_COHORT, ASSIGNTYPE_MANAGERJA), array($this->audiences[1], $this->managerjas[1])),
            array(21, array(ASSIGNTYPE_COHORT, ASSIGNTYPE_INDIVIDUAL), array($this->audiences[1], $this->users[21])),
            array(22, array(), array()), // This user will not be assigned to anything.
            array(23, array(), array()), // This user will not be assigned to anything.
        );

        return $user_data_full;
    }

    /**
     * @param program $program
     * @param array $user_data_full - array in a format like that returned in get_assignment_data above.
     */
    private function assign_users_to_program($program, $user_data_full) {

        foreach($user_data_full as $user_data) {
            foreach($user_data[1] as $key => $assignment_method) {
                $userid = $this->users[$user_data[0]]->id;
                // Groupid could actually be the individual user id in the case of ASSIGNTYPE_INDIVIDUAL
                // but for all other cases, its the id of the audience, organisation etc.
                $groupid = $user_data[2][$key]->id;
                // All users will have a job assignment record that was created during create_user.
                $jobassignment = \shezar_job\job_assignment::get_first($userid);
                switch($assignment_method) {
                    case ASSIGNTYPE_ORGANISATION:
                        $jobassignment->update(array('organisationid' => $groupid));
                        break;
                    case ASSIGNTYPE_POSITION:
                        $jobassignment->update(array('positionid' => $groupid));
                        break;
                    case ASSIGNTYPE_COHORT:
                        $this->cohort_generator->cohort_assign_users($groupid, array($userid));
                        break;
                    case ASSIGNTYPE_MANAGERJA:
                        $jobassignment->update(array('managerjaid' => $groupid));
                        break;
                }
                $this->data_generator->assign_to_program($program->id, $assignment_method, $user_data[2][$key]->id);
            }
        }
    }

    /**
     * These are parts of the strings returned when assignment is for their corresponding reason.
     *
     * The html hasn't been included here. This is really just to be used as part of a strpos to indicate
     * that the correct reasons were included in the string, not to validate the entire message.
     *
     * @return array
     */
    private function get_expected_assignment_reason_strings() {
        $expectedstrings = array(
            ASSIGNTYPE_COHORT => 'Member of audience',
            ASSIGNTYPE_INDIVIDUAL => 'Assigned as an individual',
            ASSIGNTYPE_MANAGERJA => 'Part of',
            ASSIGNTYPE_ORGANISATION => 'Member of organisation',
            ASSIGNTYPE_POSITION => 'Hold position of ',
        );

        return $expectedstrings;
    }

    /**
     * The dataProvider for a number of assignment and completion record reason tests.
     *
     * @return array
     */
    public function program_type() {
        $data = array(
            array('program'),
            array('certification')
        );

        return $data;
    }

    /**
     * Given the type, 'program' or 'certification', returns the program instances used
     * for assignment and completion record reason tests.
     *
     * @param $type - a string in the array returned by the program_type dataProvider.
     * @return program[]
     */
    private function get_program_objects($type) {
        if ($type === 'program') {
            /** @var program $program1 */
            $program1 = $this->program_generator->create_program();
            /** @var program $program2 */
            $program2 = $this->program_generator->create_program();
        } else {
            // Create a certification. We only need to deal with it's related prog records for this
            // test though.
            $program1id = $this->program_generator->create_certification();
            $program1 = new program($program1id);

            $program2id = $this->program_generator->create_certification();
            $program2 = new program($program2id);
        }

        return array($program1, $program2);
    }

    /**
     * Tests the program->display_required_assignment_reason method.
     *
     * The dataProvider ensures this is tested for both programs and certs.
     *
     * The user assignments as defined in get_assignment_data make sure and each assignment
     * method is tested along with combinations of them.
     *
     * @dataProvider program_type
     */
    public function test_display_required_assignment_reason($type) {
        $this->resetAfterTest(true);

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $assignmentdata = $this->get_assignment_data();
        $this->assign_users_to_program($program1, $assignmentdata);

        $this->setAdminUser();

        // Strings for start of assignment reason string, before show list of reasons.
        $learnerisassignedtocomplete = '<p>The learner is required to complete this program under the following criteria:</p>';
        $youarerequiredtocompleted = '<p>You are required to complete this program under the following criteria:</p>';
        $expectedreasonstrings = $this->get_expected_assignment_reason_strings();

        foreach($assignmentdata as $userassigned) {

            $userid = $this->users[$userassigned[0]]->id;

            // First of all, none should be assigned to program2.
            $program2reason = $program2->display_required_assignment_reason($userid, true);
            $this->assertEquals('', $program2reason);

            // Let's run the function we're testing.
            $returnedreason = $program1->display_required_assignment_reason($userid, true);

            if (empty($userassigned[1])) {
                // There are no assignments specified for this user. So no string should be returned.
                $this->assertEquals('', $returnedreason);
            } else {
                // We said true for the $viewinganothersprogram param, so should return 'The learner is required to complete...'.
                $this->assertNotFalse(strpos($returnedreason, $learnerisassignedtocomplete),
                    'The user with index ' . $userassigned[0] . ' did not return the expected string.');
                // Loop through the expected reason strings making sure those and only those we expect are present.
                foreach($expectedreasonstrings as $assignmentmethod => $expectedreasonstring) {
                    if (in_array($assignmentmethod, $userassigned[1])) {
                        $this->assertNotFalse(strpos($returnedreason, $expectedreasonstring),
                            'The user with index ' . $userassigned[0] . ' did not return an expected reason.');
                    } else {
                        $this->assertFalse(strpos($returnedreason, $expectedreasonstring),
                            'The user with index ' . $userassigned[0] . ' returned a reason it should not have.');
                    }
                }
            }
        }

        // Our loop tested a whole range of scenarios but it's clunky to test for more specific
        // strings, e.g. manager's names, in a loop.  We do a few spot checks below to test for actual manager, org and pos
        // names in the returned message.

        // User[11] should have a reason of being assigned to audience[0]
        $user11 = $this->users[11];
        $expected = $learnerisassignedtocomplete;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_required_assignment_reason($user11->id, true);
        $this->assertEquals($expected, $returnedreason);

        // Let's test this one with some different settings for $USER and the 2nd param of display_assignment_reason.

        // First of all,have user11 view their own record.
        $this->setUser($user11);
        $expected = $youarerequiredtocompleted;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_required_assignment_reason($user11->id, true);
        $this->assertEquals($expected, $returnedreason);

        // Now set $includefull to false. This means we should just get the <li> tags and their contents.
        $expected = '<li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li>';
        $returnedreason = $program1->display_required_assignment_reason($user11->id, false);
        $this->assertEquals($expected, $returnedreason);

        // User[16] should have the reasons of being part of manager[1]'s team.
        $this->setAdminUser();
        $user16 = $this->users[16];
        $expected = $learnerisassignedtocomplete;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $returnedreason = $program1->display_required_assignment_reason($user16->id, true);
        $this->assertEquals($expected, $returnedreason);

        // User[22] has not been assigned to the program. An empty string should be returned.
        $user22 = $this->users[22];
        $expected = '';
        $returnedreason = $program1->display_required_assignment_reason($user22->id, true);
        $this->assertEquals($expected, $returnedreason);
        // We don't expect any different when the other settings are changed.
        $returnedreason = $program1->display_required_assignment_reason($user22->id, true);
        $this->assertEquals($expected, $returnedreason);
        $returnedreason = $program1->display_required_assignment_reason($user22->id, false);
        $this->assertEquals($expected, $returnedreason);
    }

    /**
     * Tests the program->display_completion_record_reason method when used with standard assignment methods.
     *
     * @dataProvider program_type
     */
    public function test_display_completion_record_reason_required_assignments($type) {
        $this->resetAfterTest(true);

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $assignmentdata = $this->get_assignment_data();
        $this->assign_users_to_program($program1, $assignmentdata);

        // Strings for start of assignment reason string, before show list of reasons.
        $hasrecordbecause = '<p>This user has a current completion record for the following reasons:</p>';

        // We don't need to test everything that would be done internally by the display_required_assignment_reason method in full,
        // we just need to make sure that when there's data that would process, it is returned properly by display_completion_record_reason.

        // User[11] should have a reason of being assigned to audience[0]
        $user11 = $this->users[11];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user11);
        $this->assertEquals($expected, $returnedreason);

        // User[16] should have the reasons of being part of manager[1]'s team.
        $user16 = $this->users[16];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user16);
        $this->assertEquals($expected, $returnedreason);
    }

    /**
     * Tests the program->display_completion_record_reason method when a user is assigned via a learning plan.
     *
     * Note that we use the dataProvider to test this with certifications as well as programs.
     * Certifications are currently not supported by learning plans (at the time of writing this test),
     * but I've included certs for now since it is still passing.
     *
     * @dataProvider program_type
     */
    public function test_display_completion_record_reason_learningplan($type) {
        $this->resetAfterTest(true);

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $user = $this->users[25];
        $this->setUser($user);

        $enddate = time() + DAYSECS;
        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $user->id, 'enddate' => $enddate));
        $plan = new development_plan($planrecord->id);

        $plan->initialize_settings();
        /** @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');
        $assigneditem = $component_program->assign_new_item($program1->id);

        $expected = '<p>A current completion record exists for this user due to having added this program to their learning plan. However, please note that this has not been approved.</p>';
        $returnedreason = $program1->display_completion_record_reason($user);
        $this->assertEquals($expected, $returnedreason);

        // Set the status to approved.
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        $expected = '<p>This user has a current completion record for the following reasons:</p><ul><li>Assigned via learning plan.</li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user);
        $this->assertEquals($expected, $returnedreason);
    }

    /**
     * Tests the program->display_completion_record_reason method. This a message is given acknowledging
     * when users are suspended or deleted.
     *
     * @dataProvider program_type
     */
    public function test_display_completion_record_reason_deleted_suspended($type) {
        $this->resetAfterTest(true);
        global $DB;

        /** @var program $program1 */
        /** @var program $program2 */
        list($program1, $program2) = $this->get_program_objects($type);

        $assignmentdata = $this->get_assignment_data();
        $this->assign_users_to_program($program1, $assignmentdata);

        // Strings for start of assignment reason string, before show list of reasons.
        $hasrecordbecause = '<p>This user has a current completion record for the following reasons:</p>';

        // Check their status before deletion and suspension.

        // User[11] should have a reason of being assigned to audience[0]
        $user11 = $this->users[11];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Member of audience \''. $this->audiences[0]->name .'\'.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user11);
        $this->assertEquals($expected, $returnedreason);

        // User[16] should have the reasons of being part of manager[1]'s team.
        $user16 = $this->users[16];
        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $returnedreason = $program1->display_completion_record_reason($user16);
        $this->assertEquals($expected, $returnedreason);

        delete_user($user11);
        $user11 = $DB->get_record('user', array('id' => $user11->id));

        $user16->suspended = 1;
        user_update_user($user16, false);
        \shezar_core\event\user_suspended::create_from_user($user16)->trigger();
        $program1->update_learner_assignments();
        $user16 = $DB->get_record('user', array('id' => $user16->id));

        $expected = '<p>A current completion record exists for this user. However, as the user has been deleted, they are not currently assigned.</p>';
        $returnedreason = $program1->display_completion_record_reason($user11);
        $this->assertEquals($expected, $returnedreason);

        $expected = $hasrecordbecause;
        $expected .= '<ul><li class="assignmentcriteria"><span class="criteria">Part of \''. fullname($this->managers[1]) .'\' team.</span></li></ul>';
        $expected .= '<p>This user is suspended. Automated processes such as cron tasks are unlikely to update this user\'s records.</p>';
        $returnedreason = $program1->display_completion_record_reason($user16);
        $this->assertEquals($expected, $returnedreason);
    }

    // Create some completion records by direct insert where there will be no explanation for them.
    /**
     *
     */
    public function test_display_completion_record_reason_unknown() {
        $this->resetAfterTest(true);
        global $DB;

        // Create a program and a certification.

        /** @var program $program1 */
        $program1 = $this->program_generator->create_program();

        $certprogram1id = $this->program_generator->create_certification();
        /** @var program $certprogram1 */
        $certprogram1 = new program($certprogram1id);

        $user25 = $this->users[25];
        $user26 = $this->users[26];
        $user27 = $this->users[27];
        $user28 = $this->users[28];

        /*
         * $user25 has a prog completion record for the program.
         * $user26 has both a prog completion record and a cert completion for the cert.
         * $user27 has no prog completion but not cert completion for the cert.
         * $user28 has a prog completion but no cert completion for the cert.
         */

        $progcompletion25 = new stdClass();
        $progcompletion25->programid = $program1->id;
        $progcompletion25->userid = $user25->id;
        $progcompletion25->coursesetid = 0;
        $progcompletion25->status = 0;
        $progcompletion25->timestarted = time();
        $progcompletion25->timedue = COMPLETION_TIME_NOT_SET;
        $progcompletion25->timecompleted = 0;
        $DB->insert_record('prog_completion', $progcompletion25);

        $progcompletion26 = clone $progcompletion25;
        $progcompletion26->programid = $certprogram1->id;
        $progcompletion26->userid = $user26->id;
        $DB->insert_record('prog_completion', $progcompletion26);

        $certcompletion26 = new stdClass();
        $certcompletion26->certifid = $certprogram1->certifid;
        $certcompletion26->userid = $user26->id;
        $certcompletion26->certifpath = CERTIFPATH_CERT;
        $certcompletion26->status = CERTIFSTATUS_ASSIGNED;
        $certcompletion26->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion26->timewindowopens = 0;
        $certcompletion26->timeexpires = 0;
        $certcompletion26->timecompleted = 0;
        $certcompletion26->timemodified = time();
        $DB->insert_record('certif_completion', $certcompletion26);

        $certcompletion27 = clone $certcompletion26;
        $certcompletion27->userid = $user27->id;
        $DB->insert_record('certif_completion', $certcompletion27);

        $progcompletion28 = clone $progcompletion26;
        $progcompletion28->userid = $user28->id;
        $DB->insert_record('prog_completion', $progcompletion28);

        $expected = '<p>A current completion record exists for this user and this program, however no current assignment details could be found.</p>';
        $returnedreason = $program1->display_completion_record_reason($user25);
        $this->assertEquals($expected, $returnedreason);

        // The user has both cert and prog completions, it just needs to say there's a completion but no found reason.
        $expected = '<p>A current completion record exists for this user and this program, however no current assignment details could be found.</p>';
        $returnedreason = $certprogram1->display_completion_record_reason($user26);
        $this->assertEquals($expected, $returnedreason);

        // The user has only a cert but no prog completion record. The completion editor currently only shows a current record if
        // it has both.
        $expected = 'The user is not currently assigned. A user cannot have a current completion record unless they are assigned.';
        $returnedreason = $certprogram1->display_completion_record_reason($user27);
        $this->assertEquals($expected, $returnedreason);

        // The user has only a prog but no cert completion record. The completion editor currently only shows a current record if
        // it has both which makes the returned message seem somewhat wrong. However, we'll stick with this behaviour
        // to avoid complicating code and the API.
        $expected = '<p>A current completion record exists for this user and this program, however no current assignment details could be found.</p>';
        $returnedreason = $certprogram1->display_completion_record_reason($user28);
        $this->assertEquals($expected, $returnedreason);
    }
}