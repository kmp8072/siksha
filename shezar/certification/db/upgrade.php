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
 * @author Jon Sharp <jon.sharp@catalyst-eu.net>
 * @package shezar
 * @subpackage certification
 */

// Certification db upgrades.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/shezar/core/db/utils.php');
require_once($CFG->dirroot.'/shezar/certification/db/upgradelib.php');

/**
 * Certification database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade)
 * @return  boolean $result
 */
function xmldb_shezar_certification_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013111200) {
        // Define field unassigned to be added to certif_completion_history.
        $table = new xmldb_table('certif_completion_history');
        $field = new xmldb_field('unassigned', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field unassigned.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Certification savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2013111200, 'shezar_certification');
    }

    if ($oldversion < 2014110701) {
        // Find orphaned certif_completion records, archive them, delete them.
        $sql = "SELECT cc.* FROM {certif_completion} cc
             LEFT JOIN {prog} p ON cc.certifid = p.certifid
       LEFT OUTER JOIN {prog_user_assignment} pua on p.id = pua.programid AND cc.userid = pua.userid
                 WHERE pua.id IS NULL";
        $orphans = $DB->get_recordset_sql($sql, array());
        $deletecollection = array();
        $deleteids = array();
        $idcount = 0;
        foreach ($orphans as $orphan) {
            $deleteids[] = $orphan->id;
            $idcount++;
            $orphan->timemodified = time();
            $orphan->unassigned = true;
            // Move the record to history.
            $completionhistory = $DB->get_record('certif_completion_history',
                    array('certifid' => $orphan->certifid, 'userid' => $orphan->userid, 'timeexpires' => $orphan->timeexpires));
            if ($completionhistory) {
                $orphan->id = $completionhistory->id;
                $DB->update_record('certif_completion_history', $orphan);
            } else {
                $DB->insert_record('certif_completion_history', $orphan);
            }
            // In case there are many thousands of records chunk the ids for deletion later.
            if ($idcount >= BATCH_INSERT_MAX_ROW_COUNT) {
                $deletecollection[] = $deleteids;
                $deleteids = array();
                $idcount = 0;
            }
        }
        // Store any leftovers.
        if ($idcount > 0) {
            $deletecollection[] = $deleteids;
        }
        foreach ($deletecollection as $key => $idarray) {
            // Remove the original orphaned records.
            if (!empty($idarray)) {
                list($usql, $uparams) = $DB->get_in_or_equal($idarray, SQL_PARAMS_QM);
                $DB->delete_records_select('certif_completion', "id {$usql}", $uparams);
            }
        }
        // Certification savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2014110701, 'shezar_certification');
    }

    // T-13550 Copy timeexpires in certif_completion to timedue in prog_completion for active certifications.
    if ($oldversion < 2015030201) {

        // This could take some time and use a lot of resources.
        set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $countsql = "SELECT COUNT(pc.id) AS c";
        $selectsql = "SELECT pc.id, latestcc.timeexpires AS timedue";
        $basesql = " FROM (SELECT allcc.userid, allcc.certifid, MAX(allcc.timeexpires) AS timeexpires
                             FROM (SELECT cc.userid, cc.certifid, cc.timeexpires
                                     FROM {certif_completion} cc
                                    UNION
                                   SELECT cch.userid, cch.certifid, cch.timeexpires
                                     FROM {certif_completion_history} cch
                                    WHERE cch.unassigned = 0) allcc
                            GROUP BY allcc.userid, allcc.certifid) latestcc
                     JOIN {prog} p ON latestcc.certifid = p.certifid AND latestcc.timeexpires > 0
                     JOIN {prog_completion} pc ON pc.programid = p.id AND pc.userid = latestcc.userid AND pc.coursesetid = 0";

        $total = $DB->count_records_sql($countsql . $basesql);
        if ($total > 0) {
            $i = 0;
            $pbar = new progress_bar('copytimeexpirestotimedue', 500, true);
            $pbar->update($i, $total, "Copying certification time expires to program completion time due - {$i}/{$total}.");

            $progcompletionsrs = $DB->get_recordset_sql($selectsql . $basesql);
            foreach ($progcompletionsrs as $progcompletion) {
                $DB->update_record('prog_completion', $progcompletion, true);
                unset($progcompletion);

                $i++;
                $pbar->update($i, $total, "Copying certification time expires to program completion time due - {$i}/{$total}.");
            }
        }

        // Certification savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015030201, 'shezar_certification');
    }

    if ($oldversion < 2015030202) {
        require_once($CFG->dirroot.'/shezar/certification/lib.php');

        $sql = "UPDATE {certif_completion}
            SET status = ?
            WHERE renewalstatus = ?";
        $DB->execute($sql, array(CERTIFSTATUS_EXPIRED, CERTIFRENEWALSTATUS_EXPIRED));
        // Certification savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015030202, 'shezar_certification');
    }

    // T-14315 Copy earliest timestarted from prog_user_assignment to prog_completion for empty values.
    if ($oldversion < 2015030203) {

        $sql = "UPDATE {prog_completion}
                   SET timestarted =
                       COALESCE ((SELECT MIN(pua.timeassigned)
                                    FROM {prog_user_assignment} pua
                                   WHERE pua.programid = {prog_completion}.programid
                                     AND pua.userid = {prog_completion}.userid
                                     AND pua.timeassigned > 0),
                                 0)
                 WHERE timestarted = 0
                   AND coursesetid = 0";

        $DB->execute($sql);

        // Certification savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015030203, 'shezar_certification');
    }

    // TL-6329 Add minimumactiveperiod to certif.
    if ($oldversion < 2015030204) {

        // Define field and table.
        $table = new xmldb_table('certif');
        $field = new xmldb_field('minimumactiveperiod', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'activeperiod');

        // Conditionally add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Certification savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015030204, 'shezar_certification');
    }

    // TL-6645 Change certification completion report columns to use certification status column instead of program status.
    if ($oldversion < 2015080500) {

        // Rename any existing records.
        reportbuilder_rename_data('columns',
            'certification_completion', 'progcompletion', 'status', 'certcompletion', 'status');
        reportbuilder_rename_data('filters',
            'certification_completion', 'progcompletion', 'status', 'certcompletion', 'status');

        reportbuilder_rename_data('columns',
            'certification_completion', 'progcompletion', 'iscomplete', 'certcompletion', 'iscertified');
        reportbuilder_rename_data('filters',
            'certification_completion', 'progcompletion', 'iscomplete', 'certcompletion', 'iscertified');

        reportbuilder_rename_data('columns',
            'certification_completion', 'progcompletion', 'isnotcomplete', 'certcompletion', 'isnotcertified');
        reportbuilder_rename_data('filters',
            'certification_completion', 'progcompletion', 'isnotcomplete', 'certcompletion', 'isnotcertified');

        reportbuilder_rename_data('columns',
            'certification_completion', 'progcompletion', 'isinprogress', 'certcompletion', 'isinprogress');
        reportbuilder_rename_data('filters',
            'certification_completion', 'progcompletion', 'isinprogress', 'certcompletion', 'isinprogress');

        reportbuilder_rename_data('columns',
            'certification_completion', 'progcompletion', 'isnotstarted', 'certcompletion', 'isnotstarted');
        reportbuilder_rename_data('filters',
            'certification_completion', 'progcompletion', 'isnotstarted', 'certcompletion', 'isnotstarted');

        // Certification savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015080500, 'shezar_certification');
    }

    // TL-7842 Repair completion records affected by bug fixed in TL-6979. F2F records missing archive flag do NOT
    // need to be repaired because this fix will cause the window open code to be run again, causing the archive
    // flag to be set this time.
    if ($oldversion < 2015111600) {
        $sql = "UPDATE {certif_completion}
                   SET renewalstatus = :renewalstatusnotdue
                 WHERE status = :certstatuscompleted
                   AND certifpath = :certifpathrecert
                   AND EXISTS (SELECT pc.id
                                 FROM {prog_completion} pc
                                 JOIN {prog} prog ON pc.programid = prog.id
                                WHERE pc.coursesetid = 0
                                  AND pc.status = :progstatuscomplete
                                  AND pc.userid = {certif_completion}.userid
                                  AND prog.certifid = {certif_completion}.certifid)";
        $params = array('renewalstatusnotdue' => CERTIFRENEWALSTATUS_NOTDUE,
                        'certstatuscompleted' => CERTIFSTATUS_COMPLETED,
                        'certifpathrecert'    => CERTIFPATH_RECERT,
                        'progstatuscomplete'  => STATUS_PROGRAM_COMPLETE);
        $DB->execute($sql, $params);

        // Savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015111600, 'shezar_certification');
    }

    // TL-7970 Add program completion log table.
    // Duplicate of the upgrade step in programs upgrade, because this table is required by subsequent cert upgrade steps,
    // possibly BEFORE the program upgrade has occurred. Safe to run in both places, because it checks if the table exists.
    if ($oldversion < 2016021000) {

        // Define table prog_completion_log to be created.
        $table = new xmldb_table('prog_completion_log');

        // Adding fields to table prog_completion_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('changeuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table prog_completion_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('certif_comp_log_programid_ix', XMLDB_KEY_FOREIGN, array('programid'), 'prog', array('id'));
        $table->add_key('certif_comp_log_userid_ix', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('certif_comp_log_changeuserid_ix', XMLDB_KEY_FOREIGN, array('changeuserid'), 'user', array('id'));

        // Conditionally launch create table for prog_completion_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016021000, 'shezar_certification');
    }

    if ($oldversion < 2016040400) {
        $sql = "DELETE FROM {course_completion_history}
                WHERE courseid NOT IN (SELECT id FROM {course})";
        $DB->execute($sql);

        shezar_upgrade_mod_savepoint(true, 2016040400, 'shezar_certification');
    }

    // TL-9020 Create completion log records for all completion records that don't already have one.
    // This should have been done when the completion log was created, but better late than never.
    // It ensures that when a change is logged, the values that it changed FROM will be in the log.
    // Also create completion logs for certification settings, if they haven't been created already.
    if ($oldversion < 2016042900) {
        // Certification settings logs.
        $sql = "SELECT cert.*, prog.id AS programid
                  FROM {certif} cert
                  JOIN {prog} prog ON cert.id = prog.certifid
             LEFT JOIN {prog_completion_log} pcl ON pcl.programid = prog.id AND pcl.userid IS NULL
                 WHERE pcl.id IS NULL";
        $certs = $DB->get_records_sql($sql);

        $now = time();

        foreach ($certs as $cert) {
            $minimumactiveperiod = '';
            $recertification = '';
            switch ($cert->recertifydatetype) {
                case CERTIFRECERT_COMPLETION:
                    $recertification = 'Use certification completion date';
                    break;
                case CERTIFRECERT_EXPIRY:
                    $recertification = 'Use certification expiry date';
                    break;
                case CERTIFRECERT_FIXED:
                    $recertification = 'Use fixed expiry date';
                    $minimumactiveperiod = '<li>Minimum active period: ' . $cert->minimumactiveperiod . '</li>';
                    break;
            }
            $description = 'Snapshot of certification settings<br>' .
                '<ul><li>Recertification date: ' . $recertification . '</li>' .
                '<li>Active period: ' . $cert->activeperiod . '</li>' .
                $minimumactiveperiod .
                '<li>Window period: ' . $cert->windowperiod . '</li></ul>';

            $record = new stdClass();
            $record->programid = $cert->programid;
            $record->userid = null;
            $record->changeuserid = 0;
            $record->description = $description;
            $record->timemodified = $now;

            $DB->insert_record('prog_completion_log', $record);
        }

        // Certification completion logs, as one big insert/select.
        $description = $DB->sql_concat(
            "'Snapshot created during upgrade<br><ul><li>Status: '", "cc.status",
            "'</li><li>Renewal status: '", "cc.renewalstatus",
            "'</li><li>Certification path: '", "cc.certifpath",
            "'</li><li>Due date: '", "pc.timedue",
            "'</li><li>Completion date: '", "cc.timecompleted",
            "'</li><li>Window open date: '", "cc.timewindowopens",
            "'</li><li>Expiry date: '", "cc.timeexpires",
            "'</li><li>Program status: '", "pc.status",
            "'</li><li>Program completion date: '", "pc.timecompleted",
            "'</li></ul>'"
        );
        $sql = "INSERT INTO {prog_completion_log} (programid, userid, changeuserid, description, timemodified)
                     (SELECT pc.programid, cc.userid, 0, {$description}, {$now}
                        FROM {certif_completion} cc
                        JOIN {prog} prog ON cc.certifid = prog.certifid
                        JOIN {prog_completion} pc ON pc.programid = prog.id AND pc.userid = cc.userid AND pc.coursesetid = 0
                   LEFT JOIN {prog_completion_log} pcl ON pcl.programid = prog.id AND pcl.userid = cc.userid
                       WHERE pcl.id IS NULL)";
        $DB->execute($sql);

        // Savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016042900, 'shezar_certification');
    }

    // TL-8605 Repair completion records affected by bug fixed in TL-6790. Users were "certified" when they were
    // unassigned, then later reassigned. Their program completion record is complete while their certification
    // record in newly assigned. Only restore if there is an "unassigned" history record to restore from. If there
    // is any problem then the records must be fixed manually.
    if ($oldversion < 2016051100) {

        certif_upgrade_fix_reassigned_users();

        // Savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016051100, 'shezar_certification');
    }

    // TL-8675 Change unique constraint on certif_completion_history from timeexpires to timecompleted and timeexpires.
    if ($oldversion < 2016082600) {

        // Define key certif_comp_hist_unq_ix (unique) to be dropped form certif_completion_history.
        $table = new xmldb_table('certif_completion_history');
        $key = new xmldb_key('certif_comp_hist_unq_ix', XMLDB_KEY_UNIQUE, array('certifid', 'userid', 'timeexpires'));
        $dbman->drop_key($table, $key);

        // Define key certif_comp_hist_unq_ix (unique) to be added to certif_completion_history.
        $table = new xmldb_table('certif_completion_history');
        $key = new xmldb_key('certif_comp_hist_unq_ix', XMLDB_KEY_UNIQUE, array('certifid', 'userid', 'timecompleted', 'timeexpires'));
        $dbman->add_key($table, $key);

        // Savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016082600, 'shezar_certification');
    }

    return true;
}
