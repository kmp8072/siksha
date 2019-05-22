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
 * @package    mod_facetoface
 * @subpackage phpunit
 * @author     Oleg Demeshev <oleg.demeshev@shezarlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/shezar/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot . '/shezar/customfield/field/datetime/define.class.php');
require_once($CFG->dirroot . '/shezar/customfield/field/datetime/field.class.php');

class mod_facetoface_notifications_testcase extends advanced_testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp() {
        parent::setUp();
        $this->preventResetByRollback();
        $this->resetAfterTest();
    }

    public function test_cancellation_send_delete_session() {

        $session = $this->f2f_generate_data();

        // Call facetoface_delete_session function for session1.
        $emailsink = $this->redirectEmails();
        facetoface_delete_session($session);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(4, $emails, 'Wrong no of cancellation notifications sent out.');
    }

    public function test_cancellation_nonesend_delete_session() {

        $session = $this->f2f_generate_data(false);

        // Call facetoface_delete_session function for session1.
        $emailsink = $this->redirectEmails();
        facetoface_delete_session($session);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(0, $emails, 'Error: cancellation notifications should not be sent out.');
    }

    /**
     * Create course, users, face-to-face, session
     *
     * @param bool $future, time status: future or past, to test cancellation notifications
     * @return object $session
     */
    private function f2f_generate_data($future = true) {
        global $DB;

        $this->setAdminUser();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $managerja = \shezar_job\job_assignment::create_default($manager->id);
        \shezar_job\job_assignment::create_default($student1->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($student2->id, array('managerjaid' => $managerja->id));

        $course = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        if ($future) {
            $sessiondate->timestart = time() + WEEKSECS;
            $sessiondate->timefinish = time() + WEEKSECS + 60;
        } else {
            $sessiondate->timestart = time() - WEEKSECS;
            $sessiondate->timefinish = time() - WEEKSECS + 60;
        }
        $sessiondate->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);
        $session->sessiondates = facetoface_get_session_dates($session->id);

        $discountcode = 'GET15OFF';
        $notificationtype = 1;
        $statuscode = MDL_F2F_STATUS_REQUESTED;

        // Signup user1.
        $emailsink = $this->redirectEmails();
        $this->setUser($student1);
        facetoface_user_signup($session, $facetoface, $course, $discountcode, $notificationtype, $statuscode);
        $emailsink->close();

        // Signup user2.
        $emailsink = $this->redirectEmails();
        $this->setUser($student2);
        facetoface_user_signup($session, $facetoface, $course, $discountcode, $notificationtype, $statuscode);
        $emailsink->close();

        return $session;
    }

    /**
     * @return array of timestamps for use in testing.
     */
    private function create_array_of_times() {
        $times = array(
            'start1' => time() + 1 * DAYSECS,
            'end1' => time() + 1 * DAYSECS + 2 * HOURSECS,
            'other1' => time() + 5 * DAYSECS,
            'start2' => time() + 3 * DAYSECS + 30 * MINSECS,
            'end2' => time() + 4 * DAYSECS + 6 * HOURSECS,
            'other2' => time() - 4 * DAYSECS
        );
        if (date('G', $times['other1']) == 0) {
            $times['other1'] += 1; // Otherwise a different display format will be used for customfield_datetime.
        }
        if (date('G', $times['other2']) == 0) {
            $times['other2'] += 1; // Otherwise a different display format will be used for customfield_datetime.
        }

        return $times;
    }

    /**
     * Ensure that ical attachment is updated properly when session dates or sign up status changes
     */
    public function test_ical_generation() {
        global $DB;
        $this->resetAfterTest(true);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $room = $facetofacegenerator->add_site_wide_room(array('name' => 'Site x 1'));
        $room->customfield_locationaddress = "Address\nTest\nTest2";
        customfield_save_data($room, 'facetofaceroom', 'facetoface_room');

        $date1 = new stdClass();
        $date1->sessiontimezone = 'Pacific/Auckland';
        $date1->timestart = time() + WEEKSECS;
        $date1->timefinish = time() + WEEKSECS + 3600;
        $date1->roomid = $room->id;

        $date2 = new stdClass();
        $date2->sessiontimezone = 'Pacific/Auckland';
        $date2->timestart = time() + WEEKSECS + DAYSECS;
        $date2->timefinish = time() + WEEKSECS + DAYSECS + 3600;

        $sessiondates = array($date1, $date2);

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => $sessiondates,
            'datetimeknown' => '1',
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $session = facetoface_get_session($facetofacegenerator->add_session($sessiondata));

        $init = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1);
        $initlocation = $this->get_ical_values($init->content, 'LOCATION');
        $inituids = $this->get_ical_values($init->content, 'UID');
        $initseqs = $this->get_ical_values($init->content, 'SEQUENCE');
        $this->assertEquals('Site x 1\, Address, Test, Test2', $initlocation[0]);
        $this->assertNotEquals($inituids[0], $inituids[1]);

        $initother = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student2);
        $otheruids = $this->get_ical_values($initother->content, 'UID');
        $otherseqs = $this->get_ical_values($initother->content, 'SEQUENCE');
        $this->assertNotEquals($inituids[0], $otheruids[0]);
        $this->assertNotEquals($inituids[0], $otheruids[1]);
        $this->assertNotEquals($inituids[1], $otheruids[0]);
        $this->assertNotEquals($inituids[1], $otheruids[1]);

        $this->mock_status_change($student2->id, $session->id);
        $cancelother = facetoface_get_ical_attachment(MDL_F2F_CANCEL, $facetoface, $session, $student2);
        $cancelotheruids = $this->get_ical_values($cancelother->content, 'UID');
        $cancelotherseqs = $this->get_ical_values($cancelother->content, 'SEQUENCE');
        $cancelstatus = $this->get_ical_values($cancelother->content, "STATUS");
        $this->assertEquals($cancelotheruids[0], $otheruids[0]);
        $this->assertEquals($cancelotheruids[1], $otheruids[1]);
        $this->assertEquals('CANCELLED', $cancelstatus[0]);
        $this->assertEquals('CANCELLED', $cancelstatus[1]);
        $this->assertGreaterThan($otherseqs[0], $cancelotherseqs[0]);
        $this->assertGreaterThan($otherseqs[1], $cancelotherseqs[1]);

        $session->sessiondates[1]->id++;
        $updatedate = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1);
        $updatedateuids = $this->get_ical_values($updatedate->content, 'UID');
        $updatedateseqs = $this->get_ical_values($updatedate->content, 'SEQUENCE');
        $this->assertEquals($updatedateuids[0], $inituids[0]);
        $this->assertEquals($updatedateuids[1], $inituids[1]);
        $this->assertGreaterThanOrEqual($initseqs[0], $updatedateseqs[0]); // This date was not changed.
        $this->assertGreaterThan($initseqs[1], $updatedateseqs[1]);

        $this->mock_status_change($student1->id, $session->id);
        $updatestatus = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1);
        $updatestatusuids = $this->get_ical_values($updatestatus->content, 'UID');
        $updatestatusseqs = $this->get_ical_values($updatestatus->content, 'SEQUENCE');
        $this->assertEquals($updatestatusuids[0], $inituids[0]);
        $this->assertEquals($updatestatusuids[1], $inituids[1]);
        $this->assertGreaterThan($updatedateseqs[0], $updatestatusseqs[0]);
        $this->assertGreaterThan($updatedateseqs[1], $updatestatusseqs[1]);

        $olddates = $session->sessiondates;
        array_shift($session->sessiondates);
        $removedate = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1, $olddates);
        $removedateuids = $this->get_ical_values($removedate->content, 'UID');
        $removedateseqs = $this->get_ical_values($removedate->content, 'SEQUENCE');
        $removedatestatus = $this->get_ical_values($removedate->content, 'STATUS');
        $this->assertEquals($removedateuids[0], $inituids[0]);
        $this->assertEquals($removedateuids[1], $inituids[1]);
        $this->assertEquals('CANCELLED', $removedatestatus[0]);
        $this->assertArrayNotHasKey(1, $removedatestatus);
        $this->assertGreaterThan($updatestatusseqs[0], $removedateseqs[0]);
        $this->assertGreaterThanOrEqual($updatestatusseqs[1], $removedateseqs[1]);

        $session->sessiondates[0]->id++;
        $updateafter = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1);
        $updateafteruids = $this->get_ical_values($updateafter->content, 'UID');
        $updateafterseqs = $this->get_ical_values($updateafter->content, 'SEQUENCE');
        $this->assertEquals($updateafteruids[0], $inituids[0]);
        $this->assertArrayNotHasKey(1, $updateafteruids);
        $this->assertGreaterThan($removedateseqs[0], $updateafterseqs[0]);
        $this->assertArrayNotHasKey(1, $updateafterseqs);
    }

    /**
     * Test sending notifications when "facetoface_oneemailperday" is enabled
     */
    public function test_oneperday_ical_generation() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('facetoface_oneemailperday', true);

        $student1 = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $date1 = new stdClass();
        $date1->sessiontimezone = 'Pacific/Auckland';
        $date1->timestart = time() + WEEKSECS;
        $date1->timefinish = time() + WEEKSECS + 3600;

        $date2 = new stdClass();
        $date2->sessiontimezone = 'Pacific/Auckland';
        $date2->timestart = time() + WEEKSECS + DAYSECS;
        $date2->timefinish = time() + WEEKSECS + DAYSECS + 3600;

        $sessiondates = array($date1, $date2);

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => $sessiondates,
            'datetimeknown' => '1',
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $session = facetoface_get_session($facetofacegenerator->add_session($sessiondata));
        $sessionolddates = facetoface_get_session_dates($session->id);

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $preemails = $emailsink->get_messages();
        foreach($preemails as $preemail) {
            $this->assertContains("This is to confirm that you are now booked", $preemail->body);
        }

        // Get ical specifics.
        $before0 = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1, array(), 0);
        $before1 = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1, array(), 1);

        $date1edit = new stdClass();
        $date1edit->sessiontimezone = 'Pacific/Auckland';
        $date1edit->timestart = time() + 2 * WEEKSECS;
        $date1edit->timefinish = time() + 2 * WEEKSECS + 3600;

        $emailsink = $this->redirectEmails();
        // Change one date and cancel second.
        facetoface_update_session($session, array($date1edit));
        // Refresh session data.
        $session = facetoface_get_session($session->id);

        // Send message.
        facetoface_send_datetime_change_notice($facetoface, $session, $student1->id, $sessionolddates);
        $emailsink->close();
        $after0 = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1, $sessionolddates, 0);
        $after1 = facetoface_get_ical_attachment(MDL_F2F_INVITE, $facetoface, $session, $student1, $sessionolddates, 1);

        $emails = $emailsink->get_messages();
        $emailsink->close();
        $this->assertContains("Your session has changed", $emails[0]->body);
        $this->assertContains("BOOKING CANCELLED", $emails[1]->body);

        // Check ical specifics.
        $before0ids = $this->get_ical_values($before0->content, 'UID');
        $before0seqs = $this->get_ical_values($before0->content, 'SEQUENCE');
        $before1ids = $this->get_ical_values($before1->content, 'UID');
        $before1seqs = $this->get_ical_values($before1->content, 'SEQUENCE');
        $after0ids = $this->get_ical_values($after0->content, 'UID');
        $after0seqs = $this->get_ical_values($after0->content, 'SEQUENCE');
        $after1ids = $this->get_ical_values($after1->content, 'UID');
        $after1seqs = $this->get_ical_values($after1->content, 'SEQUENCE');

        $this->assertCount(1, $before0ids);
        $this->assertCount(1, $after0ids);
        $this->assertCount(1, $before0seqs);
        $this->assertCount(1, $after0seqs);
        $this->assertCount(1, $before1ids);
        $this->assertCount(1, $after1ids);
        $this->assertCount(1, $before1seqs);
        $this->assertCount(1, $after1seqs);
        $this->assertEquals($before0ids[0], $after0ids[0]);
        $this->assertEquals($before1ids[0], $after1ids[0]);
        $this->assertGreaterThanOrEqual($before0seqs[0], $after0seqs[0]);
        $this->assertGreaterThanOrEqual($before0seqs[0], $after1seqs[0]);

        // Now test cancelling the session.
        $emailsink = $this->redirectEmails();
        $result = facetoface_cancel_session(facetoface_get_session($session->id), null);
        $this->assertTrue($result);

        $messages = $emailsink->get_messages();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertContains('Seminar event cancellation', $message->subject);
        $this->assertContains('This is to advise that the following session has been cancelled', $message->body);
        $this->assertContains('Course: Test course 1', $message->body);
        $this->assertContains('Seminar: facetoface', $message->body);
        $this->assertContains('Date(s) and location(s):', $message->body);

        $session = facetoface_get_session($session->id);
        $this->assertEquals(1, $session->cancelledstatus);
    }

    /**
     * Simplified parse $ical content and return values of requested property
     * @param string $content
     * @param string $name
     * @return array of values
     */
    private function get_ical_values($content, $name) {
        $strings = explode("\n", $content);
        $result = array();
        foreach($strings as $string) {
            if (strpos($string, $name.':') === 0) {
                $result[] = trim(substr($string, strlen($name)+1));
            }
        }
        return $result;
    }

    /**
     * Add superceeded record to signup status to mock user status change
     * @param int $userid
     * @param int $sessionid
     */
    private function mock_status_change($userid, $sessionid) {
        global $DB;

        $signupid = $DB->get_field('facetoface_signups', 'id', array('userid' => $userid, 'sessionid' => $sessionid));
        if (!$signupid) {
            $signupmock = new stdClass();
            $signupmock->userid = $userid;
            $signupmock->sessionid = $sessionid;
            $signupmock->notificationtype = 3;
            $signupmock->bookedby = 2;
            $signupid = $DB->insert_record('facetoface_signups', $signupmock);
        }

        $mock = new stdClass();
        $mock->superceded = 1;
        $mock->statuscode = 0;
        $mock->signupid = $signupid;
        $mock->createdby = 2;
        $mock->timecreated = time();
        $DB->insert_record('facetoface_signups_status', $mock);
    }

    /**
     * Tests the facetoface_notification_loop_session_placeholders function alone, without relying on proper working
     * of functions for saving to and retrieving from database.
     */
    public function test_facetoface_notification_loop_session_placeholders() {
        $this->resetAfterTest(true);

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $msg = "Testing with non-saved session.[#sessions] Start time is [session:starttime]. Finish time is [session:finishtime].[/sessions] That is all.";
        $dataset['sessions'] = array();
        $session = new stdClass();
        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = $timezone;
        $sessiondate->timestart = $times['start1'];
        $sessiondate->timefinish = $times['end1'];
        $session->sessiondates = array($sessiondate);
        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);
        $expectedstart = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $expectedend = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $this->assertEquals("Testing with non-saved session. Start time is ".$expectedstart.". Finish time is ".$expectedend.". That is all.", $replacedmsg);
    }

    /**
     * Tests the facetoface_notification_loop_session_placeholders function alone, without relying on proper working
     * of functions for saving to and retrieving from database. In this case, there are two lots of tags.
     */
    public function test_facetoface_notification_loop_session_placeholders_double() {
        $this->resetAfterTest(true);

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $msg = "Testing with non-saved session.[#sessions]Start time is [session:starttime]. Finish time is [session:finishtime].\n[/sessions]";
        $msg .= "[#sessions]Start date is [session:startdate]. Finish date is [session:finishdate].\n[/sessions]";
        $msg .= "That is all.";
        $dataset['sessions'] = array();
        $session = new stdClass();
        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];;
        $sessiondate2->timefinish = $times['end2'];
        $session->sessiondates = array($sessiondate1, $sessiondate2);
        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);

        // Get strings for display of dates and times in email.
        $startdate1 = userdate($times['start1'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime1 = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate1 = userdate($times['end1'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime1 = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $startdate2 = userdate($times['start2'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime2 = userdate($times['start2'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate2 = userdate($times['end2'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime2 = userdate($times['end2'], get_string('strftimetime', 'langconfig'), $timezone);

        $expectedmsg = "Testing with non-saved session.";
        $expectedmsg .= "Start time is ".$starttime1.". Finish time is ".$endtime1.".\n";
        $expectedmsg .= "Start time is ".$starttime2.". Finish time is ".$endtime2.".\n";
        $expectedmsg .= "Start date is ".$startdate1.". Finish date is ".$enddate1.".\n";
        $expectedmsg .= "Start date is ".$startdate2.". Finish date is ".$enddate2.".\n";
        $expectedmsg .= "That is all.";
        $this->assertEquals($expectedmsg, $replacedmsg);
    }
 public function test_facetoface_notification_loop_session_placeholders_no_session() {
        $this->resetAfterTest(true);

        $msg = "Testing with non-saved session. A[#sessions]Start time is [session:starttime]. Finish time is [session:finishtime].\n[/sessions]A";
        $msg .= " I repeat: [#sessions]Start date is [session:startdate]. Finish date is [session:finishdate].\n[/sessions]";
        $msg .= " That is all.";

        $session = new stdClass();
        $session->sessiondates = array();
        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session);
        $expectedmsg = "Testing with non-saved session. ALocation and time to be announced later.A I repeat: Location and time to be announced later. That is all.";
        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests facetoface_notification_loop_session_placeholders function with data returned by functions generally used
     * to retrieve facetoface session data.
     */
    public function test_facetoface_notification_loop_session_placeholders_with_session() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room = new stdClass();
        $room->name = 'Room One';
        $room->capacity = 20;
        $room->timemodified = time();
        $room->timecreated = $room->timemodified;
        $room->id = $DB->insert_record('facetoface_room', $room);

        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]* Start time of [session:startdate] at [session:starttime]";
        $msg .= " and end time of [session:finishdate] at [session:finishtime] ([session:timezone]).\n";
        $msg .= "  Location is [session:room:name].\n";
        $msg .= "[/sessions]";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session, $rooms);

        // Get strings for display of dates and times in email.
        $startdate1 = userdate($times['start1'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime1 = userdate($times['start1'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate1 = userdate($times['end1'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime1 = userdate($times['end1'], get_string('strftimetime', 'langconfig'), $timezone);
        $startdate2 = userdate($times['start2'], get_string('strftimedate', 'langconfig'), $timezone);
        $starttime2 = userdate($times['start2'], get_string('strftimetime', 'langconfig'), $timezone);
        $enddate2 = userdate($times['end2'], get_string('strftimedate', 'langconfig'), $timezone);
        $endtime2 = userdate($times['end2'], get_string('strftimetime', 'langconfig'), $timezone);

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "* Start time of ".$startdate1." at ".$starttime1." and end time of ".$enddate1." at ".$endtime1." (".$timezone.").\n";
        $expectedmsg .= "  Location is Room One.\n";
        $expectedmsg .= "* Start time of ".$startdate2." at ".$starttime2." and end time of ".$enddate2." at ".$endtime2." (".$timezone.").\n";
        $expectedmsg .= "  Location is .\n";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    public function test_facetoface_notification_loop_session_placeholders_room_customfields() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        /** @var shezar_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('shezar_customfield');

        $customfields = array();

        // Create a datetime customfield.
        $cfsettings = array('Room Date' => array('shortname' => 'roomdate', 'startyear' => 2015, 'endyear' => 2030));
        $customfields += $customfieldgenerator->create_datetime('facetoface_room', $cfsettings);

        // Create a text customfield.
        $cfsettings = array('Room Text'); // Will have the shortname of RoomText
        $customfields += $customfieldgenerator->create_text('facetoface_room', $cfsettings);

        // Create a location customfield.
        $cfsettings = array('Room Location' => array('shortname' => 'roomlocation')); // Will have the shortname of RoomText
        $customfields += $customfieldgenerator->create_location('facetoface_room', $cfsettings);

        // Create a room to add to a session date.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);

        $customfieldgenerator->set_datetime($room1, $customfields['Room Date'], $times['other1'], 'facetofaceroom', 'facetoface_room');
        $customfieldgenerator->set_text($room1, $customfields['Room Text'], 'Details about the room', 'facetofaceroom', 'facetoface_room');
        $location1 = new stdClass();
        $customfieldgenerator->set_location_address($room1, $customfields['Room Location'], '150 Willis Street', 'facetofaceroom', 'facetoface_room');

        // Create another room to add to a session date.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->capacity = 40;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);

        $customfieldgenerator->set_datetime($room2, $customfields['Room Date'], $times['other2'], 'facetofaceroom', 'facetoface_room');

        // Set up the face-to-face session.
        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room1->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomid = $room2->id;
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        // Now get all the date we've created.
        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);
        // Get data for room custom fields.
        $roomcustomfields = array();
        foreach($rooms as $room) {
            $roomcustomfields[$room->id] = customfield_get_data($room, 'facetoface_room', 'facetofaceroom', false);
        }

        $msg = "The details for each session:\n";
        $msg .= "[#sessions]";
        $msg .= "[session:room:name] has custom date of [session:room:cf_roomdate].\n";
        $msg .= "[session:room:name] has custom text of [session:room:cf_RoomText].\n";
        $msg .= "[session:room:name] has a custom location of [session:room:cf_roomlocation].\n";
        $msg .= "[/sessions]";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_loop_session_placeholders($msg, $session, $rooms, $roomcustomfields);

        $expectedmsg = "The details for each session:\n";
        $expectedmsg .= "Room One has custom date of ".userdate($times['other1'], get_string('strftimedaydatetime', 'langconfig'), $timezone).".\n";
        $expectedmsg .= "Room One has custom text of Details about the room.\n";
        $expectedmsg .= "Room One has a custom location of 150 Willis Street.\n";
        $expectedmsg .= "Room Two has custom date of ".userdate($times['other2'], get_string('strftimedaydatetime', 'langconfig'), $timezone).".\n";
        $expectedmsg .= "Room Two has custom text of .\n";
        $expectedmsg .= "Room Two has a custom location of .\n";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests the function facetoface_notification_substitute_deprecated_placeholders, ensuring that the values within
     * the 'location' and 'building' custom fields are substituted where the [session:location]
     * and [session:venue] placeholders are found.
     */
    public function test_facetoface_notification_substitute_deprecated_placeholders_with_customfield_values() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        /** @var shezar_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('shezar_customfield');

        $locationfieldid = $DB->get_field('facetoface_room_info_field', 'id', array('shortname' => 'location'));
        $buildingfieldid = $DB->get_field('facetoface_room_info_field', 'id', array('shortname' => 'building'));

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);

        $customfieldgenerator->set_location_address($room1, $locationfieldid, '150 Willis Street', 'facetofaceroom', 'facetoface_room');
        $customfieldgenerator->set_text($room1, $buildingfieldid, 'Building One', 'facetofaceroom', 'facetoface_room');

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->capacity = 20;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);

        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room1->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomid = $room2->id;
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);
        $roomcustomfields = array();
        foreach($rooms as $room) {
            $roomcustomfields[$room->id] = customfield_get_data($room, 'facetoface_room', 'facetofaceroom', false);
        }

        $msg = "Using the old deprecated subsitutions ";
        $msg .= "Room name: [session:room] ";
        $msg .= "Building name: [session:venue]. ";
        $msg .= "Location: [session:location]. ";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_substitute_deprecated_placeholders($msg, $session, $rooms, $roomcustomfields);

        $expectedmsg = "Using the old deprecated subsitutions ";
        $expectedmsg .= "Room name: Room One ";
        $expectedmsg .= "Building name: Building One. ";
        $expectedmsg .= "Location: 150 Willis Street. ";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests the function facetoface_notification_substitute_deprecated_placeholders where there are no values for
     * the 'location' and 'building' custom fields.  In these cases, the [session:location] and
     * [session:venue] placeholders should be replaced with empty strings.
     */
    public function test_facetoface_notification_substitute_deprecated_placeholders_with_customfields_empty() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use the server timezone otherwise this test will fail in some parts of the world and not others.
        $timezone = core_date::get_server_timezone();

        $times = $this->create_array_of_times();

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        /** @var shezar_customfield_generator $customfieldgenerator */
        $customfieldgenerator = $this->getDataGenerator()->get_plugin_generator('shezar_customfield');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room1 = new stdClass();
        $room1->name = 'Room One';
        $room1->capacity = 20;
        $room1->timemodified = time();
        $room1->timecreated = $room1->timemodified;
        $room1->id = $DB->insert_record('facetoface_room', $room1);

        // Create a room to add to a session date. Ideally this would use an existing function rather than
        // a direct db insert - none exists while writing this test.
        $room2 = new stdClass();
        $room2->name = 'Room Two';
        $room2->capacity = 20;
        $room2->timemodified = time();
        $room2->timecreated = $room2->timemodified;
        $room2->id = $DB->insert_record('facetoface_room', $room2);

        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = $timezone;
        $sessiondate1->timestart = $times['start1'];
        $sessiondate1->timefinish = $times['end1'];
        $sessiondate1->roomid = $room1->id;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = $timezone;
        $sessiondate2->timestart = $times['start2'];
        $sessiondate2->timefinish = $times['end2'];
        $sessiondate2->roomid = $room2->id;
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);
        $rooms = facetoface_get_session_rooms($session->id);
        $roomcustomfields = array();
        foreach($rooms as $room) {
            $roomcustomfields[$room->id] = customfield_get_data($room, 'facetoface_room', 'facetofaceroom', false);
        }

        $msg = "Using the old deprecated subsitutions ";
        $msg .= "Room name: [session:room] ";
        $msg .= "Building name: [session:venue]. ";
        $msg .= "Location: [session:location]. ";
        $msg .= "Those are all the details.";

        $replacedmsg = facetoface_notification_substitute_deprecated_placeholders($msg, $session, $rooms, $roomcustomfields);

        $expectedmsg = "Using the old deprecated subsitutions ";
        $expectedmsg .= "Room name: Room One ";
        $expectedmsg .= "Building name: . ";
        $expectedmsg .= "Location: . ";
        $expectedmsg .= "Those are all the details.";

        $this->assertEquals($expectedmsg, $replacedmsg);
    }

    /**
     * Tests the output of facetoface_get_default_notifications.
     */
    public function test_facetoface_get_default_notifications() {
        $this->resetAfterTest(true);
        global $DB;

        $course = $this->getDataGenerator()->create_course();

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        list($notifications, $missing) = facetoface_get_default_notifications($facetoface->id);

        // Get templates.
        $templaterecords = $DB->get_records('facetoface_notification_tpl');

        // There should be no missing notifications.
        $this->assertEmpty($missing);

        // The number of default notifications should equal the number of templates.
        $this->assertEquals(count($templaterecords), count($notifications));
    }

    /**
     * Tests values returned by facetoface_notification_get_templates_with_old_placeholders.
     */
    public function test_facetoface_notification_get_templates_with_old_placeholders() {
        $this->resetAfterTest(true);
        global $DB;

        $oldnotifications = facetoface_notification_get_templates_with_old_placeholders();
        // There should be no oldplaceholders in templates on a newly installed 9.0 site.
        // We expect an empty array, rather than false or null.
        $this->assertEquals(array(), $oldnotifications);

        // A template with the placeholder in the title.
        $newtemplate1 = new stdClass();
        $newtemplate1->title = 'Sometitle with an old placeholder [session:location] ...';
        $newtemplate1->body = 'A body with a new placeholder [session:room:location] ...';
        $newtemplate1->managerprefix = 'A managerprefix with a new placeholder [session:room:link] ...';
        $newtemplate1->status = 1;
        $newtemplate1->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate1);

        // A template with the placeholder in the body.
        $newtemplate2 = new stdClass();
        $newtemplate2->title = 'Sometitle with an no placeholders';
        $newtemplate2->body = 'A body with a new placeholder [session:venue] ...';
        $newtemplate2->managerprefix = null; // Managerprefix field can be null.
        $newtemplate2->status = 1;
        $newtemplate2->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate2);

        // A template with the placeholder in the managerprefix.
        $newtemplate3 = new stdClass();
        $newtemplate3->title = 'Sometitle with a new placeholder [session:room:name] ...';
        $newtemplate3->body = 'A body with no placeholders ...';
        $newtemplate3->managerprefix = 'A managerprefix with two old placeholders [session:room] and [alldates]...';
        $newtemplate3->status = 1;
        $newtemplate3->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate3);

        // Another new template with no old placeholders.
        $newtemplate4 = new stdClass();
        $newtemplate4->title = 'Sometitle with a new placeholder [session:room:location] ...';
        $newtemplate4->body = 'A body with a placeholders that works before and after 9.0 [startdate] ...';
        $newtemplate4->managerprefix = 'A managerprefix with no placeholders...';
        $newtemplate4->status = 1;
        $newtemplate4->id = $DB->insert_record('facetoface_notification_tpl', $newtemplate4);

        // Let's edit an existing template to include an old placeholder.
        $existingtemplate = $DB->get_record('facetoface_notification_tpl', array('reference' => 'confirmation'));
        $existingtemplate->body = 'Overwriting the body with a message the includes an old template [session:room] ...';
        $DB->update_record('facetoface_notification_tpl', $existingtemplate);

        // We need to clear the cache.
        $cacheoptions = array(
            'simplekeys' => true,
            'simpledata' => true
        );
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'mod_facetoface', 'notificationtpl', array(), $cacheoptions);
        $cache->delete('oldnotifications');

        $oldnotifications = facetoface_notification_get_templates_with_old_placeholders();

        $expected = array(
            $newtemplate1->id,
            $newtemplate2->id,
            $newtemplate3->id,
            $existingtemplate->id
        );

        // Order does not matter. Sorting both should set the orders in each to be the same.
        sort($expected);
        sort($oldnotifications);
        $this->assertEquals($expected, $oldnotifications);
    }

    /**
     * Check auto notifications duplicates recovery code
     */
    public function test_notification_duplicates() {
        global $DB;
        $sessionok = $this->f2f_generate_data(false);
        $sessionbad = $session = $this->f2f_generate_data(false);

        // Make duplicate.
        $duplicate = $DB->get_record('facetoface_notification', array(
            'facetofaceid' => $sessionbad->facetoface,
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $duplicate->id = null;
        $DB->insert_record('facetoface_notification', $duplicate);

        $noduplicate = $DB->get_record('facetoface_notification', array(
            'facetofaceid' => $sessionok->facetoface,
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $noduplicate->id = null;
        $noduplicate->type = 1;
        $DB->insert_record('facetoface_notification', $noduplicate);

        // Check duplicates detection.
        $this->assertTrue(facetoface_notification::has_auto_duplicates($sessionbad->facetoface));
        $this->assertFalse(facetoface_notification::has_auto_duplicates($sessionok->facetoface));

        // Check that it will not fail when attempted to send duplicate.
        $facetoface = $DB->get_record('facetoface', array('id' => $sessionbad->facetoface));
        $course = $DB->get_record("course", array('id' => $facetoface->course));
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_STATUS_BOOKED, $student->id);

        facetoface_send_cancellation_notice($facetoface, $sessionbad, $student->id);
        $this->assertDebuggingCalled();

        // Check duplicates prevention.
        $allbefore = $DB->get_records('facetoface_notification', array('facetofaceid' => $sessionok->facetoface));

        $note = new facetoface_notification(array(
            'facetofaceid'  => $sessionok->facetoface,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ));
        $note->id = null;
        $note->save();
        $this->assertDebuggingCalled();

        $allafter = $DB->get_records('facetoface_notification', array('facetofaceid' => $sessionok->facetoface));
        $this->assertEquals(count($allbefore), count($allafter));
    }

    public function f2fsession_generate_data($future = true) {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $manager  = $this->getDataGenerator()->create_user();

        $managerja = \shezar_job\job_assignment::create_default($manager->id);
        \shezar_job\job_assignment::create_default($student1->id, array('managerjaid' => $managerja->id));
        \shezar_job\job_assignment::create_default($student2->id, array('managerjaid' => $managerja->id));

        $course = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondate->timestart = time() + WEEKSECS;
        $sessiondate->timefinish = time() + WEEKSECS + 60;

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'datetimeknown' => '1',
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        return array($session, $facetoface, $course, $student1, $student2, $teacher1, $manager);
    }

    public function test_booking_confirmation_default() {

        // Default test Manager copy is enable and suppressccmanager is disabled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails, 'Wrong booking confirmation for Default test Manager copy is enable and suppressccmanager is disabled.');
    }

    public function test_booking_confirmation_suppress_ccmanager() {

        // Test Manager copy is enable and suppressccmanager is enabled(do not send a copy to manager).
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $params = array();
        if ($suppressccmanager) {
            $params['ccmanager'] = 0;
        }
        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id, $params);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is enable and suppressccmanager is enabled(do not send a copy to manager).');
    }

    public function test_booking_confirmation_no_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_confirmation_no_ccmanager_and_suppress_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $data = array();
        if ($suppressccmanager) {
            $data['ccmanager'] = 0;
        }
        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id, $data);
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking confirmation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_cancellation_default() {

        // Default test Manager copy is enable and suppressccmanager is disabled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $emailsink = $this->redirectEmails();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(2, $emails, 'Wrong booking cancellation for Default test Manager copy is enable and suppressccmanager is disabled.');
    }

    public function test_booking_cancellation_suppress_ccmanager() {

        // Test Manager copy is enable and suppressccmanager is enabled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $emailsink = $this->redirectEmails();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                if ($suppressccmanager) {
                    $facetoface->ccmanager = 0;
                }
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is enable and suppressccmanager is enabled.');
    }

    public function test_booking_cancellation_only_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );
        $this->update_f2f_notification($params, 1);

        $emailsink = $this->redirectEmails();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                $facetoface->ccmanager = 1;
                $session->notifyuser = 0;
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Only one message is expected');
        $this->assertEquals($manager->email, $emails[0]->to);
        $joinedbody = str_replace("=\n", "", $emails[0]->body);
        $this->assertContains('you as their Team Leader', $joinedbody);
    }

    public function test_booking_cancellation_no_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $emailsink = $this->redirectEmails();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    public function test_booking_cancellation_no_ccmanager_and_suppress_ccmanager() {

        // Test Manager copy is disabled and suppressccmanager is disbaled.
        list($session, $facetoface, $course, $student1, $student2, $teacher1, $manager) = $this->f2fsession_generate_data();

        $suppressccmanager = true;

        $emailsink = $this->redirectEmails();
        facetoface_user_import($course, $facetoface, $session, $student1->id);
        $emailsink->close();

        $attendees = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED));

        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        );
        $this->update_f2f_notification($params, 0);

        $emailsink = $this->redirectEmails();
        foreach ($attendees as $attendee) {
            if (facetoface_user_cancel($session, $attendee->id)) {
                if ($suppressccmanager) {
                    $facetoface->ccmanager = 0;
                }
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);
            }
        }
        $emailsink->close();

        $emails = $emailsink->get_messages();
        $this->assertCount(1, $emails, 'Wrong booking cancellation for Test Manager copy is disabled and suppressccmanager is disbaled.');
    }

    private function update_f2f_notification($params, $ccmanager) {
        global $DB;

        $notification = new facetoface_notification($params);

        $notice = new stdClass();
        $notice->id = $notification->id;
        $notice->ccmanager = $ccmanager;

        return $DB->update_record('facetoface_notification', $notice);
    }

    public function test_user_timezone() {
        global $DB;

        $emailsink = $this->redirectEmails();
        list($sessiondate, $student1, $student2, $student3) = $this->f2fsession_generate_timezone(99);
        $emailsink->close();

        // Test we are getting F2F booking confirmation email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is to confirm that you are now booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking confirmation');

        $alldates = $this->get_user_date($sessiondate, $student1);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->body,
            'Wrong session timezone date for student 1 Face-to-face booking confirmation notification');

        $alldates = $this->get_user_date($sessiondate, $student2);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->body,
            'Wrong session timezone date for student 2 Face-to-face booking confirmation notification');

        $alldates = $this->get_user_date($sessiondate, $student3);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->body,
            'Wrong session timezone date for student 3 Face-to-face booking confirmation notification');

        $scheduled = $DB->get_records_select('facetoface_notification', 'conditiontype = ?', array(MDL_F2F_CONDITION_BEFORE_SESSION));
        $this->assertCount(1, $scheduled);
        $notify = reset($scheduled);
        $emailsink = $this->redirectEmails();
        $notification = new \facetoface_notification((array)$notify, false);
        $notification->send_scheduled();
        $emailsink->close();
        // Test we are getting F2F booking reminder email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is a reminder that you are booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking reminder');

        $alldates = $this->get_user_date($sessiondate, $student1);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->body,
            'Wrong session timezone date for student 1 of Face-to-face booking reminder notification');

        $alldates = $this->get_user_date($sessiondate, $student2);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->body,
            'Wrong session timezone date for student 2 of Face-to-face booking reminder notification');

        $alldates = $this->get_user_date($sessiondate, $student3);
        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->body,
            'Wrong session timezone date for student 3 of Face-to-face booking reminder notification');
    }

    public function test_session_timezone() {
        global $DB;

        $test = new stdClass();
        $test->timezone = 'America/New_York';

        $emailsink = $this->redirectEmails();
        list($sessiondate, $student1, $student2, $student3) = $this->f2fsession_generate_timezone($test->timezone);
        $emailsink->close();

        // Test we are getting F2F booking confirmation email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is to confirm that you are now booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking confirmation');

        $alldates = $this->get_user_date($sessiondate, $test);

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->body,
            'Wrong session timezone date for student 1 Face-to-face booking confirmation notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->body,
            'Wrong session timezone date for student 2 Face-to-face booking confirmation notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->body,
            'Wrong session timezone date for student 3 Face-to-face booking confirmation notification');

        $scheduled = $DB->get_records_select('facetoface_notification', 'conditiontype = ?', array(MDL_F2F_CONDITION_BEFORE_SESSION));
        $this->assertCount(1, $scheduled);
        $notify = reset($scheduled);
        $emailsink = $this->redirectEmails();
        $notification = new \facetoface_notification((array)$notify, false);
        $notification->send_scheduled();
        $emailsink->close();
        // Test we are getting F2F booking reminder email.
        $haystack = $emailsink->get_messages();
        $this->notification_content_test(
            'This is a reminder that you are booked on the following course',
            $haystack,
            'Wrong notification, must be Face-to-face booking reminder');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[0]->body,
            'Wrong session timezone date for student 1 of Face-to-face booking reminder notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[1]->body,
            'Wrong session timezone date for student 2 of Face-to-face booking reminder notification');

        // Test user timezone date with session timezone date.
        $this->assertContains(
            $alldates,
            $haystack[2]->body,
            'Wrong session timezone date for student 3 of Face-to-face booking reminder notification');
    }

    private function f2fsession_generate_timezone($sessiontimezone) {
        global $DB, $CFG;

        $this->setAdminUser();

        // Server timezone is Australia/Perth = $CFG->timezone.
        $student1 = $this->getDataGenerator()->create_user(array('timezone' => 'Europe/London'));
        $student2 = $this->getDataGenerator()->create_user(array('timezone' => 'Pacific/Auckland'));
        $student3 = $this->getDataGenerator()->create_user(array('timezone' => $CFG->timezone));
        $this->assertEquals($student1->timezone, 'Europe/London');
        $this->assertEquals($student2->timezone, 'Pacific/Auckland');
        $this->assertEquals($student3->timezone, $CFG->timezone);

        \shezar_job\job_assignment::create_default($student1->id);
        \shezar_job\job_assignment::create_default($student2->id);
        \shezar_job\job_assignment::create_default($student3->id);

        $course = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student3->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        $sessiondate = new stdClass();
        $sessiondate->sessiontimezone = $sessiontimezone;
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + (4 * HOURSECS);

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 5,
            'sessiondates' => array($sessiondate),
            'datetimeknown' => '1',
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $student1->id);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $student2->id);
        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_TEXT, MDL_F2F_STATUS_BOOKED, $student3->id);

        return array($sessiondate, $student1, $student2, $student3);
    }

    private function notification_content_test($needlebody, $emails, $message) {

        $this->assertContains($needlebody, $emails[0]->body, $message);
        $this->assertContains($needlebody, $emails[1]->body, $message);
        $this->assertContains($needlebody, $emails[2]->body, $message);
    }

    private function get_user_date($sessiondate, $date) {
        // Get user settings.
        $alldates = '';
        $strftimedate = get_string('strftimedate');
        $strftimetime = get_string('strftimetime');

        $startdate  = userdate($sessiondate->timestart, $strftimedate, $date->timezone);
        $startime   = userdate($sessiondate->timestart, $strftimetime, $date->timezone);

        $finishdate = userdate($sessiondate->timefinish, $strftimedate, $date->timezone);
        $finishtime = userdate($sessiondate->timefinish, $strftimetime, $date->timezone);

        // Template example: [session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
        $alldates .= $startdate .', '.$startime .' - '. $finishdate .', '. $finishtime . ' '. $date->timezone;

        return $alldates;
    }

}
