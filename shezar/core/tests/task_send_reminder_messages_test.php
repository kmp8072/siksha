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
 * @author Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @package shezar_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->libdir.'/reminderlib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');

/**
 * Send reminder messages task tests.
 */
class shezar_core_task_send_reminder_messages_test extends reportcache_advanced_testcase {

    /** @var stdClass */
    protected $course;
    /** @var stdClass */
    protected $feedback;
    /** @var reminder */
    protected $reminder;
    /** @var stdClass */
    protected $manager;
    /** @var stdClass */
    protected $learner1;
    /** @var stdClass */
    protected $learner2;

    /**
     * Set up for each test.
     */
    public function setUp() {
        global $CFG, $DB;

        // We must reset after this test.
        $this->resetAfterTest();
        // Completion must be enabled.
        $CFG->enablecompletion = true;

        // Grab a generator, we're going to need this.
        $generator = $this->getDataGenerator();

        // Generate a course with completion enabled and set to start on enrol.
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        );
        $this->course = $generator->create_course($coursedefaults, array('createsections' => true));

        // Generate a feedback module. Needed for reminders.
        $this->feedback = $generator->create_module('feedback', array('course' => $this->course->id), array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED
        ));
        // Create reminders for the course now that we have a feedback module.
        $this->reminder = $this->create_reminder($this->feedback->id, $this->course->id);

        // Set self completion = true for this course.
        $criterion = new completion_criteria_self();
        $criterion->update_config((object)array(
            'criteria_self' => 1,
            'criteria_self_value' => 1,
            'id' => $this->course->id
        ));

        // Create a manager and a learner with that same position assignment.
        $this->manager = $generator->create_user(array('username' => 'manager'));
        $this->learner1 = $generator->create_user(array('username' => 'learner1', 'managerid' => $this->manager->id));
        $this->learner2 = $generator->create_user(array('username' => 'learner2', 'managerid' => $this->manager->id));

        // Give each user a second manager.
        $secondmanager = $generator->create_user(['username' => 'butter_manager']);
        $managerja = \shezar_job\job_assignment::create_default($secondmanager->id);
        \shezar_job\job_assignment::create_default($this->learner1->id, array('managerjaid' => $managerja->id, 'fullname' => 'Head banana ripener'));
        \shezar_job\job_assignment::create_default($this->learner2->id, array('managerjaid' => $managerja->id, 'fullname' => 'Internet explorer'));

        // Enrol the user into the course.
        $generator->enrol_user($this->learner1->id, $this->course->id);
        $generator->enrol_user($this->learner2->id, $this->course->id);

        // Test the reminder structure.
        $reminders = get_course_reminders($this->course->id);
        $this->assertCount(1, $reminders);
        $reminder = reset($reminders);
        $messages = $reminder->get_messages();
        $this->assertCount(3, $messages);

        // Test that there is no completion for this course yet.
        $completioninfo = new completion_info($this->course);
        $this->assertEquals(COMPLETION_ENABLED, $completioninfo->is_enabled());
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'),'Record count mismatch for completion');
        $this->assertTrue($completioninfo->is_tracked_user($this->learner1));
        $this->assertTrue($completioninfo->is_tracked_user($this->learner2));
    }

    /**
     * Creates a reminder and the default message types.
     *
     * @param int $feedbackid
     * @param int $courseid
     * @param array $reminderparams
     * @param array $messageparams
     * @return reminder
     */
    protected function create_reminder($feedbackid, $courseid, array $reminderparams = array(), array $messageparams = array()) {
        global $USER;

        $mod = get_coursemodule_from_instance('feedback', $feedbackid);

        $config = array(
            'tracking' => 0,
            'requirement' => $mod->id
        );

        $reminder = new reminder();
        // Create the reminder object
        $reminder->timemodified = time();
        $reminder->modifierid = $USER->id;
        $reminder->deleted = '0';
        $reminder->title = 'Test reminder';
        $reminder->type = 'completion';
        $reminder->config = serialize($config);
        $reminder->timecreated = time() - (DAYSECS * 5);
        foreach ($reminderparams as $key => $value) {
            $reminder->$key = $value;
        }
        $reminder->courseid = $courseid;
        $reminder->id = $reminder->insert();

        // Create the messages
        $messageproperties = array(
            'subject',
            'message',
            'period',
            'dontsend',
            'copyto', // skipmanager
            'deleted',
        );
        foreach (array('invitation', 'reminder', 'escalation') as $type) {
            $message = new reminder_message(
                array(
                    'reminderid'    => $reminder->id,
                    'type'          => $type,
                    'deleted'       => 0
                )
            );
            $message->period = 0;
            $message->copyto = '';
            $message->subject = 'Subject for type '.$type;
            $message->message = 'Message for type '.$type;
            $message->deleted = 0;

            foreach ($messageproperties as $key) {
                if (isset($messageparams[$key])) {
                    $message->$key = $messageparams[$key];
                }
            }
            if (!$message->insert()) {
                throw new coding_exception('Failed to create course reminder message');
            }
        }

        return $reminder;
    }

    /**
     * Test that no notifications get sent on a fresh site.
     */
    public function test_no_notifications_by_default() {
        $sink = $this->redirectMessages();
        $task = new \shezar_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        $this->assertSame(0, $sink->count());
        $this->assertContains('no users to send invitation message', $output);
        $this->assertContains('no users to send reminder message', $output);
        $this->assertContains('no users to send escalation message', $output);
        $sink->close();
    }

    /**
     * Test course reminders get sent.
     */
    public function test_reminders_get_sent() {
        $sink = $this->redirectMessages();

        // Make user1 to complete the certification with completion date 1 day before today.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        $task = new \shezar_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // There should be four messages, three to the learner and 1 to the learners manager.
        $this->assertSame(4, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertContains('2 "escalation" type messages sent', $output);

        // Make sure that an escalation email did indeed go to the manager.
        $messages = $sink->get_messages();
        $managergotemail = false;
        foreach($messages as $message) {
            if (($message->subject === 'Subject for type escalation') and ($message->useridto == $this->manager->id)) {
                $managergotemail = true;
            }
        }
        $this->assertTrue($managergotemail);

        $sink->close();
    }

    /**
     * Test that we don't send backdated escalation notices when changing the escalation dontsend option.
     */
    public function test_changing_escalation_nosend_value() {
        global $DB;

        $sink = $this->redirectMessages();

        // Disable the escalation reminder.
        $config = unserialize($this->reminder->config);
        $config['escalationmodified'] = time() - (DAYSECS * 2);
        $this->reminder->config = serialize($config);
        $this->reminder->update();
        $messages = $this->reminder->get_messages();
        foreach ($messages as $message) {
            /* @var reminder_message $message */
            if ($message->type === 'escalation' && empty($message->deleted)) {
                $message->deleted = 1;
                $message->update();
                break;
            }
        }

        // Mark learner1 as complete.
        $coursecompletion = new completion_info($this->course);
        $completion = $coursecompletion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        // Trigger the task for the first time.
        // The user has been marked complete so they should receive invitation and reminder type messages.
        $task = new \shezar_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();
        $this->assertSame(2, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertNotContains('escalation', $output);
        // Clear the sink we don't want to keep track of this.
        $sink->clear();

        // Update the reminder and create the new message escalation.
        $config = unserialize($this->reminder->config);
        $config['escalationmodified'] = time() - (DAYSECS / 2);
        $this->reminder->config = serialize($config);
        $this->reminder->update();
        // This is truly horrid but it IS how it actually works.
        // We need to insert a second escalation reminder message with delete = 0.
        unset($message->id);
        $message->deleted = 0;
        $message->insert();

        // Trigger the task for the second time. The invitation and reminder messages have already been sent
        // and we've only just enabled the escalation reminder again so this should result in no notifications.
        $task = new \shezar_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();
        $this->assertSame(0, $sink->count());
        $this->assertContains('no users to send invitation message', $output);
        $this->assertContains('no users to send reminder message', $output);
        $this->assertContains('no users to send escalation message', $output);

        // Now mark learner 2 as complete.
        $completion = $coursecompletion->get_completion($this->learner2->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (DAYSECS / 4));
        $this->assertTrue($completion->is_complete());

        // Trigger the task for the third time.
        $task = new \shezar_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();
        // There should be four messages, three to the learner and 1 to the learners first manager.
        // The learner has a second manager, but only the first should get messaged.
        $this->assertSame(4, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertContains('2 "escalation" type messages sent', $output);

        $sink->close();
    }

}