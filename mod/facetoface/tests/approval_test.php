<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * facetoface module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit mod_facetoface_notifications_testcase mod/facetoface/tests/notifications_test.php
 *
 * @author     David Curry <david.curry@shezarlms.com>
 * @package    mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/mod_form.php');
require_once($CFG->dirroot . '/shezar/hierarchy/prefix/position/lib.php');

class mod_facetoface_approvals_testcase extends advanced_testcase {

    /**
     * Intercept emails and stores them locally for later verification.
     */
    private $emailsink = null;


    /**
     * Original configuration value to enable sending emails.
     */
    private $cfgemail = null;

    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->preventResetByRollback();
        $this->resetAfterTest();

        $this->emailsink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        $this->cfgemail = isset($CFG->noemailever) ? $CFG->noemailever : null;
        $CFG->noemailever = false;
    }

    /**
     * Check that approvers list is validated correctly
     */
    public function test_admin_approvers_validation() {
        global $DB;
        $guest = guest_user()->id;
        $user1 = $this->getDataGenerator()->create_user()->id;
        $user2 = $this->getDataGenerator()->create_user()->id;
        $inactive = $this->getDataGenerator()->create_user(array('suspended' => 1))->id;
        $deleted = $this->getDataGenerator()->create_user(array('deleted' => 1))->id;
        $course = $this->getDataGenerator()->create_course();
        $nonadmin = $this->getDataGenerator()->create_user()->id;
        $admin = $this->getDataGenerator()->create_user()->id;

        set_config('facetoface_adminapprovers', "$nonadmin,$admin");
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        role_assign($managerrole->id, $admin, context_system::instance());
        assign_capability('mod/facetoface:approveanyrequest', CAP_ALLOW, $managerrole->id, context_system::instance());

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course->id));
        $cm = get_coursemodule_from_id('facetoface', $facetoface->cmid, $course->id, true, MUST_EXIST);

        $form = new mod_facetoface_mod_form($cm, 0, $cm, $course);
        $mockdata = array(
            'name' => 'test',
            'modulename' => 'facetoface',
            'instance' => $cm->instance,
            'coursemodule' => $cm->id,
            'cmidnumber' => $cm->idnumber
        );
        // Many errors.
        $mockdata['selectedapprovers'] = "$user1,$user2,$inactive,$user2,$admin,$guest,$deleted";
        $errors = $form->validation($mockdata, array());
        $this->assertNotEmpty($errors['approvaloptions']);

        // Duplicate.
        $mockdata['selectedapprovers'] = "$user1,$user2,$user1";
        $errors = $form->validation($mockdata, array());
        $this->assertNotEmpty($errors['approvaloptions']);

        // Admin.
        $mockdata['selectedapprovers'] = "$user1,$user2,$admin";
        $errors = $form->validation($mockdata, array());
        $this->assertNotEmpty($errors['approvaloptions']);

        // Guest.
        $mockdata['selectedapprovers'] = "$user1,$guest,$user2";
        $errors = $form->validation($mockdata, array());
        $this->assertNotEmpty($errors['approvaloptions']);

        // Deleted.
        $mockdata['selectedapprovers'] = "$deleted,$user1,$user2";
        $errors = $form->validation($mockdata, array());
        $this->assertNotEmpty($errors['approvaloptions']);

        // Inactive.
        $mockdata['selectedapprovers'] = "$user1,$user2,$inactive";
        $errors = $form->validation($mockdata, array());
        $this->assertNotEmpty($errors['approvaloptions']);

        // Ok.
        $mockdata['selectedapprovers'] = "$user1,$user2,$nonadmin";
        $errors = $form->validation($mockdata, array());
        $this->assertArrayNotHasKey('approvaloptions', $errors);
    }

    // TODO - manager, role, admin notification checks
    public function test_cancellation_send_delete_session() {
/*
        $session = $this->f2f_generate_data();

        // Call facetoface_delete_session function for session1.
        $this->emailsink = $this->redirectEmails();
        facetoface_delete_session($session);
        $this->emailsink->close();

        $emails = $this->get_emails();
        $this->assertCount(4, $emails, 'Wrong no of cancellation notifications sent out.');
 */
    }
}
