<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2014 onwards shezar Learning Solutions LTD
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

// TL-8605 Repair completion records affected by bug fixed in TL-6790. Users were "certified" when they were
// unassigned, then later reassigned. Their program completion record is complete while their certification
// record in newly assigned. Only restore if there is an "unassigned" history record to restore from. If there
// is any problem then the records must be fixed manually.
require_once($CFG->dirroot.'/shezar/program/program.class.php'); // For program status constants.
require_once($CFG->dirroot.'/shezar/certification/lib.php'); // For certification function and status constants.

function certif_upgrade_fix_reassigned_users() {
    global $DB;

    // Search certification completion records for the specific problem.
    $sql = "SELECT cc.id AS ccid, cc.userid, pc.id AS pcid,
                   cc.certifid, cc.status, cc.renewalstatus, cc.certifpath, cc.timecompleted, cc.timewindowopens, cc.timeexpires,
                   pc.programid, pc.status AS progstatus, pc.timecompleted AS progtimecompleted, pc.timedue
              FROM {certif_completion} cc
              JOIN {prog} prog ON prog.certifid = cc.certifid
              JOIN {prog_completion} pc ON pc.programid = prog.id AND pc.userid = cc.userid AND pc.coursesetid = 0
             WHERE pc.status = :progstatuscomplete AND pc.timecompleted > 0
               AND (cc.status = :certstatusassigned OR
                    cc.status = :certstatusinprogress AND cc.renewalstatus = :renewalstatusnotdue)";
    $params = array(
        'progstatuscomplete' => STATUS_PROGRAM_COMPLETE,
        'certstatusassigned' => CERTIFSTATUS_ASSIGNED,
        'certstatusinprogress' => CERTIFSTATUS_INPROGRESS,
        'renewalstatusnotdue' => CERTIFRENEWALSTATUS_NOTDUE
    );
    $rs = $DB->get_recordset_sql($sql, $params);

    foreach ($rs as $record) {
        $certcompletion = new stdClass();
        $certcompletion->id = $record->ccid;
        $certcompletion->userid = $record->userid;
        $certcompletion->certifid = $record->certifid;
        $certcompletion->status = $record->status;
        $certcompletion->renewalstatus = $record->renewalstatus;
        $certcompletion->certifpath = $record->certifpath;
        $certcompletion->timecompleted = $record->timecompleted;
        $certcompletion->timewindowopens = $record->timewindowopens;
        $certcompletion->timeexpires = $record->timeexpires;

        $progcompletion = new stdClass();
        $progcompletion->id = $record->pcid;
        $progcompletion->userid = $record->userid;
        $progcompletion->programid = $record->programid;
        $progcompletion->status = $record->progstatus;
        $progcompletion->timecompleted = $record->progtimecompleted;
        $progcompletion->timedue = $record->timedue;

        $errors = certif_get_completion_errors($certcompletion, $progcompletion);

        if (!empty($errors)) {
            $problemkey = certif_get_completion_error_problemkey($errors);

            if ($problemkey == 'error:stateassigned-progstatusincorrect|error:stateassigned-progtimecompletednotempty') {
                // This record suffers from the specific problem we are dealing with and nothing more.
                // Find the "unassigned" history record to be restored.
                $sql = "SELECT *
                          FROM {certif_completion_history}
                         WHERE userid = :userid
                           AND certifid = :certifid
                           AND status = :statuscompleted
                           AND renewalstatus = :renewalstatusnotdue
                           AND unassigned = 1
                         ORDER BY timeexpires DESC";
                $params = array(
                    'userid' => $record->userid,
                    'certifid' => $record->certifid,
                    'statuscompleted' => CERTIFSTATUS_COMPLETED,
                    'renewalstatusnotdue' => CERTIFRENEWALSTATUS_NOTDUE
                );
                $history = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

                if ($history) {
                    // Apply the history record to the current records.
                    $certcompletion->status = CERTIFSTATUS_COMPLETED;
                    $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
                    $certcompletion->certifpath = CERTIFPATH_RECERT;
                    $certcompletion->timecompleted = $history->timecompleted;
                    $certcompletion->timewindowopens = $history->timewindowopens;
                    $certcompletion->timeexpires = $history->timeexpires;

                    $progcompletion->status = STATUS_PROGRAM_COMPLETE;
                    $progcompletion->timecompleted = $history->timecompleted;
                    $progcompletion->timedue = $history->timeexpires;

                    // Save the changed records. This could fail if there is some problem with the history data.
                    if (certif_write_completion($certcompletion, $progcompletion,
                        'Completion updated from history by upgrade TL-8605')) {
                        // If saving was successful, we can remove the history record.
                        certif_delete_completion_history($history->id, 'History deleted by upgrade TL-8605');
                        // Wipe the user's other unassigned flags since they're assigned now (this is a tidy-up step).
                        $params = array('userid' => $record->userid, 'certifid' => $record->certifid, 'unassigned' => 1);
                        $unassigned = $DB->get_records('certif_completion_history', $params);
                        foreach ($unassigned as $unass) {
                            $DB->set_field('certif_completion_history', 'unassigned', 0, array('id' => $unass->id));
                            certif_write_completion_history_log($unass->id, 'Unassigned flag removed by upgrade TL-8605');
                        }
                    }
                }
            }
        }
    }
}

