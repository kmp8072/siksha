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
 * @author Ciaran Irvine <ciaran.irvine@shezarlms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar
 * @subpackage shezar_appraisal
 */

/**
 * Local db upgrades for shezar Core
 */

require_once($CFG->dirroot.'/shezar/core/db/utils.php');
require_once($CFG->dirroot.'/shezar/appraisal/lib.php');
require_once($CFG->dirroot.'/shezar/appraisal/db/upgradelib.php');

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_shezar_appraisal_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2013080501) {

        // Define field appraisalscalevalueid to be added to appraisal_review_data.
        $table = new xmldb_table('appraisal_review_data');
        $field = new xmldb_field('appraisalscalevalueid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0,
                'appraisalquestfieldid');

        // Conditionally launch add field appraisalscalevalueid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Appraisal savepoint reached.
        upgrade_plugin_savepoint(true, 2013080501, 'shezar', 'appraisal');
    }

    if ($oldversion < 2014062000) {
        $users = $DB->get_fieldset_select('user', 'id', 'deleted = ? ', array(1));

        $transaction = $DB->start_delegated_transaction();

        if (!empty($users)) {

            $now = time();

            // First try and complete the stage so the user can continue the appraisal.
            $sql = "SELECT ara.*, aua.activestageid, aua.appraisalid, aua.userid AS subjectid
                      FROM {appraisal_role_assignment} ara
                      JOIN {appraisal_user_assignment} aua
                        ON ara.appraisaluserassignmentid = aua.id
                      JOIN {user} u
                        ON ara.userid = u.id
                       AND u.deleted = ?";
            $roleassignments = $DB->get_records_sql($sql, array(1));

            $completionsql = "SELECT 1
                                FROM {appraisal_role_assignment} ara
                           LEFT JOIN {appraisal_stage_data} asd
                                  ON asd.appraisalroleassignmentid = ara.id
                                 AND asd.appraisalstageid = ?
                               WHERE ara.appraisaluserassignmentid = ?
                                 AND ara.userid <> 0
                                 AND asd.timecompleted IS NULL";

            $todb = new stdClass();
            $todb->timecompleted = $now;
            foreach ($roleassignments as $role) {
                $todb->appraisalroleassignmentid = $role->id;
                $todb->appraisalstageid = $role->activestageid;
                $DB->insert_record('appraisal_stage_data', $todb);

                // Check if all assigned roles have completed the appraisal.
                if (!$DB->record_exists_sql($completionsql, array($role->activestageid, $role->appraisaluserassignmentid))) {
                    $stages = $DB->get_records('appraisal_stage', array('appraisalid' => $role->appraisalid), 'timedue, id');

                    // Find the next stage.
                    $currentstage = reset($stages);
                    for ($i = 0; $i < count($stages) - 1; $i++) {
                        if ($currentstage->id == $role->activestageid) {
                            $currentstage = next($stages);
                            $nextstageid = $currentstage->id;
                            break;
                        }
                        $currentstage = next($stages);
                    }

                    // Move to the next stage or mark the appraisal as complete.
                    if (!empty($nextstageid)) {
                        $DB->set_field('appraisal_user_assignment', 'activestageid', $nextstageid,
                            array('userid' => $role->subjectid, 'appraisalid' => $role->appraisalid));
                        $nextstageid = 0;
                    } else {
                        // Mark the user as complete for this appraisal.
                        $DB->set_field('appraisal_user_assignment', 'timecompleted', $now, array('id' => $role->appraisaluserassignmentid));

                        // Check if all users are complete.
                        $unfinished = $DB->count_records('appraisal_user_assignment', array('appraisalid' => $role->appraisalid, 'timecompleted' => null));
                        if (!$unfinished) {
                            // Mark this appraisal as complete.
                            $DB->set_field('appraisal', 'status', appraisal::STATUS_COMPLETED, array('id' => $role->appraisalid));
                            $DB->set_field('appraisal', 'timefinished', $now, array('id' => $role->appraisalid));
                        }
                    }
                }
            }

            // Then flag all the role_assignments as empty. Chunk the data in case there are more than 65535 deleted users.
            $length = 1000;
            $chunked_datarows = array_chunk($users, $length);
            unset($users);
            foreach ($chunked_datarows as $key => $chunk) {
                list($insql, $inparam) = $DB->get_in_or_equal($chunk);
                $sql = "UPDATE {appraisal_role_assignment}
                       SET userid = 0
                       WHERE userid {$insql}";
                $DB->execute($sql, $inparam);
                unset($chunked_datarows[$key]);
                unset($chunk);
                unset($sql);
            }
            unset($chunked_datarows);
        }

        $transaction->allow_commit();

        upgrade_plugin_savepoint(true, 2014062000, 'shezar', 'appraisal');
    }

    if ($oldversion < 2014090100) {
        $transaction = $DB->start_delegated_transaction();
        $records = $DB->get_recordset_select('appraisal_stage', ' timedue > ?', array(0), ' id ASC', 'id, timedue');
        foreach ($records as $record) {
            $timestring = date('H:i:s', $record->timedue);
            if ($timestring !== '23:59:59') {
                $datestring = date('Y-m-d', $record->timedue);
                $datestring .= " 23:59:59";
                if ($newtimestamp = shezar_date_parse_from_format('Y-m-d H:i:s', $datestring, true)) {
                    $DB->set_field('appraisal_stage', 'timedue', $newtimestamp, array('id' => $record->id));
                }
            }
        }
        $transaction->allow_commit();
        upgrade_plugin_savepoint(true, 2014090100, 'shezar', 'appraisal');
    }


    // This is the shezar 2.7 upgrade line.
    // All following versions need to be bumped up during merging from 2.6 until we have separate t2-release-27 branch!


    if ($oldversion < 2014090801) {
        $table = new xmldb_table('appraisal_user_assignment');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecompleted');

        if (!$dbman->field_exists($table, $field)) {
            $transaction = $DB->start_delegated_transaction();

            // TODO: Adding fields while in transaction is not allowed - see T-12870
            $dbman->add_field($table, $field);

            // Migrate status from appraisal status.
            $appraisals = $DB->get_records('appraisal');
            foreach ($appraisals as $appraisal) {
                $sql = "UPDATE {appraisal_user_assignment} SET status = ? WHERE appraisalid = ?";
                $params = array($appraisal->status, $appraisal->id);
                $DB->execute($sql, $params);
            }

            // Finally set the status for all completed users at once.
            $sql = "UPDATE {appraisal_user_assignment} SET status = ? WHERE timecompleted IS NOT NULL";
            $params = array(appraisal::STATUS_COMPLETED);
            $DB->execute($sql, $params);

            $transaction->allow_commit();
        }

        upgrade_plugin_savepoint(true, 2014090801, 'shezar', 'appraisal');
    }

    if ($oldversion < 2014090802) {

        // Define table appraisal_role_changes to be created.
        $table = new xmldb_table('appraisal_role_changes');

        // Adding fields to table appraisal_role_changes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userassignmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('originaluserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('newuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('role', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        // Adding keys to table appraisal_role_changes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for appraisal_role_changes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2014090802, 'shezar', 'appraisal');
    }

    if ($oldversion < 2014090803) {
        // Adding a timecreated field to appraisals role assignments.
        $table = new xmldb_table('appraisal_role_assignment');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2014090803, 'shezar', 'appraisal');
    }

    if ($oldversion < 2014120900) {
        // Fix columns definitions.

        // Fix appraisal_role_assignment.
        $table = new xmldb_table('appraisal_role_assignment');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        // Change of nullability for appraisal_role_assignment.timecreated.
        $dbman->change_field_notnull($table, $field);

        // Fix appraisal_role_changes.
        $table = new xmldb_table('appraisal_role_changes');
        $field = new xmldb_field('originaluserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Change of nullability for appraisal_role_changes.originaluserid.
        $dbman->change_field_notnull($table, $field);
        // Change of nullability for appraisal_role_changes.role.
        $field = new xmldb_field('role', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $dbman->change_field_notnull($table, $field);

        // Fix appraisal_user_assignment.
        $table = new xmldb_table('appraisal_user_assignment');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecompleted');
        // Change default to 0 for appraisal_user_assignment.status.
        $dbman->change_field_default($table, $field);

        upgrade_plugin_savepoint(true, 2014120900, 'shezar', 'appraisal');
    }

    if ($oldversion < 2014120901) {
        // Maintain appraisals static functionality for upgrades in case there are existing appraisals in use.
        set_config('dynamicappraisals', 0);

        upgrade_plugin_savepoint(true, 2014120901, 'shezar', 'appraisal');
    }

    // Add appraisal_user_event table to track events scheduled for specific users.
    if ($oldversion < 2015030201) {

        // Define table to be created.
        $table = new xmldb_table('appraisal_user_event');

        // Add fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('timescheduled', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('eventid', XMLDB_KEY_FOREIGN, array('eventid'), 'appraisal_event', array('id'));

        // Create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2015030201, 'shezar', 'appraisal');
    }

    // TL-7650 Increase size of sortorder fields so that they can handle more than 100 records.
    if ($oldversion <= 2015100201) {
        // Questions table.
        $table = new xmldb_table('appraisal_quest_field');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null, 'descriptionformat');
        $dbman->change_field_precision($table, $field);

        // Pages table.
        $table = new xmldb_table('appraisal_stage_page');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null, 'name');
        $dbman->change_field_precision($table, $field);

        // Scales table - we need to remove the index first, modify the table, then re-add the index.
        $table = new xmldb_table('appraisal_scale');
        $index = new xmldb_index('apprscal_scatyp_ix', XMLDB_INDEX_NOTUNIQUE, array('scaletype'));
        $field = new xmldb_field('scaletype', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null, 'userid');
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $dbman->change_field_precision($table, $field);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Appraisal savepoint reached.
        if ($oldversion < 2015100201) {
            upgrade_plugin_savepoint(true, 2015100201, 'shezar', 'appraisal');
        }
    }

    // JSON encode param1 for all aggregate questions.
    if ($oldversion < 2016041800) {

        appraisals_upgrade_clean_aggregate_params();

        // Appraisal savepoint reached.
        upgrade_plugin_savepoint(true, 2016041800, 'shezar', 'appraisal');
    }

    return true;
}
