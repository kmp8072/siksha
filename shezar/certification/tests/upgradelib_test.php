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
 * @author Nathan Lewis <nathan.lewis@shezarlms.com>
 * @package shezar_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/shezar/certification/lib.php');
require_once($CFG->dirroot . '/shezar/certification/db/upgradelib.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose shezar_certification_upgradelib_testcase shezar/certification/tests/upgradelib_test.php
 */
class shezar_certification_upgradelib_testcase extends reportcache_advanced_testcase {

    public $users = array();
    public $programs = array();
    public $certifications = array();
    public $numtestusers = 10;
    public $numtestcerts = 10;
    public $numtestprogs = 7;

    /**
     * Set up the users, certifications and completions.
     */
    public function setup_completions() {
        // Create users.
        for ($i = 1; $i <= $this->numtestusers; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numtestprogs; $i++) {
            $this->programs[$i] = $this->getDataGenerator()->create_program();
        }

        // Create certifications.
        for ($i = 1; $i <= $this->numtestcerts; $i++) {
            $this->certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Assign users to the certification as individuals.
        foreach ($this->users as $user) {
            foreach ($this->certifications as $prog) {
                $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }
    }

    /**
     * Change the state of all completion records to certified, before the window opens.
     */
    public function shift_completions_to_certified($timecompleted) {
        global $DB;

        // Manually change their state.
        $sql = "UPDATE {prog_completion}
                   SET status = :progstatus, timecompleted = :timecompleted, timedue = :timedue
                 WHERE coursesetid = 0";
        $params = array('progstatus' => STATUS_PROGRAM_COMPLETE, 'timecompleted' => $timecompleted,
            'timedue' => $timecompleted + 2000);
        $DB->execute($sql, $params);
        $sql = "UPDATE {certif_completion}
                   SET status = :certstatus, renewalstatus = :renewalstatus, certifpath = :certifpath,
                       timecompleted = :timecompleted, timewindowopens = :timewindowopens, timeexpires = :timeexpires";
        $params = array('certstatus' => CERTIFSTATUS_COMPLETED, 'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
            'certifpath' => CERTIFPATH_RECERT, 'timecompleted' => $timecompleted, 'timewindowopens' => $timecompleted + 1000,
            'timeexpires' => $timecompleted + 2000);
        $DB->execute($sql, $params);
    }

    public function test_certif_upgrade_fix_reassigned_users() {
        global $DB;

        $this->resetAfterTest(true);

        // Create users and certs, assign users.
        $this->setup_completions();

        $now = time();

        // Mark users complete (will be used in history).
        $this->shift_completions_to_certified($now);

        // Check that all records are ok.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Copy current completion records into history.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }
        // Create second copy of history records, to test that the unassigned flag is correctly cleared.
        $DB->execute("UPDATE {certif_completion}
                         SET timecompleted = timecompleted - 10000,
                             timewindowopens = timewindowopens - 10000,
                             timeexpires = timeexpires - 10000");
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts * 2, count($histcompletions));

        // Set up the specific test completion data:
        // * Target/base record:
        //      program complete, cert incomplete, history unassigned "certified, before window opens"
        // * Control records
        //      1) cert complete
        //      2) program incomplete
        //      3) history NOT unassigned "certified, before window opens"
        //      4) history unassigned "certified, window HAS opened"
        //      5) history unassigned "certified, before window opens" with date error.
        $controluser = array(
            1 => $this->users[3]->id,
            2 => $this->users[4]->id,
            3 => $this->users[7]->id,
            4 => $this->users[5]->id,
            5 => $this->users[7]->id);
        $controlcert = array(
            1 => $this->certifications[4]->certifid,
            2 => $this->certifications[6]->certifid,
            3 => $this->certifications[3]->certifid,
            4 => $this->certifications[4]->certifid,
            5 => $this->certifications[4]->certifid);

        // Change data to look like the target and control records.
        $DB->execute("UPDATE {certif_completion_history} SET unassigned = 1");
        $certcompletions = $DB->get_records('certif_completion');
        $controlscreated = 0;
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);

            // First make all records look like the ones we are targeting, then make specific changes for each control.
            // Targets: cert incomplete, prog complete, history unassigned "certified, before window opens".
            $certcompletion->status = CERTIFSTATUS_ASSIGNED;
            $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
            $certcompletion->certifpath = CERTIFPATH_CERT;
            $certcompletion->timecompleted = 0;
            $certcompletion->timewindowopens = 0;
            $certcompletion->timeexpires = 0;
            $certcompletion->timemodified = $now;
            $DB->update_record('certif_completion', $certcompletion);

            // Controls.
            if ($certcompletion->userid == $controluser[1] && $certcompletion->certifid == $controlcert[1]) {
                // Control 1: cert complete, prog complete, history unassigned "certified, before window opens".
                $certcompletion->status = CERTIFSTATUS_COMPLETED;
                $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
                $certcompletion->certifpath = CERTIFPATH_RECERT;
                $certcompletion->timecompleted = $now;
                $certcompletion->timewindowopens = $now + 1000;
                $certcompletion->timeexpires = $now + 2000;
                $DB->update_record('certif_completion', $certcompletion);
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[2] && $certcompletion->certifid == $controlcert[2]) {
                // Control 2: cert incomplete, prog incomplete, history unassigned "certified, before window opens".
                $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
                $progcompletion->timecompleted = 0;
                $DB->update_record('prog_completion', $progcompletion);
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[3] && $certcompletion->certifid == $controlcert[3]) {
                // Control 3: cert incomplete, prog complete, history NOT unassigned "certified, before window opens".
                $DB->set_field('certif_completion_history', 'unassigned', 0,
                    array('userid' => $controluser[3], 'certifid' => $controlcert[3]));
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[4] && $certcompletion->certifid == $controlcert[4]) {
                // Control 4: cert incomplete, prog complete, history unassigned "certified, window HAS opened".
                $cchs = $DB->get_records('certif_completion_history',
                    array('userid' => $controluser[4], 'certifid' => $controlcert[4]), 'timeexpires DESC');
                $cchlatest = reset($cchs);
                $ccholder = next($cchs);
                $cchlatest->renewalstatus = CERTIFRENEWALSTATUS_DUE;
                $DB->update_record('certif_completion_history', $cchlatest);
                $DB->delete_records('certif_completion_history', array('id' => $ccholder->id)); // Remove so it doesn't interfere.
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[5] && $certcompletion->certifid == $controlcert[5]) {
                // Control 5: cert incomplete, prog complete, history unassigned "certified, before window opens" with date error.
                $cchs = $DB->get_records('certif_completion_history',
                    array('userid' => $controluser[5], 'certifid' => $controlcert[5]), 'timeexpires DESC');
                $cchlatest = reset($cchs);
                $cchlatest->timecompleted = $now + 2000;
                $cchlatest->timewindowopens = $now + 1000;
                $cchlatest->timeexpires = $now + 3000;
                $DB->update_record('certif_completion_history', $cchlatest);
                // Leave older history as it is - the latest should be selected, but will fail.
                $controlscreated++;
            }
        }
        $this->assertEquals($controlscreated, 5);
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Check that all records are set up in the specified way.
        $certcompletions = $DB->get_records('certif_completion');
        $controlschecked = 0;
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $history = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid));
            $historyunassigned = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid, 'unassigned' => 1));
            if ($certcompletion->userid == $controluser[1] && $certcompletion->certifid == $controlcert[1]) {
                // Control 1: cert complete, prog complete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[2] && $certcompletion->certifid == $controlcert[2]) {
                // Control 2: cert incomplete, prog incomplete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[3] && $certcompletion->certifid == $controlcert[3]) {
                // Control 3: cert incomplete, prog complete, history NOT unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(0, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[4] && $certcompletion->certifid == $controlcert[4]) {
                // Control 4: cert incomplete, prog complete, history unassigned "certified, window HAS opened".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(1, count($history)); // Older was removed during setup.
                $this->assertEquals(1, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[5] && $certcompletion->certifid == $controlcert[5]) {
                // Control 5: cert incomplete, prog complete, history unassigned "certified, before window opens" with date error.
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else {
                // Targets: cert incomplete, prog complete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
            }
        }
        $this->assertEquals($controlschecked, 5);
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Run the upgrade.
        certif_upgrade_fix_reassigned_users();

        // Check that target records have been fixed and others have been unaffected.
        $certcompletions = $DB->get_records('certif_completion');
        $controlschecked = 0;
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $history = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid));
            $historyunassigned = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid, 'unassigned' => 1));
            if ($certcompletion->userid == $controluser[1] && $certcompletion->certifid == $controlcert[1]) {
                // Control 1: cert complete, prog complete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[2] && $certcompletion->certifid == $controlcert[2]) {
                // Control 2: cert incomplete, prog incomplete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[3] && $certcompletion->certifid == $controlcert[3]) {
                // Control 3: cert incomplete, prog complete, history NOT unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(0, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[4] && $certcompletion->certifid == $controlcert[4]) {
                // Control 4: cert incomplete, prog complete, history unassigned "certified, window HAS opened".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(1, count($history)); // Older was removed during setup.
                $this->assertEquals(1, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[5] && $certcompletion->certifid == $controlcert[5]) {
                // Control 5: cert incomplete, prog complete, history unassigned "certified, before window opens" with date error.
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else {
                // Targets: cert complete, prog complete, history has been removed.
                $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, $state, $certcompletion);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(1, count($history));
                $this->assertEquals(0, count($historyunassigned));
            }
        }
        $this->assertEquals($controlschecked, 5);
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

    }
}
