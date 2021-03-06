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
 * @author  David Curry <david.curry@shezarlearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_session_registration_closure_testcase extends advanced_testcase {

    private $cfgemail;
    private $eventsink;
    private $emailsink;

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->cfgemail = isset($CFG->noemailever) ? $CFG->noemailever : null;
        $CFG->noemailever = false;

        $this->resetAfterTest();
        $this->setAdminUser();

        $this->eventsink = $this->redirectEvents();
        $this->emailsink = $this->redirectMessages();
    }

    public function tearDown() {
        global $CFG;

        parent::tearDown();

        if (isset($this->cfgemail)) {
            $CFG->noemailever = $this->cfgemail;
            unset($this->cfgemail);
        }

        $this->eventsink->close();
        $this->emailsink->close();
    }

    public function test_facetoface_session_registration_closure() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/facetoface/lib.php');

        $now = time();

        $generator = $this->getDataGenerator();
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $manager = $generator->create_user();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $managerja = \shezar_job\job_assignment::create_default($manager->id);
        \shezar_job\job_assignment::create_default($user1->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user3->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($user4->id, array('managerjaid' => $managerja->id));

        $course = $generator->create_course();
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course->id));

        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $session->sessiondates = array($sessiondate);
        $session->registrationtimestart = $now - 2000;
        $session->registrationtimefinish = $now + 2000;
        $sessionid = $facetofacegenerator->add_session($session);

        $session = facetoface_get_session($sessionid);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_REQUESTED, $user1->id, false);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_REQUESTEDADMIN, $user2->id, false);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_APPROVED, $user3->id, false);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NONE, MDL_F2F_STATUS_APPROVED, $user4->id, false);
        facetoface_cancel_attendees($session->id, array($user4->id));

        // Clear any events/messages caused by the signups.
        $this->eventsink->clear();
        $this->emailsink->clear();

        // Move the registration finish time into the past.
        $DB->execute('UPDATE {facetoface_sessions} SET registrationtimefinish = (registrationtimefinish - 3000)');

        $cron = new \mod_facetoface\task\close_registrations_task();
        $cron->testing = true;
        $cron->execute();

        // Check that users 1 & 2 are no longer pending but are declined.
        $closures = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_DECLINED));
        $this->assertEquals(2, count($closures));
        foreach ($closures as $closure) {
            $expected = false;

            // Make sure the denied users are the ones that were pending earlier.
            if ($closure->id == $user1->id || $closure->id == $user2->id) {
                $expected = true;
            }

            $this->assertTrue($expected);
        }

        // And just double check there are no pending requests.
        $requests = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_REQUESTEDADMIN));
        $this->assertEquals(0, count($requests));

        // There should be 2 status changed events.
        $events = $this->eventsink->get_events();
        $this->assertEquals(2, count($events));
        foreach ($events as $event) {
            $status = $event->get_signupstatus();
            $expected = false;
            $userid = $event->other['userid'];

            // Make sure the denied users are the ones that were pending earlier.
            if ($userid == $user1->id || $userid == $user2->id) {
                $expected = true;
            }

            $this->assertTrue($expected);
            $this->assertEquals(MDL_F2F_STATUS_DECLINED, $status->statuscode);
            $this->assertInstanceOf('\mod_facetoface\event\signup_status_updated', $event);
        }

        // Check the registration closure messages.
        $emails = $this->emailsink->get_messages();
        $this->assertEquals(4, count($emails));
        $subject = get_string('setting:defaultpendingreqclosuresubjectdefault', 'mod_facetoface');
        foreach ($emails as $email) {
            $expected = false;
            $userid = $email->useridto;

            // Make sure the denied users are the ones that were pending earlier.
            if ($userid == $user1->id || $userid == $user2->id || $userid == $manager->id) {
                $expected = true;
            }

            $this->assertTrue($expected);
            $this->assertEquals($subject, $email->subject);
        }
    }
}
