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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package shezar_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');

/**
 * Test events in programs.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit shezar_program_events_testcase
 *
 */
class shezar_program_messages_testcase extends advanced_testcase {

    private $program_generator = null;
    private $program = null;
    private $user = null;

    public function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
        $this->program_generator = $this->getDataGenerator()->get_plugin_generator('shezar_program');
    }

    public function test_program_enrolment_messages() {
        global $DB, $CFG, $UNITTEST;

        $this->preventResetByRollback();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Function in lib/moodlelib.php email_to_user require this.
        if (!isset($UNITTEST)) {
            $UNITTEST = new stdClass();
            $UNITTEST->running = true;
        }

        unset_config('noemailever');
        $sink = $this->redirectEmails();
        ob_start(); // Start a buffer to catch all the mtraces in the task.

        // Create 8 users.
        $this->assertEquals(2, $DB->count_records('user'));
        for ($i = 1; $i <= 8; $i++) {
            $this->{'user'.$i} = $this->getDataGenerator()->create_user();
        }
        $this->assertEquals(10, $DB->count_records('user'));

        // Create two programs.
        $this->assertEquals(0, $DB->count_records('prog'));
        $this->program1 = $this->program_generator->create_program();
        $this->program2 = $this->program_generator->create_program();
        $this->assertEquals(2, $DB->count_records('prog'));

        // Make sure the mail is redirecting and the sink is clear.
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());
        $sink->clear();

        // Assign users to program1.
        $usersprogram1 = array($this->user1->id, $this->user2->id, $this->user3->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.

        // Attempt to send any program messages.
        $task = new \shezar_program\task\send_messages_task();
        $task->execute();

        // Check the right amount of messages were caught.
        $emails = $sink->get_messages();
        $this->assertCount(3, $emails);
        $sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(3, $DB->count_records('prog_messagelog'));

        // Assign more users to program1 and make sure only the new users get the message.
        $usersprogram1 = array($this->user1->id, $this->user2->id, $this->user4->id, $this->user5->id);
        $this->program_generator->assign_program($this->program1->id, $usersprogram1);

        sleep(1);

        // Attempt to send any program messages.
        $task = new \shezar_program\task\send_messages_task();
        $task->execute();

        // Check the right amount of messages were caught.
        $emails = $sink->get_messages();
        $this->assertCount(2, $emails);
        $sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(5, $DB->count_records('prog_messagelog'));

        $usersprogram2 = array($this->user3->id, $this->user4->id, $this->user5->id, $this->user6->id, $this->user7->id);
        $this->program_generator->assign_program($this->program2->id, $usersprogram2);

        sleep(1);

        // Attempt to send any program messages.
        $task = new \shezar_program\task\send_messages_task();
        $task->execute();

        // Check the right amount of messages were caught.
        $emails = $sink->get_messages();
        $this->assertCount(5, $emails);
        $sink->clear();

        // Check that they all had logs created.
        $this->assertEquals(10, $DB->count_records('prog_messagelog'));

        ob_end_clean(); // Throw away the buffer content.
    }
}
