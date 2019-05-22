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
 * @author  Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests functions in mod/facetoface/db/upgradelib.php
 */
class mod_facetoface_upgradelib_testcase extends advanced_testcase {

    /**
     * Test the F2F customfield migration function that helps us move signup and cancellation data.
     * Here we test with the default setting.
     */
    public function test_mod_facetoface_migrate_session_signup_customdata_default() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        // We have to reset as we're going to be adding a config var when calling mod_facetoface_migrate_session_signup_customdata.
        $this->resetAfterTest();
        $this->preventResetByRollback();

        $dbman = $DB->get_manager();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        // To test this method we are going to use 3 learners, 1 manager, and 1 course with a facetoface activity with
        // approval required turned on.
        // Each of the three users has the
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager1->id);
        \shezar_job\job_assignment::create_default($user1->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user3->id, array('managerjaid' => $managerja->id));
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_manager'));
        $sid = $facetofacegenerator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array()));
        $session = facetoface_get_session($sid);
        $fieldid = $DB->get_field('facetoface_signup_info_field', 'id', array('shortname' => 'signupnote'));

        // Sign user 1 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        // Set signup note to "Elephants" for user 1.
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_signupnote = 'Elephants';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 2 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        // Set signup note to "Monkeys" for user 2.
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_signupnote = 'Monkeys';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 3 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user3->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user3signup = facetoface_get_attendee($session->id, $user3->id);
        // Set signup note to "Sharks" for user 3.
        $customdata = new stdClass;
        $customdata->id = $user3signup->submissionid;
        $customdata->customfield_signupnote = 'Sharks';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Check that all three users have been signed up and that they are in the requested state which is where we expect them.
        $this->assertSame(3, $DB->count_records('facetoface_signup_info_data'));
        $this->assertSame('Elephants', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user1signup->submissionid)));
        $this->assertSame('Monkeys', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user2signup->submissionid)));
        $this->assertSame('Sharks', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user3signup->submissionid)));
        $this->assertCount(3, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        $this->setUser($manager1);
        // Approve user 1 and user 3's signup requests.
        facetoface_approve_requests((object)array(
            's' => $sid,
            'action' => 'approvalrequired',
            'requests' => array(
                $user1->id => 2,
                $user3->id => 2
            )
        ));

        // Check that we now have two users in the waitlisted state and only 1 remaining in the requested state.
        $this->assertCount(1, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(2, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        // Check that the user 1 has Elephants set as their signup note.
        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user1data);
        $this->assertSame('Elephants', $user1data['signupnote']);

        // Check that the user 2 has Monkeys set as their signup note.
        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user2data);
        $this->assertSame('Monkeys', $user2data['signupnote']);

        // Check that the user 3 has Sharks set as their signup note.
        $signupstatus = new stdClass();
        $signupstatus->id = $user3signup->submissionid;
        $user3data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user3data);
        $this->assertSame('Sharks', $user3data['signupnote']);

        // OK all data is correct and as we expect, now revert it so that it appears as though we've got a history like
        // we did prior to 2.7.14 and 2.9.6.
        // Because the API has been changed to fix this issue we must replicate what we previously saw by directly inserting
        // data in the custom field data tables.
        $DB->delete_records('facetoface_signup_info_data');

        // Create signup note data for user 1 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they got signed up and then approved by their manager.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user1signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user1signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user1signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user1signupids)->id, 'fieldid' => $fieldid, 'data' => 'Elephants'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $fieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $fieldid, 'data' => ''));

        // Create signup note data for user 2 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they signed up but have not been approved.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user2signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user2signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(1, $user2signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user2signupids)->id, 'fieldid' => $fieldid, 'data' => 'Monkeys'));

        // Create signup note data for user 3 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they got signed up and then approved by their manager.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user3signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user3signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user3signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user3signupids)->id, 'fieldid' => $fieldid, 'data' => 'Sharks'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $fieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $fieldid, 'data' => ''));

        // Run the upgrade helper function and ensure
        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_signup_info_data', 'facetofacesignupid');

        // Confirm that now when I fetch using the signup->id I get the expected value "Elephants" for user 1.
        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user1data);
        $this->assertSame('Elephants', $user1data['signupnote']);

        // Confirm that now when I fetch using the signup->id I get the expected value "Monkeys" for user 1.
        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user2data);
        $this->assertSame('Monkeys', $user2data['signupnote']);

        // Confirm that now when I fetch using the signup->id I get the expected value "Sharks" for user 1.
        $signupstatus = new stdClass();
        $signupstatus->id = $user3signup->submissionid;
        $user3data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user3data);
        $this->assertSame('Sharks', $user3data['signupnote']);

        // Check that there are no users.
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_USER_CANCELLED));
        // Cancel user 2's signup request.
        facetoface_cancel_attendees($sid, array($user2->id));
        // Check we now have 1 user who is cancelled.
        $this->assertCount(1, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_USER_CANCELLED));

        // Signup user 2 again.
        $result = facetoface_user_import($course, $facetoface, $session, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        // Save an empty signup note this time.
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_signupnote = '';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');
        // Now that they have been signed up again we expect there to be no cancelled users.
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_USER_CANCELLED));

        // Check we get an empty signup note.
        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertSame('', $user2data['signupnote']);

        // Now we want to fake this exact data situation prior to the status => signup migration.
        // We can't do this using the API's they've been fixed so we just cheat and insert directly into the database.
        $DB->delete_records('facetoface_signup_info_data');
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user2signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user2signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user2signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user2signupids)->id, 'fieldid' => $fieldid, 'data' => 'Fish'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $fieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $fieldid, 'data' => ''));

        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_signup_info_data', 'facetofacesignupid');

        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);

        $this->assertArrayHasKey('signupnote', $user2data);
        $this->assertSame('Fish', $user2data['signupnote']);
    }

    /**
     * Test the F2F customfield migration function that helps us move signup and cancellation data.
     * Here we test with the latest records setting.
     */
    public function test_mod_facetoface_migrate_session_signup_customdata_latest() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        $this->resetAfterTest();
        $this->preventResetByRollback();

        $CFG->facetoface_customfield_migration_behaviour = 'latest';

        $dbman = $DB->get_manager();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        /** @var shezar_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager1->id);
        \shezar_job\job_assignment::create_default($user1->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user3->id, array('managerjaid' => $managerja->id));
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_manager'));
        $sid = $facetofacegenerator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array()));
        $session = facetoface_get_session($sid);
        $fieldid = $DB->get_field('facetoface_signup_info_field', 'id', array('shortname' => 'signupnote'));

        $result = facetoface_user_import($course, $facetoface, $session, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_signupnote = 'Elephants';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        $result = facetoface_user_import($course, $facetoface, $session, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_signupnote = 'Monkeys';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        $result = facetoface_user_import($course, $facetoface, $session, $user3->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user3signup = facetoface_get_attendee($session->id, $user3->id);
        $customdata = new stdClass;
        $customdata->id = $user3signup->submissionid;
        $customdata->customfield_signupnote = 'Sharks';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        $this->assertSame(3, $DB->count_records('facetoface_signup_info_data'));

        $this->assertSame('Elephants', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user1signup->submissionid)));
        $this->assertSame('Monkeys', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user2signup->submissionid)));
        $this->assertSame('Sharks', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user3signup->submissionid)));

        $this->assertCount(3, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        $this->setUser($manager1);
        facetoface_approve_requests((object)array(
            's' => $sid,
            'action' => 'approvalrequired',
            'requests' => array(
                $user1->id => 2,
                $user3->id => 2
            )
        ));

        $this->assertCount(1, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(2, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user1data);
        $this->assertSame('Elephants', $user1data['signupnote']);

        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user2data);
        $this->assertSame('Monkeys', $user2data['signupnote']);

        $signupstatus = new stdClass();
        $signupstatus->id = $user3signup->submissionid;
        $user3data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user3data);
        $this->assertSame('Sharks', $user3data['signupnote']);

        // OK all data is correct and as we expect, now revert it so that it appears as though we've got a history like
        // we did prior to 2.7.14 and 2.9.6.

        $DB->delete_records('facetoface_signup_info_data');
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user1signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user1signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user1signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user1signupids)->id, 'fieldid' => $fieldid, 'data' => 'Elephants'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $fieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $fieldid, 'data' => ''));

        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user2signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user2signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(1, $user2signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user2signupids)->id, 'fieldid' => $fieldid, 'data' => 'Monkeys'));

        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user3signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user3signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user3signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user3signupids)->id, 'fieldid' => $fieldid, 'data' => 'Sharks'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $fieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $fieldid, 'data' => ''));

        // Statuscode 10 = MDL_F2F_STATUS_USER_CANCELLED.
        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_signup_info_data', 'facetofacesignupid');

        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user1data);
        $this->assertSame('', $user1data['signupnote']); // Latest says Elephants is lost.

        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user2data);
        $this->assertSame('Monkeys', $user2data['signupnote']);

        $signupstatus = new stdClass();
        $signupstatus->id = $user3signup->submissionid;
        $user3data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user3data);
        $this->assertSame('', $user3data['signupnote']); // Latest says Sharks is lost.

        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_USER_CANCELLED));

        facetoface_cancel_attendees($sid, array($user2->id));

        $this->assertCount(1, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_USER_CANCELLED));

        $result = facetoface_user_import($course, $facetoface, $session, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_signupnote = '';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_USER_CANCELLED));

        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user2data);
        $this->assertSame('', $user2data['signupnote']);

        // OK Regular cancellation works OK.
        $DB->delete_records('facetoface_signup_info_data');
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user2signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user2signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user2signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user2signupids)->id, 'fieldid' => $fieldid, 'data' => 'Fish'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $fieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $fieldid, 'data' => ''));

        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_signup_info_data', 'facetofacesignupid');

        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);

        $this->assertSame('', $user2data['signupnote']); // Lates - Fish is lost.
    }

    /**
     * Test the F2F customfield migration function that helps us move signup and cancellation data.
     * Here we test cancellation notes.
     */
    public function test_mod_facetoface_migrate_session_cancellation_customdata_default() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        // We have to reset as we're going to be adding a config var when calling mod_facetoface_migrate_session_signup_customdata.
        $this->resetAfterTest();
        $this->preventResetByRollback();

        $dbman = $DB->get_manager();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        /** @var shezar_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');

        // To test this method we are going to use 3 learners, 1 manager, and 1 course with a facetoface activity with
        // approval required turned on.
        // Each of the three users has the same manager, we don't use the manager, they are just there as its more accurate and
        // prevents debugging calls.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager1->id);
        \shezar_job\job_assignment::create_default($user1->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user3->id, array('managerjaid' => $managerja->id));
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_manager'));
        $sid = $facetofacegenerator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array()));
        $session = facetoface_get_session($sid);
        $signupfieldid = $DB->get_field('facetoface_signup_info_field', 'id', array('shortname' => 'signupnote'));
        $cancellationfieldid = $DB->get_field('facetoface_cancellation_info_field', 'id', array('shortname' => 'cancellationnote'));

        // Sign user 1 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        // Set signup note to "Elephants" for user 1.
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_signupnote = 'Elephants';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 2 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        // Set signup note to "Monkeys" for user 2.
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_signupnote = 'Monkeys';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 3 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user3->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user3signup = facetoface_get_attendee($session->id, $user3->id);
        // Set signup note to "Sharks" for user 3.
        $customdata = new stdClass;
        $customdata->id = $user3signup->submissionid;
        $customdata->customfield_signupnote = 'Sharks';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Check that all three users have been signed up and that they are in the requested state which is where we expect them.
        $this->assertSame(3, $DB->count_records('facetoface_signup_info_data'));
        $this->assertSame('Elephants', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user1signup->submissionid)));
        $this->assertSame('Monkeys', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user2signup->submissionid)));
        $this->assertSame('Sharks', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user3signup->submissionid)));
        $this->assertCount(3, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        $this->setUser($manager1);
        // Approve all three users.
        facetoface_approve_requests((object)array(
            's' => $sid,
            'action' => 'approvalrequired',
            'requests' => array(
                $user1->id => 2,
                $user2->id => 2,
                $user3->id => 2
            )
        ));

        // Check that we now have two users in the waitlisted state and only 1 remaining in the requested state.
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(3, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        // Now cancel users 1 and 2, we'll give them customfield data after this.
        facetoface_cancel_attendees($sid, array($user1->id, $user2->id));

        // Set cancellation note to "Blue" for user 1.
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_cancellationnote = 'Blue';
        customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');

        // Set cancellation note to "Red" for user 2.
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_cancellationnote = 'Red';
        customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');

        // Get the cancellation note data for user 1 and check it is as we expect.
        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertArrayHasKey('cancellationnote', $user1data);
        $this->assertSame('Blue', $user1data['cancellationnote']);

        // Get the cancellation note data for user 2 and check it is as we expect.
        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertArrayHasKey('cancellationnote', $user2data);
        $this->assertSame('Red', $user2data['cancellationnote']);

        // Now we are going to sign user 1 up again, and cancel them again.
        // Sign user 1 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_signupnote = 'Rockets';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');
        facetoface_approve_requests((object)array(
            's' => $sid,
            'action' => 'approvalrequired',
            'requests' => array(
                $user1->id => 2,
            )
        ));
        facetoface_cancel_attendees($sid, array($user1->id));
        // Set cancellation note to "Blue" for user 1.
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_cancellationnote = '';
        customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');

        // OK all data is correct and as we expect, now revert it so that it appears as though we've got a history like
        // we did prior to 2.7.14 and 2.9.6.
        // Because the API has been changed to fix this issue we must replicate what we previously saw by directly inserting
        // data in the custom field data tables.
        $DB->delete_records('facetoface_signup_info_data');

        // Create signup note data for user 1 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they got signed up and then approved by their manager.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user1signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user1signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(8, $user1signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Elephants'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_cancellation_info_data', array('facetofacecancellationid' => next($user1signupids)->id, 'fieldid' => $cancellationfieldid, 'data' => 'Blue'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Rockets'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_cancellation_info_data', array('facetofacecancellationid' => next($user1signupids)->id, 'fieldid' => $cancellationfieldid, 'data' => ''));

        // Create signup note data for user 2 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they signed up but have not been approved.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user2signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user2signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(4, $user2signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user2signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Monkeys'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_cancellation_info_data', array('facetofacecancellationid' => next($user2signupids)->id, 'fieldid' => $cancellationfieldid, 'data' => 'Red'));

        // Create signup note data for user 3 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they got signed up and then approved by their manager.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user3signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user3signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user3signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user3signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Sharks'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));

        // Run the upgrade helper function and ensure that we get the data we expect.
        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_signup_info_data', 'facetofacesignupid');
        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_cancellation_info_data', 'facetofacecancellationid');

        // Now check that we have the signup and cancellation notes still in tack.
        // User 1 should have Elephants as signup and Blue as cancellation.
        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertArrayHasKey('signupnote', $user1data);
        $this->assertArrayNotHasKey('cancellationnote', $user1data);
        $this->assertSame('Rockets', $user1data['signupnote']);
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertArrayHasKey('cancellationnote', $user1data);
        $this->assertArrayNotHasKey('signupnote', $user1data);
        $this->assertSame('Blue', $user1data['cancellationnote']);

        // User 2 should have Monkeys as signup and Red as cancellation.
        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertSame('Monkeys', $user2data['signupnote']);
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertSame('Red', $user1data['cancellationnote']);
        $this->assertArrayNotHasKey('signupnote', $user1data);

        // User 1 should have Sharks as signup and should not have a cancellation note.
        $signupstatus = new stdClass();
        $signupstatus->id = $user3signup->submissionid;
        $user3data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertSame('Sharks', $user3data['signupnote']);
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertArrayNotHasKey('cancellationnote', $user1data);
        $this->assertArrayNotHasKey('signupnote', $user1data);
    }

    /**
     * Test the F2F customfield migration function that helps us move signup and cancellation data.
     * Here we test cancellation notes.
     */
    public function test_mod_facetoface_migrate_session_cancellation_customdata_latest() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        // We have to reset as we're going to be adding a config var when calling mod_facetoface_migrate_session_signup_customdata.
        $this->resetAfterTest();
        $this->preventResetByRollback();

        $dbman = $DB->get_manager();

        $CFG->facetoface_customfield_migration_behaviour = 'latest';

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        /** @var shezar_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');

        // To test this method we are going to use 3 learners, 1 manager, and 1 course with a facetoface activity with
        // approval required turned on.
        // Each of the three users has the same manager, we don't use the manager, they are just there as its more accurate and
        // prevents debugging calls.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $manager1 = $this->getDataGenerator()->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager1->id);
        \shezar_job\job_assignment::create_default($user1->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user3->id, array('managerjaid' => $managerja->id));
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_manager'));
        $sid = $facetofacegenerator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array()));
        $session = facetoface_get_session($sid);
        $signupfieldid = $DB->get_field('facetoface_signup_info_field', 'id', array('shortname' => 'signupnote'));
        $cancellationfieldid = $DB->get_field('facetoface_cancellation_info_field', 'id', array('shortname' => 'cancellationnote'));

        // Sign user 1 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        // Set signup note to "Elephants" for user 1.
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_signupnote = 'Elephants';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 2 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        // Set signup note to "Monkeys" for user 2.
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_signupnote = 'Monkeys';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 3 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user3->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user3signup = facetoface_get_attendee($session->id, $user3->id);
        // Set signup note to "Sharks" for user 3.
        $customdata = new stdClass;
        $customdata->id = $user3signup->submissionid;
        $customdata->customfield_signupnote = 'Sharks';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Check that all three users have been signed up and that they are in the requested state which is where we expect them.
        $this->assertSame(3, $DB->count_records('facetoface_signup_info_data'));
        $this->assertSame('Elephants', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user1signup->submissionid)));
        $this->assertSame('Monkeys', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user2signup->submissionid)));
        $this->assertSame('Sharks', $DB->get_field('facetoface_signup_info_data', 'data', array('facetofacesignupid' => $user3signup->submissionid)));

        $this->assertCount(3, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        $this->setUser($manager1);
        // Approve all three users.
        facetoface_approve_requests((object)array(
            's' => $sid,
            'action' => 'approvalrequired',
            'requests' => array(
                $user1->id => 2,
                $user2->id => 2,
                $user3->id => 2
            )
        ));

        // Check that we now have two users in the waitlisted state and only 1 remaining in the requested state.
        $this->assertCount(0, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_REQUESTED));
        $this->assertCount(3, facetoface_get_users_by_status($sid, MDL_F2F_STATUS_WAITLISTED));

        // Now cancel users 1 and 2, we'll give them customfield data after this.
        facetoface_cancel_attendees($sid, array($user1->id, $user2->id));

        // Set cancellation note to "Blue" for user 1.
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_cancellationnote = 'Blue';
        customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');

        // Set cancellation note to "Red" for user 2.
        $user2signup = facetoface_get_attendee($session->id, $user2->id);
        $customdata = new stdClass;
        $customdata->id = $user2signup->submissionid;
        $customdata->customfield_cancellationnote = 'Red';
        customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');

        // Get the cancellation note data for user 1 and check it is as we expect.
        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertArrayHasKey('cancellationnote', $user1data);
        $this->assertSame('Blue', $user1data['cancellationnote']);

        // Get the cancellation note data for user 2 and check it is as we expect.
        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertArrayHasKey('cancellationnote', $user2data);
        $this->assertSame('Red', $user2data['cancellationnote']);

        // Now we are going to sign user 1 up again, and cancel them again.
        // Sign user 1 up to the facetoface activity.
        $result = facetoface_user_import($course, $facetoface, $session, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_signupnote = 'Rockets';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');
        facetoface_approve_requests((object)array(
            's' => $sid,
            'action' => 'approvalrequired',
            'requests' => array(
                $user1->id => 2,
            )
        ));
        facetoface_cancel_attendees($sid, array($user1->id));
        // Set cancellation note to "Blue" for user 1.
        $user1signup = facetoface_get_attendee($session->id, $user1->id);
        $customdata = new stdClass;
        $customdata->id = $user1signup->submissionid;
        $customdata->customfield_cancellationnote = '';
        customfield_save_data($customdata, 'facetofacecancellation', 'facetoface_cancellation');

        // OK all data is correct and as we expect, now revert it so that it appears as though we've got a history like
        // we did prior to 2.7.14 and 2.9.6.
        // Because the API has been changed to fix this issue we must replicate what we previously saw by directly inserting
        // data in the custom field data tables.
        $DB->delete_records('facetoface_signup_info_data');

        // Create signup note data for user 1 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they got signed up and then approved by their manager.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user1signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user1signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(8, $user1signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Elephants'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_cancellation_info_data', array('facetofacecancellationid' => next($user1signupids)->id, 'fieldid' => $cancellationfieldid, 'data' => 'Blue'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Rockets'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user1signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_cancellation_info_data', array('facetofacecancellationid' => next($user1signupids)->id, 'fieldid' => $cancellationfieldid, 'data' => ''));

        // Create signup note data for user 2 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they signed up but have not been approved.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user2signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user2signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(4, $user2signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user2signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Monkeys'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user2signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_cancellation_info_data', array('facetofacecancellationid' => next($user2signupids)->id, 'fieldid' => $cancellationfieldid, 'data' => 'Red'));

        // Create signup note data for user 3 against their signup status record. We need to check it gets correctly reattached to
        // their signup record.
        // Like before we expect that they got signed up and then approved by their manager.
        // Please note ordering by ID is not correct, but as unit tests act so fast we can't tell by any other means what order things happened.
        $user3signupids = $DB->get_records('facetoface_signups_status', array('signupid' => $user3signup->submissionid), 'id ASC, superceded DESC, timecreated DESC');
        $this->assertCount(3, $user3signupids);
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => reset($user3signupids)->id, 'fieldid' => $signupfieldid, 'data' => 'Sharks'));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));
        $DB->insert_record('facetoface_signup_info_data', array('facetofacesignupid' => next($user3signupids)->id, 'fieldid' => $signupfieldid, 'data' => ''));

        // Run the upgrade helper function and ensure that we get the data we expect.
        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_signup_info_data', 'facetofacesignupid');
        mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_cancellation_info_data', 'facetofacecancellationid');

        // Now check that we have the signup and cancellation notes still in tack.
        // User 1 should have Elephants as signup and Blue as cancellation.
        $signupstatus = new stdClass();
        $signupstatus->id = $user1signup->submissionid;
        $user1data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertSame('', $user1data['signupnote']);
        $this->assertArrayNotHasKey('cancellationnote', $user1data);
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertSame('', $user1data['cancellationnote']);
        $this->assertArrayNotHasKey('signupnote', $user1data);

        // User 2 should have Monkeys as signup and Red as cancellation.
        $signupstatus = new stdClass();
        $signupstatus->id = $user2signup->submissionid;
        $user2data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertSame('', $user2data['signupnote']);
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertSame('Red', $user1data['cancellationnote']);
        $this->assertArrayNotHasKey('signupnote', $user1data);

        // User 1 should have Sharks as signup and should not have a cancellation note.
        $signupstatus = new stdClass();
        $signupstatus->id = $user3signup->submissionid;
        $user3data = customfield_get_data($signupstatus, 'facetoface_signup', 'facetofacesignup', false);
        $this->assertSame('', $user3data['signupnote']);
        $user1data = customfield_get_data($signupstatus, 'facetoface_cancellation', 'facetofacecancellation', false);
        $this->assertArrayNotHasKey('cancellationnote', $user1data);
        $this->assertArrayNotHasKey('signupnote', $user1data);
    }

    /**
     * Test Upgrade calendar search config settings to support new customfield search
     */
    public function test_mod_facetoface_calendar_search_config_upgrade() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        // We have to reset as we're going to be adding a config var when calling mod_facetoface_migrate_session_signup_customdata.
        $this->resetAfterTest();
        $this->preventResetByRollback();

        // Test config is empty.
        set_config('facetoface_calendarfilters', '');
        mod_facetoface_calendar_search_config_upgrade();
        $this->assertEmpty(get_config(null, 'facetoface_calendarfilters'));

        // No room specific values.
        set_config('facetoface_calendarfilters', '1,2,test');
        mod_facetoface_calendar_search_config_upgrade();
        $this->assertEquals('sess_1,sess_2,test', get_config(null, 'facetoface_calendarfilters'));

        // Test config have both room specific values.
        set_config('facetoface_calendarfilters', 'building,room,address');
        mod_facetoface_calendar_search_config_upgrade();
        $this->assertEquals('room_2,room_1', get_config(null, 'facetoface_calendarfilters'));

        // Test config have only one room specific value and confirm that function is indempotent.
        set_config('facetoface_calendarfilters', 'test,room,1');
        mod_facetoface_calendar_search_config_upgrade();
        mod_facetoface_calendar_search_config_upgrade();
        $this->assertEquals('test,room_1,sess_1', get_config(null, 'facetoface_calendarfilters'));
    }

    public function test_mod_facetoface_delete_orphaned_customfield_data_signup() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
      
        $this->resetAfterTest();
        $this->preventResetByRollback();

        // Get some generators, and generate some data.
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();

        $f2f1 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_none'));
        $s1id = $facetofacegenerator->add_session(array('facetoface' => $f2f1->id, 'sessiondates' => array()));
        $session1 = facetoface_get_session($s1id);

        $f2f2 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_none'));
        $s2id = $facetofacegenerator->add_session(array('facetoface' => $f2f2->id, 'sessiondates' => array()));
        $session2 = facetoface_get_session($s2id);

        $signupfieldid = $DB->get_field('facetoface_signup_info_field', 'id', array('shortname' => 'signupnote'));
        $cancellationfieldid = $DB->get_field('facetoface_cancellation_info_field', 'id', array('shortname' => 'cancellationnote'));

        // Sign user 1 up to session 1.
        $result = facetoface_user_import($course, $f2f1, $session1, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u1s1 = facetoface_get_attendee($session1->id, $user1->id);
        // Now set up the custom data.
        $customdata = new stdClass;
        $customdata->id = $signup_u1s1->submissionid;
        $customdata->customfield_signupnote = 'Apples';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 2 up to session 1.
        $result = facetoface_user_import($course, $f2f1, $session1, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u2s1 = facetoface_get_attendee($session1->id, $user2->id);
        // Now set up the custom data.
        $customdata = new stdClass;
        $customdata->id = $signup_u2s1->submissionid;
        $customdata->customfield_signupnote = 'Oranges';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 1 up to session 2.
        $result = facetoface_user_import($course, $f2f2, $session2, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u1s2 = facetoface_get_attendee($session2->id, $user1->id);
        // Now set up the custom data.
        $customdata = new stdClass;
        $customdata->id = $signup_u1s2->submissionid;
        $customdata->customfield_signupnote = 'Mangoes';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 2 up to session 2.
        $result = facetoface_user_import($course, $f2f2, $session2, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u2s2 = facetoface_get_attendee($session2->id, $user2->id);
        // Now set up the custom data.
        $customdata = new stdClass;
        $customdata->id = $signup_u2s2->submissionid;
        $customdata->customfield_signupnote = 'Bananas';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Now manually delete the signups for s1u1 and s2u2 to orphan the custom fields.
        $DB->delete_records('facetoface_signups', array('userid' => $user1->id, 'sessionid' => $session1->id));
        $DB->delete_records('facetoface_signups', array('userid' => $user2->id, 'sessionid' => $session2->id));

        // Now do some pre-checks for sanity/comparison.
        $this->assertEquals(4, $DB->count_records('facetoface_signup_info_data'));
        $this->assertNotEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u1s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u2s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u1s2->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u2s2->submissionid)));

        mod_facetoface_delete_orphaned_customfield_data('signup');

        $this->assertEquals(2, $DB->count_records('facetoface_signup_info_data'));

        $this->assertEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u1s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u2s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u1s2->submissionid)));
        $this->assertEmpty($DB->get_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup_u2s2->submissionid)));
    }

    public function test_mod_facetoface_delete_orphaned_customfield_data_cancellation() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        $this->resetAfterTest();
        $this->preventResetByRollback();

        // Get some generators, and generate some data.
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();

        $f2f1 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_none'));
        $s1id = $facetofacegenerator->add_session(array('facetoface' => $f2f1->id, 'sessiondates' => array()));
        $session1 = facetoface_get_session($s1id);

        $f2f2 = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'approvaloptions' => 'approval_none'));
        $s2id = $facetofacegenerator->add_session(array('facetoface' => $f2f2->id, 'sessiondates' => array()));
        $session2 = facetoface_get_session($s2id);

        $signupfieldid = $DB->get_field('facetoface_signup_info_field', 'id', array('shortname' => 'signupnote'));
        $cancellationfieldid = $DB->get_field('facetoface_cancellation_info_field', 'id', array('shortname' => 'cancellationnote'));

        // Sign user 1 up to session 1.
        $result = facetoface_user_import($course, $f2f1, $session1, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u1s1 = facetoface_get_attendee($session1->id, $user1->id);
        // Now set up the custom data - to make sure it isn't affected.
        $customdata = new stdClass;
        $customdata->id = $signup_u1s1->submissionid;
        $customdata->customfield_signupnote = 'Apples';
        customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');

        // Sign user 2 up to session 1.
        $result = facetoface_user_import($course, $f2f1, $session1, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u2s1 = facetoface_get_attendee($session1->id, $user2->id);

        // Sign user 1 up to session 2.
        $result = facetoface_user_import($course, $f2f2, $session2, $user1->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u1s2 = facetoface_get_attendee($session2->id, $user1->id);

        // Sign user 2 up to session 2.
        $result = facetoface_user_import($course, $f2f2, $session2, $user2->id, array(
            'discountcode' => '',
            'notificationtype' => MDL_F2F_NONE,
        ));
        $this->assertTrue($result['result']);
        $signup_u2s2 = facetoface_get_attendee($session2->id, $user2->id);

        // Cancel all the users and set up some cancellation custom fields.
        $error = null;
        facetoface_user_cancel($session1, $user1->id, true, $error, 'Rabbits');
        facetoface_user_cancel($session1, $user2->id, true, $error, 'Kittens');
        facetoface_user_cancel($session2, $user1->id, true, $error, 'Puppies');
        facetoface_user_cancel($session2, $user2->id, true, $error, 'Squirrels');

        // Now manually delete the signups for s1u1 and s2u2 to orphan the custom fields.
        $DB->delete_records('facetoface_signups', array('userid' => $user1->id, 'sessionid' => $session1->id));
        $DB->delete_records('facetoface_signups', array('userid' => $user2->id, 'sessionid' => $session2->id));

        // Now do some pre-checks for sanity/comparison.
        $this->assertEquals(4, $DB->count_records('facetoface_cancellation_info_data'));
        $this->assertEquals(1, $DB->count_records('facetoface_signup_info_data'));
        $this->assertNotEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u1s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u2s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u1s2->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u2s2->submissionid)));

        mod_facetoface_delete_orphaned_customfield_data('cancellation');

        $this->assertEquals(2, $DB->count_records('facetoface_cancellation_info_data'));
        $this->assertEquals(1, $DB->count_records('facetoface_signup_info_data'));

        $this->assertEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u1s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u2s1->submissionid)));
        $this->assertNotEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u1s2->submissionid)));
        $this->assertEmpty($DB->get_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup_u2s2->submissionid)));
    }

    /**
     * Test that filearea correctly replaced/copied after field rename
     */
    public function test_mod_facetoface_fix_cancellationid_files() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        $this->resetAfterTest();
        $this->preventResetByRollback();
        // Data
        $fixtures = [
            'facetoface_sessioncancel_info_field' =>
            [
                ['id' =>"1", 'shortname' => "evtcncl", 'datatype' => "text", 'sortorder' => 1, 'param1' => "30", 'param2' => "2048", 'fullname' =>"Event cancellation note", 'hidden'=> 0, 'locked' => 0, 'required' => 0, 'forceunique' => 0],
                ['id' =>"2", 'shortname' => "ecf", 'datatype' => "file", 'sortorder' => 2, 'fullname' =>"Event cancel file", 'hidden'=> 0, 'locked' => 0, 'required' => 0, 'forceunique' => 0],
                ['id' =>"3", 'shortname' => "ecta", 'datatype' => "textarea", 'sortorder' => 3, 'param1' => "30", 'param2' => "10", 'fullname' =>"Event cancel text area", 'hidden'=> 0, 'locked' => 0, 'required' => 0, 'forceunique' => 0],

            ],
            'facetoface_sessioncancel_info_data' =>
            [
                ['id' => "7", 'data' => "Event cancel C1", 'fieldid' => 1, 'facetofacesessioncancelid' => 5],
                ['id' => "8", 'data' => "8", 'fieldid' => 2, 'facetofacesessioncancelid' => 5],
                ['id' => "9", 'data' => '<p>Event cancel textarea with image </p><p><img src="@@PLUGINFILE@@/bug.jpg" alt="" width="235" height="215" style="vertical-align:text-bottom;margin:0 .5em;" class="img-responsive" /><br /></p>', 'fieldid' => 3, 'facetofacesessioncancelid' => 5],
                ['id' => "10", 'data' => "Cancel seminar C5", 'fieldid' => 1, 'facetofacesessioncancelid' => 7],
                ['id' => "11", 'data' => "11", 'fieldid' => 2, 'facetofacesessioncancelid' => 7],
                ['id' => "12", 'data' => '<p>Cancel seminar C5</p><p><img src="@@PLUGINFILE@@/badger.jpg" alt="" width="480" height="360" style="vertical-align:text-bottom;margin:0 .5em;" class="img-responsive" /><br /></p>', 'fieldid' => 3, 'facetofacesessioncancelid' => 7]
            ],
            'facetoface_cancellation_info_field' =>
            [
                // Cancellation note is already created.
                ['id' =>"2", 'shortname' => "ucf", 'datatype' => "file", 'sortorder' => 2, 'fullname' =>"User cancel file", 'hidden'=> 0, 'locked' => 0, 'required' => 0, 'forceunique' => 0],
                ['id' =>"3", 'shortname' => "ucta", 'datatype' => "textarea", 'sortorder' => 3, 'param1' => "30", 'param2' => "10", 'fullname' =>"User cancel text area", 'hidden'=> 0, 'locked' => 0, 'required' => 0, 'forceunique' => 0]
            ],
            'facetoface_cancellation_info_data' =>
            [
                ['id' => "7", 'data' => "User cancellation", 'fieldid' => 1, 'facetofacecancellationid' => 24],
                ['id' => "8", 'data' => "8", 'fieldid' => 2, 'facetofacecancellationid' => 24],
                ['id' => "9", 'data' => '<p>User cancellation</p><p><img src="@@PLUGINFILE@@/anzac_bw2.JPG" alt="" width="321" height="269" style="vertical-align:text-bottom;margin:0 .5em;" class="img-responsive" /><br /></p>', 'fieldid' => 3, 'facetofacecancellationid' => 24],
                ['id' => "13", 'data' => "Cancel learner11", 'fieldid' => 1, 'facetofacecancellationid' => 20],
                ['id' => "14", 'data' => "14", 'fieldid' => 2, 'facetofacecancellationid' => 20],
                ['id' => "15", 'data' => '<p>Cancel learner11</p><p><img src="@@PLUGINFILE@@/the_tiger__s_anger_by_mvlaniel.jpg" alt="" width="320" height="322" style="vertical-align:text-bottom;margin:0 .5em;" class="img-responsive" /><br /></p>', 'fieldid' => 3, 'facetofacecancellationid' => 20],
            ],
            'files' =>
            [
                ['id' => "97", 'contenthash' => "da39a3ee5e6b4b0d3255bfef95601890afd80709", 'pathnamehash' =>"80e846495322259771d21cb9de12592eae65cf6c",
                 'contextid' => 5, 'component' => "user", 'filearea' =>"draft", 'itemid' => 606962697, 'filepath' => "/",
                 'filename' => ".", 'userid' => 2, 'filesize' => 0, 'mimetype' => "", 'source' => "",
                 'timecreated' => 1474414106, 'timemodified' => 1474414106],
                ['id' => "108", 'contenthash' => "da39a3ee5e6b4b0d3255bfef95601890afd80709", 'pathnamehash' =>"884555719c50529b9df662a38619d04b5b11e25c",
                 'contextid' => 1, 'component' => "core", 'filearea' =>"preview", 'itemid' => 0, 'filepath' => "/",
                 'filename' => ".", 'userid' => 2, 'filesize' => 0, 'mimetype' => "document/unknown", 'source' => "",
                 'timecreated' => 1474415277, 'timemodified' => 1474415277],
                ['id' => "111", 'contenthash' => "da3e9ddd0df9b8fe4c36dad2ca1cbcbf359030a1", 'pathnamehash' =>"e55985d0cd6ce803d6a209d0199594b96cec0aa9",
                 'contextid' => 1, 'component' => "shezar_customfield", 'filearea' =>"facetofacecancellation_filemgr", 'itemid' => 8, 'filepath' => "/",
                 'filename' => "anzac_bw.JPG", 'userid' => 2, 'filesize' => 20254, 'mimetype' => "image/jpeg", 'source' => "anzac_bw.JPG",
                 'timecreated' => 1474415277, 'timemodified' => 1474415301],
                ['id' => "112", 'contenthash' => "3fcb05b2dddb6f1af2c3791718dfb221f78977bb", 'pathnamehash' =>"754443025c90eca5c96db9c2b8b39c6087ea18d0",
                 'contextid' => 1, 'component' => "shezar_customfield", 'filearea' =>"facetofacecancellation", 'itemid' => 9, 'filepath' => "/",
                 'filename' => "anzac_bw2.JPG", 'userid' => 2, 'filesize' => 88430, 'mimetype' => "image/jpeg", 'source' => "anzac_bw2.JPG",
                 'timecreated' => 1474415290, 'timemodified' => 1474415301],
                ['id' => "126", 'contenthash' => "18f08f5d87af85260a7fc0dec0c7f27c4876827d", 'pathnamehash' =>"9866ceeefa52b870563f571a3ccf9cabcea56807",
                 'contextid' => 1, 'component' => "shezar_customfield", 'filearea' =>"facetofacecancellation_filemgr", 'itemid' => 14, 'filepath' => "/",
                 'filename' => "f2f_attendees_by_idnumber.csv", 'userid' => 2, 'filesize' => 58, 'mimetype' => "text/csv", 'source' => "f2f_attendees_by_idnumber.csv",
                 'timecreated' => 1474422576, 'timemodified' => 1474422616],
                ['id' => "128", 'contenthash' => "5d78ceb3a32bac12275c42e698a8801cd611afea", 'pathnamehash' =>"85d0d82e65ac6cc55b8b0f0488f81c2c708f70c6",
                 'contextid' => 1, 'component' => "shezar_customfield", 'filearea' =>"facetofacecancellation", 'itemid' => 15, 'filepath' => "/",
                 'filename' => "the_tiger.jpg", 'userid' => 2, 'filesize' => 1212759, 'mimetype' => "image/jpeg", 'source' => "the_tiger.jpg",
                 'timecreated' => 1474422606, 'timemodified' => 1474422616],
                ['id' => "134", 'contenthash' => "482a521ced502f4924f95d63af41956a41c3c05c", 'pathnamehash' =>"13a7e6b3fb24428c56a23550e80b96b740ab5742",
                 'contextid' => 1, 'component' => "shezar_customfield", 'filearea' =>"facetofacecancellation_filemgr", 'itemid' => 11, 'filepath' => "/",
                 'filename' => "f2f_attendees_with_ja.csv", 'userid' => 2, 'filesize' => 89, 'mimetype' => "text/csv", 'source' => "f2f_attendees_with_ja.csv",
                 'timecreated' => 1474422986, 'timemodified' => 1474423018],
                ['id' => "136", 'contenthash' => "8cf652f8f7e4757654ea9d190e802a98bacafaec", 'pathnamehash' =>"bab770c76a1d842ee97a6d4faa938d2d2b92aad8",
                 'contextid' => 1, 'component' => "shezar_customfield", 'filearea' =>"facetofacecancellation", 'itemid' => 12, 'filepath' => "/",
                 'filename' => "badger.jpg", 'userid' => 2, 'filesize' => 14418, 'mimetype' => "image/jpeg", 'source' => "badger.jpg",
                 'timecreated' => 1474423014, 'timemodified' => 1474423018],
            ]
        ];

        $DB->delete_records('files');
        // Prepare data.
        foreach ($fixtures as $table => $rows) {
            foreach ($rows as $row) {
                $DB->insert_record_raw($table, $row, true, false, true);
            }
        }

        // Perform upgrade.
        mod_facetoface_fix_cancellationid_files();

        // Check.
        $files = $DB->get_records('files');
        // 14 = 8 initial + 4 copied (facetoface_sessioncancel_info_data.id IN (8,9,11,12)) + 4 root (".") created for each copy - 2 removed from user cancel (other 2 are conflicting, so should stay).
        $this->assertCount(14, $files);

        $this->assertArrayHasKey(97, $files);
        $this->assertArrayHasKey(108, $files);
        $this->assertArrayHasKey(111, $files);
        $this->assertArrayHasKey(112, $files);
        $this->assertArrayHasKey(126, $files);
        $this->assertArrayHasKey(128, $files);
        $this->assertArrayNotHasKey(134, $files);
        $this->assertArrayNotHasKey(136, $files);
    }

    /**
     * Test fixes of notification titles.
     */
    public function test_mod_facetoface_upgrade_notification_titles() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        $this->resetAfterTest();
        $this->preventResetByRollback();


        // Old title.
        $toupgrade = $DB->get_record('facetoface_notification_tpl', ['reference' => 'trainerconfirm']);
        $toupgrade->title = 'Face-to-face trainer confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]';
        $DB->update_record('facetoface_notification_tpl', $toupgrade);

        $toskip = $DB->get_record('facetoface_notification_tpl', ['reference' => 'reminder']);
        $toskip->title = 'Should be skipped';
        $DB->update_record('facetoface_notification_tpl', $toskip);

        // Create course and f2f activity.
        $facetoface_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $facetoface_generator->create_instance(array('course' => $course->id));

        mod_facetoface_upgrade_notification_titles();

        // Confirm upgraded.
        $isupgraded = $DB->get_record('facetoface_notification_tpl', ['reference' => 'trainerconfirm']);
        $this->assertEquals('Seminar trainer confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]', $isupgraded->title);

        $isupgradedactivity = $DB->get_records('facetoface_notification', ['templateid' => $isupgraded->id]);
        foreach ($isupgradedactivity as $notification) {
            $this->assertEquals('Seminar trainer confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]', $notification->title);
        }

        // Confirm skipped.
        $isskipped = $DB->get_record('facetoface_notification_tpl', ['reference' => 'reminder']);
        $this->assertEquals('Should be skipped', $isskipped->title);

        $isskippedactivity = $DB->get_records('facetoface_notification', ['templateid' => $isskipped->id]);
        foreach ($isskippedactivity as $notification) {
            $this->assertEquals('Should be skipped', $notification->title);
        }
    }

    /**
     * Test of fix trainercancel body incorrect upgrade by 9.0rc1
     */
    public function test_mod_facetoface_fix_trainercancel_body() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/upgradelib.php');
        require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

        $this->resetAfterTest();
        $this->preventResetByRollback();

        $wrongtrainercancel = $DB->get_record('facetoface_notification_tpl', ['reference' => 'trainercancel']);
        $wrongtrainercancel->body = '<div class="text_to_html">This is to advise that your assigned training session the following course has been cancelled:<br />
<br />
***SESSION CANCELLED***<br />
<br />
Participant:   [firstname] [lastname]<br />
Course:   [coursename]<br />
Face-to-face:   [facetofacename]<br />
<br />
Duration:   [duration]<br />
Date(s):<br />
[#sessions][session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]<br>[/sessions]<br />
<br />
Location:   [session:location]<br />
Venue:   [session:venue]<br />
Room:   [session:room]<br />
</div>';
        $DB->update_record('facetoface_notification_tpl', $wrongtrainercancel);

        mod_facetoface_fix_trainercancel_body();

        $correctmessage = text_to_html(get_string('setting:defaulttrainersessioncancellationmessagedefault_v9', 'facetoface'));
        $trainercancel = $DB->get_record('facetoface_notification_tpl', ['reference' => 'trainercancel']);
        $this->assertEquals($correctmessage, $trainercancel->body);

        // Idempotence for pre-9 upgrades.
        mod_facetoface_fix_trainercancel_body();

        $trainercancel2 = $DB->get_record('facetoface_notification_tpl', ['reference' => 'trainercancel']);
        $this->assertEquals($correctmessage, $trainercancel2->body);
    }
}
