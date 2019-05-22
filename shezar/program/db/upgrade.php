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
 * @package shezar
 * @subpackage program
 */

/**
 * Local db upgrades for shezar Core
 */

require_once($CFG->dirroot.'/shezar/core/db/utils.php');


/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_shezar_program_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2012070600) {
        //doublecheck organisationid and positionid tables exist in prog_completion tables (T-9752)
        $table = new xmldb_table('prog_completion');
        $field = new xmldb_field('organisationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecompleted');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('positionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'organisationid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('prog_completion_history');
        $field = new xmldb_field('organisationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'recurringcourseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('positionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'organisationid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        shezar_upgrade_mod_savepoint(true, 2012070600, 'shezar_program');
    }

    if ($oldversion < 2012072700) {

        // a bug in the lang strings would have resulted in too many % symbols being stored in
        // the program messages - update any incorrect messages
        $sql = "UPDATE {prog_message} SET messagesubject = REPLACE(messagesubject, '%%programfullname%%', '%programfullname%'),
                mainmessage = REPLACE(" . $DB->sql_compare_text('mainmessage', 1024) . ", '%%programfullname%%', '%programfullname%')";
        $DB->execute($sql);
        shezar_upgrade_mod_savepoint(true, 2012072700, 'shezar_program');
    }

    if ($oldversion < 2012072701) {
        // Fix context levels on program capabilities
        $like_sql = $DB->sql_like('name', '?');
        $params = array(CONTEXT_PROGRAM, 'shezar/program%');
        $DB->execute("UPDATE {capabilities} SET contextlevel = ? WHERE $like_sql", $params);
        shezar_upgrade_mod_savepoint(true, 2012072701, 'shezar_program');
    }

    if ($oldversion < 2012080300) {
        //get program enrolment plugin
        $program_plugin = enrol_get_plugin('shezar_program');

        // add enrollment plugin to all courses associated with programs
        $program_courses = prog_get_courses_associated_with_programs();
        foreach ($program_courses as $course) {
            //add plugin
            $program_plugin->add_instance($course);
        }
        shezar_upgrade_mod_savepoint(true, 2012080300, 'shezar_program');
    }

    if ($oldversion < 2012080301) {
        //set up role assignment levels
        //allow all roles except guest, frontpage and authenticateduser to be assigned at Program level
        $roles = $DB->get_records('role', array(), '', 'id, archetype');
        $rcl = new stdClass();
        foreach ($roles as $role) {
            if (isset($role->archetype) && ($role->archetype != 'guest' && $role->archetype != 'user' && $role->archetype != 'frontpage')) {
                $rolecontextlevels[$role->id] = CONTEXT_PROGRAM;
                $rcl->roleid = $role->id;
                $rcl->contextlevel = CONTEXT_PROGRAM;
                $DB->insert_record('role_context_levels', $rcl, false);
            }
        }
        shezar_upgrade_mod_savepoint(true, 2012080301, 'shezar_program');
    }

    if ($oldversion < 2012081500) {
        // update completion fields to support signed values
        // as no completion date set uses -1
        $table = new xmldb_table('prog_assignment');
        $field = new xmldb_field('completiontime', XMLDB_TYPE_INTEGER, 10, false, XMLDB_NOTNULL, null, '0', 'includechildren');
        $dbman->change_field_unsigned($table, $field);

        $table = new xmldb_table('prog_completion');
        $field = new xmldb_field('timedue', XMLDB_TYPE_INTEGER, 10, false, XMLDB_NOTNULL, null, '0', 'timestarted');
        $dbman->change_field_unsigned($table, $field);

        $table = new xmldb_table('prog_completion_history');
        $field = new xmldb_field('timedue', XMLDB_TYPE_INTEGER, 10, false, XMLDB_NOTNULL, null, '0', 'timestarted');
        $dbman->change_field_unsigned($table, $field);

        shezar_upgrade_mod_savepoint(true, 2012081500, 'shezar_program');
    }

    if ($oldversion < 2012081503) {
        // Clean up exceptions where users are no longer assigned.
        $exceptionids = $DB->get_fieldset_sql("SELECT e.id
                                      FROM {prog_exception} e
                                      LEFT JOIN {prog_assignment} a ON e.assignmentid = a.id
                                      LEFT JOIN {prog_user_assignment} ua ON ua.assignmentid = a.id AND e.userid = ua.userid
                                      WHERE ua.id IS NULL");
        if (!empty($exceptionids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($exceptionids);
            $DB->execute("DELETE
                          FROM {prog_exception}
                          WHERE id {$insql}
                         ", $inparams);
        }
        shezar_upgrade_mod_savepoint(true, 2012081503, 'shezar_program');
    }

    // Looks like the previous update block is missing a step, fix it and add a new one.
    if ($oldversion < 2013090900) {
        // Clean up exceptions where users are no longer assigned.
        $exceptionids = $DB->get_fieldset_sql("SELECT e.id
                                      FROM {prog_exception} e
                                      LEFT JOIN {prog_assignment} a ON e.assignmentid = a.id
                                      LEFT JOIN {prog_user_assignment} ua ON ua.assignmentid = a.id AND e.userid = ua.userid
                                      WHERE ua.id IS NULL");
        if (!empty($exceptionids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($exceptionids);
            $DB->execute("DELETE
                            FROM {prog_exception}
                            WHERE id {$insql}
                            ", $inparams);
        }
        shezar_upgrade_mod_savepoint(true, 2013090900, 'shezar_program');
    }

    // Add audiencevisible column to programs.
    if ($oldversion < 2013091000) {
        $table = new xmldb_table('prog');
        $field = new xmldb_field('audiencevisible', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 2);

        // Conditionally launch add field audiencevisible to program table.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2013091000, 'shezar_program');
    }

    if ($oldversion < 2013092100) {
        // Certification id - if null then its a normal program, if not null then its a certification.
        $table = new xmldb_table('prog');
        $field = new xmldb_field('certifid', XMLDB_TYPE_INTEGER, '10');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key cerifid (foreign) to be added to prog.
        $table = new xmldb_table('prog');
        $key = new xmldb_key('cerifid', XMLDB_KEY_FOREIGN, array('certifid'), 'certif', array('id'));

        // Launch add key cerifid
        if (!$dbman->find_key_name($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Define field certifpath to be added to prog_courseset. Default is CERTIFPATH_STD.
        $table = new xmldb_table('prog_courseset');
        $field = new xmldb_field('certifpath', XMLDB_TYPE_INTEGER, '2', null, null, null, '1', 'label');

        // Conditionally launch add field certifpath
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field certifcount to be added to course_categories.
        $table = new xmldb_table('course_categories');
        $field = new xmldb_field('certifcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'programcount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update counts - can't use an update query because databases handle update differently when using a join/from
        // eg: Mysql uses JOIN, Postgresql uses FROM
        // Joining on category ensures the category exists
        $sql = 'SELECT cat.id,
                    SUM(CASE WHEN p.certifid IS NULL THEN 1 ELSE 0 END) AS programcount,
                    SUM(CASE WHEN p.certifid IS NULL THEN 0 ELSE 1 END) AS certifcount
                FROM {prog} p
                JOIN {course_categories} cat ON cat.id = p.category
                GROUP BY cat.id';
        $cats = $DB->get_records_sql($sql);
        foreach ($cats as $cat) {
            $DB->update_record('course_categories', $cat, true);
        }

        // program savepoint reached
        shezar_upgrade_mod_savepoint(true, 2013092100, 'shezar_program');
    }

    // Drop unused 'prog_exception_data' table and 'locked' field in 'prog_message' table.
    if ($oldversion < 2013101500) {
        $table = new xmldb_table('prog_exception_data');

        // Conditionally drop the table.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('prog_message');
        $field = new xmldb_field('locked');

        // Conditionally drop the field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2013101500, 'shezar_program');
    }

    // Add customfield support to programs.
    if ($oldversion < 2014030500) {
        // Define table prog_info_field to be created.
        $table = new xmldb_table('prog_info_field');

        // Adding fields to table prog_info_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('locked', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('required', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('forceunique', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('defaultdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param1', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param2', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param3', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param4', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param5', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024', null, null, null, null);

        // Adding keys to table prog_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for prog_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table prog_info_data to be created.
        $table = new xmldb_table('prog_info_data');

        // Adding fields to table prog_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table prog_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fieldid', XMLDB_KEY_FOREIGN, array('fieldid'), 'prog_info_field', array('id'));

        // Conditionally launch create table for prog_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('prog_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'prog_info_data', array('id'));
        $table->add_index('value', null, array('value'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2014030500, 'shezar_program');
    }

    if ($oldversion < 2014030600) {
        // Add reason for denying or approving a program extension.
        $table = new xmldb_table('prog_extension');
        $field = new xmldb_field('reasonfordecision', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2014030600, 'shezar_program');
    }

    if ($oldversion < 2014061600) {
        // Drop unused categoryid field accidentally added during 2.6 (2014030500) upgrade.
        $table = new xmldb_table('prog_info_field');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, 20);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2014061600, 'shezar_program');
    }

    if ($oldversion < 2014110703) {
        // Remove duplicate records in prog_completion table and create an index to avoid future duplications.
        // Define index progcomp_prouscou_uix (unique) to be added to prog_completion.
        $table = new xmldb_table('prog_completion');
        $index = new xmldb_index('progcomp_prouscou_uix', XMLDB_INDEX_UNIQUE, array('programid', 'userid', 'coursesetid'));

        // This could take a while.
        raise_memory_limit(MEMORY_HUGE);

        // Conditionally launch add index progcomp_prouscou_uix.
        if (!$dbman->index_exists($table, $index)) {
            // Clean up all instances of duplicate records.
            shezar_upgrade_delete_duplicate_records(
                'prog_completion',
                array('programid', 'userid', 'coursesetid'),
                'status DESC, timedue DESC',
                'shezar_prog_completion_to_history'
            );

            // Add indexes to prevent new duplicates.
            $dbman->add_index($table, $index);
        }

        // Define index proguserassi_prous_uix (unique) to be added to prog_completion.
        $table = new xmldb_table('prog_user_assignment');
        $index = new xmldb_index('proguserassi_prous_uix', XMLDB_INDEX_UNIQUE, array('programid', 'userid', 'assignmentid'));

        // Conditionally launch add index proguserassi_prous_uix.
        if (!$dbman->index_exists($table, $index)) {
            // Clean up all instances of duplicate records.
            shezar_upgrade_delete_duplicate_records(
                'prog_user_assignment',
                array('programid', 'userid', 'assignmentid'),
                'timeassigned DESC'
            );

            // Add indexes to prevent new duplicates.
            $dbman->add_index($table, $index);
        }
        shezar_upgrade_mod_savepoint(true, 2014110703, 'shezar_program');
    }

    if ($oldversion < 2014121900) {
        // Define field mincourses to be added to prog_courseset.
        $table = new xmldb_table('prog_courseset');
        $field = new xmldb_field('mincourses', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'completiontype');

        // Conditionally launch add field mincourses.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field coursesumfield to be added to prog_courseset.
        $table = new xmldb_table('prog_courseset');
        $field = new xmldb_field('coursesumfield', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'mincourses');

        // Conditionally launch add field coursesumfield.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Define key coursesumfield (foreign) to be added to prog_courseset.
            $table = new xmldb_table('prog_courseset');
            $key = new xmldb_key('coursesumfield', XMLDB_KEY_FOREIGN, array('coursesumfield'), 'course_info_field', array('id'));

            // Launch add key coursesumfield.
            $dbman->add_key($table, $key);
        }

        // Define field coursesumfieldtotal to be added to prog_courseset.
        $table = new xmldb_table('prog_courseset');
        $field = new xmldb_field('coursesumfieldtotal', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'coursesumfield');

        // Conditionally launch add field coursesumfieldtotal.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2014121900, 'shezar_program');
    }

    // TL-6581 Add assignmentsdeferred to prog.
    if ($oldversion < 2015030202) {

        // Define field and table.
        $table = new xmldb_table('prog');
        $field = new xmldb_field('assignmentsdeferred', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Conditionally add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015030202, 'shezar_program');
    }

    if ($oldversion < 2015030203) {
        // Remove references to deleted courses in programs or certifications
        // also deletes empty coursesets if they are created as they are not allowed.

        $missingcoursesetcourses = $DB->get_records_sql('SELECT cc.id from {prog_courseset_course} cc LEFT JOIN {course} c ON cc.courseid = c.id WHERE c.id IS null');
        $missingcoursesetcourses = array_keys($missingcoursesetcourses);

        $transaction = $DB->start_delegated_transaction();

        // Delete any broken records.
        if (!empty($missingcoursesetcourses)) {
            list($missingcoursesql, $missingcourseparams) = $DB->get_in_or_equal($missingcoursesetcourses);
            $DB->delete_records_select('prog_courseset_course', "id {$missingcoursesql}", $missingcourseparams);

            // Get IDs of empty coursesets so we can delete them.
            $emptycoursesets = $DB->get_fieldset_sql('SELECT cs.id FROM {prog_courseset} cs LEFT JOIN {prog_courseset_course} c ON cs.id = c.coursesetid WHERE c.coursesetid IS NULL GROUP BY cs.id');

            if (!empty($emptycoursesets)) {
                list($insql, $inparams) = $DB->get_in_or_equal($emptycoursesets);
                $DB->delete_records_select('prog_courseset', "id {$insql}", $inparams);
            }
        }

        $transaction->allow_commit();

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015030203, 'shezar_program');
    }

    if ($oldversion < 2015062600) {
        // Define field allowrequestextensions to be added to prog.
        $table = new xmldb_table('prog');
        $field = new xmldb_field('allowextensionrequests', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'assignmentsdeferred');

        // Conditionally launch add field allowrequestextensions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015062600, 'shezar_program');
    }

    if ($oldversion < 2015082500) {
        $now = time();
        $warnings = array();
        $problems = array();

        $programs = $DB->get_records('prog');
        foreach ($programs as $program) {
            // All unavailable programs.
            if ($program->available == 0) {
                $availdate = userdate($program->availablefrom, get_string('strfdateshortmonth', 'langconfig'));
                $info = "(pid: {$program->id})";

                if ($program->availablefrom <= $now && $program->availableuntil >= $now) {
                    // Program was unavailable and will become available on the next cron run, switch it now.
                    $program->available = 1;
                    $DB->update_record('prog', $program);

                    // Now log and output the action.
                    $type = 'Program Availablility Notification ' . $info;
                    $message = "Program \"{$program->fullname}\" became available on {$availdate}, it has been set to available.
                        If this does not sound correct please review the settings for the program.";
                    upgrade_log(UPGRADE_LOG_NOTICE, 'shezar_program', $type, $message);
                    echo html_writer::tag('div', $message, array('class' => 'alert notifynotice'));
                } else if ($program->availablefrom > $now) {
                    // Program will become available in the future,
                    // nothing to do but output a notification and log.
                    $type = 'Program Availablility Notification ' . $info;
                    $message = "Program \"{$program->fullname}\" will automatically become available on {$availdate}.
                        If this does not sound correct please review the settings for the program.";
                    upgrade_log(UPGRADE_LOG_NOTICE, 'shezar_program', $type, $message);
                    echo html_writer::tag('div', $message, array('class' => 'alert notifynotice'));
                } else if ($program->availablefrom == 0 && $program->availableuntil == 0) {
                    // Stuck, these are unavailable with no dates set.
                    $program->available = 1;
                    $DB->update_record('prog', $program);

                    // Now log and output the action.
                    $type = 'Program Availablility Problem ' . $info;
                    $message = "Program \"{$program->fullname}\" was marked as unavailable with no availability dates, it has been set to available.
                        If this does not sound correct please review the settings for the program";
                    upgrade_log(UPGRADE_LOG_NOTICE, 'shezar_program', $type, $message);
                    echo html_writer::tag('div', $message, array('class' => 'alert notifyproblem'));
                }
            }
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015082500, 'shezar_program');
    }

    // TL-7970 Add program completion log table.
    // Duplicated in certs upgrade, because this table is required by subsequent cert upgrade steps, possibly BEFORE the
    // program upgrade has occurred. Safe to run in both places, because it checks if the table exists.
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

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016021000, 'shezar_program');
    }

    if ($oldversion < 2016041400) {

        // This finds the row with the max sortorder value for each programid in the table.
        // If the nextsetoperator is not 0 nor 1, it returns the id so it can be updated.
        // where nextsetoperator = 2 or 3 (AND or OR) and the set was the last for that program,
        // exceptions were being thrown.
        $coursesetsql =  "SELECT pc.id
                            FROM {prog_courseset} pc
                           WHERE pc.nextsetoperator > 1
                             AND pc.sortorder = (SELECT MAX(pc2.sortorder)
                                                   FROM {prog_courseset} pc2
                                                  WHERE pc2.programid = pc.programid)";
        $coursesetids = $DB->get_fieldset_sql($coursesetsql);

        if ($coursesetids) {
            // There were coursesets to update.
            list($insql, $inparams) = $DB->get_in_or_equal($coursesetids);
            $updatesql = "UPDATE {prog_courseset}
                             SET nextsetoperator=0
                           WHERE id " . $insql;
            $DB->execute($updatesql, $inparams);
        }

        unset($coursesetsql, $coursesetids, $insql, $inparams);

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016041400, 'shezar_program');
    }

    // TL-9020 Create completion log records for all completion records that don't already have one.
    // This should have been done when the completion log was created, but better late than never.
    // It ensures that when a change is logged, the values that it changed FROM will be in the log.
    if ($oldversion < 2016051100) {
        $now = time();

        $description = $DB->sql_concat(
            "'Snapshot created during upgrade<br><ul><li>Status: '", "pc.status",
            "'</li><li>Due date: '", "pc.timedue",
            "'</li><li>Completion date: '", "pc.timecompleted",
            "'</li></ul>'"
        );
        $sql = "INSERT INTO {prog_completion_log} (programid, userid, changeuserid, description, timemodified)
                     (SELECT pc.programid, pc.userid, 0, {$description}, {$now}
                        FROM {prog_completion} pc
                        JOIN {prog} prog ON pc.programid = prog.id AND pc.coursesetid = 0 AND prog.certifid IS NULL
                   LEFT JOIN {prog_completion_log} pcl ON pcl.programid = pc.programid AND pcl.userid = pc.userid
                       WHERE pcl.id IS NULL)";
        $DB->execute($sql);

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016051100, 'shezar_program');
    }

    return true;
}
