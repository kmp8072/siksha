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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar
 * @subpackage shezar_core
 */

/**
 * Fix incorrectly upgraded text columns.
 */
function shezar_core_fix_old_upgraded_mssql() {
    global $CFG, $DB, $OUTPUT;

    if ($DB->get_dbfamily() !== 'mssql') {
        return;
    }

    $dbman = $DB->get_manager();

    // Changing the default of field laststatus on table backup_courses to 5.
    $table = new xmldb_table('backup_courses');
    $field = new xmldb_field('laststatus', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '5', 'lastendtime');
    $dbman->change_field_default($table, $field);

    // All these text columns should be NOT NULL.
    $candidates = array(
        'appraisal_event_message' => array('content'),
        'assign' => array('intro'),
        'badge' => array('message', 'messagesubject'),
        'badge_issued' => array('message', 'uniquehash'),
        'facetoface_notification' => array('body'),
        'facetoface_notification_tpl' => array('body'),
        'feedback_value_history' => array('value'),
        'goal_scale_values' => array('name'),
        'qtype_randomsamatch_options' => array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'),
        'config' => array('value'),
        'config_plugins' => array('value'),
        'course_request' => array('summary', 'reason'),
        'event' => array('description', 'name'),
        'cache_filters' => array('rawtext'),
        'cache_text' => array('formattedtext'),
        'log_queries' => array('sqltext'),
        'scale' => array('scale', 'description'),
        'scale_history' => array('scale', 'description'),
        'role' => array('description'),
        'user_info_field' => array('name'),
        'user_info_data' => array('data'),
        'question_categories' => array('info'),
        'question' => array('questiontext', 'generalfeedback'),
        'question_answers' => array('answer', 'feedback'),
        'question_hints' => array('hint'),
        'question_states' => array('answer'),
        'question_sessions' => array('manualcomment'),
        'mnet_host' => array('public_key'),
        'mnet_rpc' => array('help', 'profile'),
        'events_queue' => array('eventdata'),
        'grade_outcomes' => array('fullname'),
        'grade_outcomes_history' => array('fullname'),
        'tag_correlation' => array('correlatedtags'),
        'cache_flags' => array('value'),
        'comments' => array('content'),
        'blog_external' => array('url'),
        'backup_controllers' => array('controller'),
        'profiling' => array('data'),
        'qtype_match_options' => array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'),
        'qtype_match_subquestions' => array('questiontext'),
        'question_multianswer' => array('sequence'),
        'qtype_multichoice_options' => array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'),
        'assignment' => array('intro'),
        'assignment_submissions' => array('submissioncomment'),
        'book_chapters' => array('content'),
        'chat' => array('intro'),
        'chat_messages' => array('message'),
        'chat_messages_current' => array('message'),
        'choice' => array('intro'),
        'data' => array('intro'),
        'data_fields' => array('description'),
        'feedback' => array('intro', 'page_after_submit'),
        'feedback_item' => array('presentation'),
        'feedback_value' => array('value'),
        'feedback_valuetmp' => array('value'),
        'forum' => array('intro'),
        'forum_posts' => array('message'),
        'glossary' => array('intro'),
        'glossary_entries' => array('definition'),
        'label' => array('intro'),
        'lesson' => array('conditions'),
        'lesson_pages' => array('contents'),
        'lti' => array('toolurl'),
        'lti_types' => array('baseurl'),
        'quiz' => array('intro', 'questions'),
        'quiz_attempts' => array('layout'),
        'quiz_feedback' => array('feedbacktext'),
        'resource_old' => array('alltext', 'popup'),
        'scorm' => array('intro'),
        'scorm_scoes' => array('launch'),
        'scorm_scoes_data' => array('value'),
        'scorm_scoes_track' => array('value'),
        'survey' => array('intro'),
        'survey_answers' => array('answer1', 'answer2'),
        'survey_analysis' => array('notes'),
        'url' => array('externalurl'),
        'wiki_pages' => array('cachedcontent'),
        'wiki_versions' => array('content'),
        'workshop_old' => array('description'),
        'workshop_elements_old' => array('description'),
        'workshop_rubrics_old' => array('description'),
        'workshop_submissions_old' => array('description'),
        'workshop_grades_old' => array('feedback'),
        'workshop_stockcomments_old' => array('comments'),
        'workshop_comments_old' => array('comments'),
        'block_rss_client' => array('title', 'description'),
        'block_quicklinks' => array('title'),
        'block_shezar_stats' => array('data'),
        'mnetservice_enrol_courses' => array('summary'),
        'course_info_field' => array('fullname'),
        'errorlog' => array('details'),
        'comp_scale_values' => array('name'),
        'comp_template' => array('fullname'),
        'dp_priority_scale' => array('description'),
        'prog_message' => array('mainmessage'),
        'tool_customlang' => array('original'),
    );

    $totalcount = 0;
    foreach ($candidates as $table => $columns) {
        if (!$dbman->table_exists($table)) {
            unset($candidates[$table]);
            continue;
        }
        foreach ($columns as $column) {
            $totalcount++;
        }
    }

    $pbar = new progress_bar('mssqlfixextnulls', 500, true);

    $i = 0;
    foreach ($candidates as $table => $columns) {
        $existingcolumns = $DB->get_columns($table);
        foreach ($columns as $column) {
            if (isset($existingcolumns[$column])) {
                /** @var database_column_info $existing */
                $existing = $existingcolumns[$column];
                if ($existing->meta_type === 'X' and !$existing->not_null) {
                    $DB->execute("UPDATE {{$table}} SET $column = '' WHERE $column IS NULL");
                    $xmldbtable = new xmldb_table($table);
                    $xmldbcolumn = new xmldb_field($column, XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL);
                    $dbman->change_field_notnull($xmldbtable, $xmldbcolumn);
                }
            }
            $i++;
            $pbar->update($i, $totalcount, "Fixed text columns in MS SQL database - $i/$totalcount.");
        }
    }
}

/**
 * Fix old sites upgrade from shezar 1.x,
 */
function shezar_core_fix_upgraded_1x() {
    global $DB;

    $dbman = $DB->get_manager();

    // Changing nullability of field fullmessage on table message to null.
    $table = new xmldb_table('message');
    $field = new xmldb_field('fullmessage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'subject');

    // Launch change of nullability for field fullmessage.
    $dbman->change_field_notnull($table, $field);

    // Changing nullability of field fullmessageformat on table message to null.
    $table = new xmldb_table('message');
    $field = new xmldb_field('fullmessageformat', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'fullmessage');

    // Launch change of nullability for field fullmessageformat.
    $dbman->change_field_notnull($table, $field);

    // Changing nullability of field fullmessage on table message_read to null.
    $table = new xmldb_table('message_read');
    $field = new xmldb_field('fullmessage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'subject');

    // Launch change of nullability for field fullmessage.
    $dbman->change_field_notnull($table, $field);

    // Changing nullability of field fullmessageformat on table message_read to null.
    $table = new xmldb_table('message_read');
    $field = new xmldb_field('fullmessageformat', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'fullmessage');

    // Launch change of nullability for field fullmessageformat.
    $dbman->change_field_notnull($table, $field);

    // Changing the default of field orientation on table certificate to drop it.
    $table = new xmldb_table('certificate');
    $field = new xmldb_field('orientation', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, 'certificatetype');

    // Launch change of default for field orientation.
    $dbman->change_field_default($table, $field);

    // Changing the default of field bordercolor on table certificate to 0.
    $table = new xmldb_table('certificate');
    $field = new xmldb_field('bordercolor', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, '0', 'borderstyle');

    // Launch change of default for field bordercolor.
    $dbman->change_field_default($table, $field);

    // Define field title to be dropped from certificate.
    $table = new xmldb_table('certificate');
    $field = new xmldb_field('title');

    // Conditionally launch drop field title.
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }

    // Define field coursename to be dropped from certificate.
    $table = new xmldb_table('certificate');
    $field = new xmldb_field('coursename');

    // Conditionally launch drop field coursename.
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }

    // Changing nullability of field label on table feedback_item to not null.
    $table = new xmldb_table('feedback_item');
    $field = new xmldb_field('label', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'name');

    // Update existing data to ''.
    $DB->execute("UPDATE {feedback_item} SET label = '' WHERE label IS NULL");

    // Launch change of nullability for field label.
    $dbman->change_field_notnull($table, $field);

    // Changing nullability of field dependvalue on table feedback_item to not null.
    $table = new xmldb_table('feedback_item');
    $field = new xmldb_field('dependvalue', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'dependitem');

    // Update existing data to ''.
    $DB->execute("UPDATE {feedback_item} SET dependvalue = '' WHERE dependvalue IS NULL");

    // Launch change of nullability for field dependvalue.
    $dbman->change_field_notnull($table, $field);

    // Changing nullability of field options on table feedback_item to not null.
    $table = new xmldb_table('feedback_item');
    $field = new xmldb_field('options', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'dependvalue');

    // Update existing data to ''.
    $DB->execute("UPDATE {feedback_item} SET options = '' WHERE options IS NULL");

    // Launch change of nullability for field options.
    $dbman->change_field_notnull($table, $field);

    // Define field count to be dropped from feedback_tracking.
    $table = new xmldb_table('feedback_tracking');
    $field = new xmldb_field('count');

    // Conditionally launch drop field count.
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }

    // Changing precision of field icon on table prog to (255).
    $table = new xmldb_table('prog');
    $field = new xmldb_field('icon', XMLDB_TYPE_CHAR, '255', null, null, null, '', 'usermodified');

    // Launch change of precision for field icon.
    $dbman->change_field_precision($table, $field);

    // Changing nullability of field icon on table course to null.
    $table = new xmldb_table('course');
    $field = new xmldb_field('icon', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'coursetype');

    // Launch change of nullability for field icon.
    $dbman->change_field_notnull($table, $field);
}

// TL-8945 Upgrade pre-9 sites from pos_assignment tables to job_assignment tables.
// We use the pos_assignment table to determine if this upgrade should be performed or not.
// This upgrade is here rather than upgrade.php because upgrade.php is not run when modules are first installed.
// NOTE: Moodle upgrades do not execute this, the pos_assignment table does not exist for them!
// TODO TL-9725: This will be removed after 9 as we will require users upgrade through 9.
function shezar_core_upgrade_multiple_jobs() {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $posassigntable = new xmldb_table('pos_assignment');
    if ($dbman->table_exists($posassigntable)) {
        // There are multiple things that need to occur in a specific order, so they are all done here, rather than
        // in the module that they relate to (e.g. Face to face, Gap).

        // First up increase the timeout - this is going to be a big one.
        upgrade_set_timeout();

        // Part 0: Remove job_assignment table.
        // It was created when the job module install.xml file was processed. We're going to rename
        // pos_assignment to job_assignment, so that existing data is kept.
        $jobassigntable = new xmldb_table('job_assignment');
        if ($dbman->table_exists($jobassigntable)) {
            $dbman->drop_table($jobassigntable);
        }

        // Drop these indexes so that they don't interfere with the upgrade - we will create the indexes we need at the end.
        $index = new xmldb_index('usetyp', XMLDB_INDEX_UNIQUE, array('userid', 'type'));
        if ($dbman->index_exists($posassigntable, $index)) {
            $dbman->drop_index($posassigntable, $index);
        }
        $index = new xmldb_index('use', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        if ($dbman->index_exists($posassigntable, $index)) {
            $dbman->drop_index($posassigntable, $index);
        }
        $index = new xmldb_index('typ', XMLDB_INDEX_NOTUNIQUE, array('type'));
        if ($dbman->index_exists($posassigntable, $index)) {
            $dbman->drop_index($posassigntable, $index);
        }

        // Part 1: Perform old upgrade steps.
        // Because 9.0 is removing the pos_assignment, prog_pos_assignment and temp_manager tables, all
        // previous upgrades to those tables need to have happened before those tables are removed. To ensure
        // that they are processed in the correct order, those older upgrade steps have been removed and
        // placed here.

        // Hierarchies 2012092600.
        // Remove potential duplicates.
        upgrade_course_completion_remove_duplicates(
            'pos_assignment',
            array('userid', 'type')
        );

        // Hierarchies 2012092600.
        // Cleaning table 'pos_assignment': remove records where userid is NULL or type is NULL.
        $nullrecords = $DB->count_records_select('pos_assignment', 'userid IS NULL OR type IS NULL');
        if ($nullrecords > 0) {
            $DB->delete_records_select('pos_assignment', 'userid IS NULL OR type IS NULL');
        }
        $useridfield = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'organisationid');
        $typefield = new xmldb_field('type', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '1', 'reportstoid');
        if ($dbman->field_exists($posassigntable, $useridfield) && $dbman->field_exists($posassigntable, $typefield)) {
            $dbman->change_field_notnull($posassigntable, $useridfield);
            $dbman->change_field_notnull($posassigntable, $typefield);
            $dbman->change_field_default($posassigntable, $typefield);
        }

        // Hierarchies 2013041300.
        $posassigntable = new xmldb_table('pos_assignment');
        $appraiserid = new xmldb_field('appraiserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');
        if (!$dbman->field_exists($posassigntable, $appraiserid)) {
            $dbman->add_field($posassigntable, $appraiserid);
            $appraiserkey = new xmldb_key('posassi_app_fk', XMLDB_KEY_FOREIGN, array('appraiserid'), 'user', array('id'));
            $dbman->add_key($posassigntable, $appraiserkey);
        }

        // Hierarchies 2014070800. This code has been greatly simplified, because we will be recalculating
        // manager paths after we migrate the pos_assignment data to job_assignment table.
        // Clean up the pos_assignment table for deleted managers.
        $sql = "UPDATE {pos_assignment}
                   SET managerid = NULL
                 WHERE managerid IN (SELECT u.id
                                       FROM {user} u
                                      WHERE u.deleted = 1)";
        $DB->execute($sql);

        // Face to face 2014100902.
        // As with hierarchies, facetoface upgrades to some columns need to happen before they are renamed or removed.
        $facetofacetable = new xmldb_table('facetoface');
        $field = new xmldb_field('selectpositiononsignup', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $field->setComment('Users with multiple positions will select one on signup');
        if (!$dbman->field_exists($facetofacetable, $field)) {
            $dbman->add_field($facetofacetable, $field);
        }

        $field = new xmldb_field('forceselectposition', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $field->setComment('Error if no suitable position is available when signing up');
        if (!$dbman->field_exists($facetofacetable, $field)) {
            $dbman->add_field($facetofacetable, $field);
        }

        $facetofacesignupstable = new xmldb_table('facetoface_signups');
        $field = new xmldb_field('positionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $field->setComment('If required, the position the user is doing the training for');
        if (!$dbman->field_exists($facetofacesignupstable, $field)) {
            $dbman->add_field($facetofacesignupstable, $field);
        }

        $field = new xmldb_field('positiontype', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $field->setComment('If required, the position type (prim, sec, asp) the user is doing the training for');
        if (!$dbman->field_exists($facetofacesignupstable, $field)) {
            $dbman->add_field($facetofacesignupstable, $field);
        }

        $field = new xmldb_field('positionassignmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $field->setComment('If required, the position assignment the user is doing the training for');
        if (!$dbman->field_exists($facetofacesignupstable, $field)) {
            $dbman->add_field($facetofacesignupstable, $field);
        }

        // Part 2: Move aspirational position data into new table.
        require_once($CFG->dirroot . '/shezar/gap/db/upgradelib.php');
        shezar_gap_install_aspirational_pos();

        // Part 3: Convert pos_assignment table to job_assignment.
        $now = time();

        // Rename type => sortorder, timevalidfrom => startdate, timevalidto => enddate.
        $dbman->rename_field($posassigntable, new xmldb_field('type', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '1', 'reportstoid'), 'sortorder');
        $dbman->rename_field($posassigntable, new xmldb_field('timevalidfrom', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'description'), 'startdate');
        $dbman->rename_field($posassigntable, new xmldb_field('timevalidto', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'startdate'), 'enddate');

        // Add new fields.
        $fields = array(
            // Position assignment data will be filled in later, then the field will be changed to XMLDB_NOTNULL.
            new xmldb_field('positionassignmentdate', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0', 'positionid'),
            new xmldb_field('managerjaid', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'organisationid'),
            new xmldb_field('managerjapath', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null, 'managerjaid'),
            new xmldb_field('tempmanagerjaid', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'managerjapath'),
            new xmldb_field('tempmanagerexpirydate', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'tempmanagerjaid')
        );
        foreach ($fields as $field) {
            if (!$dbman->field_exists($posassigntable, $field)) {
                $dbman->add_field($posassigntable, $field);
            }
        }

        // Allow fullname to be empty and define a default (null).
        $field = new xmldb_field('fullname', XMLDB_TYPE_TEXT, null, null, null, null, null, 'id');
        $dbman->change_field_notnull($posassigntable, $field);
        $dbman->change_field_default($posassigntable, $field);

        // Create default sortorder 1 records where there is only a sortorder 2.
        $modifiedid = (int)get_admin()->id;
        $sql = "INSERT INTO {pos_assignment} (userid, idnumber, sortorder, timecreated, timemodified, usermodified, positionassignmentdate)
                    (SELECT userid, 1, 1, {$now}, {$now}, {$modifiedid}, {$now}
                       FROM {pos_assignment}
                      WHERE id IN (
                        SELECT sec.id
                          FROM {pos_assignment} sec
                     LEFT JOIN {pos_assignment} pri ON sec.userid = pri.userid AND pri.sortorder = 1
                         WHERE pri.id IS NULL AND sec.sortorder = 2))";
        $DB->execute($sql);

        // Ensure that idnumber is unique.
        // First put sortorder into idnumber where it is empty.
        $DB->execute("UPDATE {pos_assignment} SET idnumber = sortorder WHERE idnumber IS NULL OR idnumber = ''");
        // Then find all duplicates.
        $duplicatessql = "SELECT pa.id
                            FROM {pos_assignment} pa
                            JOIN (SELECT userid, idnumber, COUNT(id) as countid
                                    FROM {pos_assignment}
                                   GROUP BY userid, idnumber
                                  HAVING COUNT(id) > 1) dup
                              ON pa.userid = dup.userid AND pa.idnumber = dup.idnumber";
        $duplicateids = $DB->get_fieldset_sql($duplicatessql);
        $batches = array_chunk($duplicateids, $DB->get_max_in_params());
        unset($duplicateids);
        // Append the sortorder where the idnumber isn't unique, done in batches.
        foreach ($batches as $batch) {
            list($dupsql, $dupparams) = $DB->get_in_or_equal($batch);
            $sql = "UPDATE {pos_assignment}
                       SET idnumber = " . $DB->sql_concat('idnumber', "' ['", 'sortorder', "']'") . "
                     WHERE id {$dupsql}";
            $DB->execute($sql, $dupparams);
        }
        unset($duplicateids);

        // Create default manager sortorder 1 records where the managers have none already.
        $now = time();
        $modifiedid = (int)get_admin()->id;
        $sql = "INSERT INTO {pos_assignment} (userid, idnumber, sortorder, timecreated, timemodified, usermodified, positionassignmentdate)
                    (SELECT DISTINCT staffpa.managerid, 1, 1, {$now}, {$now}, {$modifiedid}, {$now}
                       FROM {pos_assignment} staffpa
                  LEFT JOIN {pos_assignment} managerpa ON staffpa.managerid = managerpa.userid AND managerpa.sortorder = 1
                      WHERE managerpa.id IS NULL AND staffpa.managerid IS NOT NULL)";
        $DB->execute($sql);

        // Create default temp manager sortorder 1 records where the temp managers have none already.
        // Done here rather than later becase we're going to calculate the managerjapths next, on all records, regardless of manager.
        $tempmantable = new xmldb_table('temporary_manager');
        if ($dbman->table_exists($tempmantable)) {
            $sql = "INSERT INTO {pos_assignment} (userid, idnumber, sortorder, timecreated, timemodified, usermodified, positionassignmentdate)
                        (SELECT DISTINCT tm.tempmanagerid, 1, 1, {$now}, {$now}, {$modifiedid}, {$now}
                           FROM {temporary_manager} tm
                      LEFT JOIN {pos_assignment} pa ON tm.tempmanagerid = pa.userid AND pa.sortorder = 1
                          WHERE pa.id IS NULL)";
            $DB->execute($sql);
        }

        // Set managerjaid and tempmanagerjaid based on managerid and tempporary_manager.tempmanagerid.
        $allposassigns = $DB->get_recordset_select('pos_assignment', null, null, '', 'id, userid, managerid');
        $alluserfirstposassigns = $DB->get_records('pos_assignment', array('sortorder' => 1), '', 'userid, id');
        if ($dbman->table_exists($tempmantable)) {
            $sql = "SELECT userid, tempmanagerid, MAX(expirytime) AS expirydate
                      FROM {temporary_manager}
                     GROUP BY userid, tempmanagerid";
            $allusertempmanagers = $DB->get_records_sql($sql);
        } else {
            $allusertempmanagers = array();
        }
        $i = 0;
        $total = $DB->count_records('pos_assignment');
        if ($total > 0) {
            $pbar = new progress_bar('updatemanagerjaids', 500, true);
            $pbar->update($i, $total, "Setting up manager and temp manager ids - {$i}/{$total}.");
            foreach ($allposassigns as $posassign) {
                $needsupdate = false;
                if (!empty($posassign->managerid)) {
                    $posassign->managerjaid = $alluserfirstposassigns[$posassign->managerid]->id;
                    $needsupdate = true;
                }
                if (!empty($allusertempmanagers[$posassign->userid])) {
                    $tempmanager = $allusertempmanagers[$posassign->userid];
                    $posassign->tempmanagerjaid = $alluserfirstposassigns[$tempmanager->tempmanagerid]->id;
                    $posassign->tempmanagerexpirydate = $tempmanager->expirydate;
                    $needsupdate = true;
                }
                if ($needsupdate) {
                    $DB->update_record('pos_assignment', $posassign);
                }
                $i++;
                $pbar->update($i, $total, "Setting up manager and temp manager ids - {$i}/{$total}.");
            }
        }

        // Populate managerjapath based on managerjaid.
        $managerrelations = $DB->get_records_menu('pos_assignment', array(), 'id', 'id, managerjaid');
        $i = 0;
        $total = count($managerrelations);
        if ($total > 0) {
            $pbar = new progress_bar('updatemanagerjapaths', 500, true);
            $pbar->update($i, $total, "Setting up manager job assignment paths - {$i}/{$total}.");
            foreach ($managerrelations as $id => $managerjaid) {
                $updatedata = new stdClass();
                $updatedata->id = $id;
                $updatedata->managerjapath = '/' . implode(shezar_get_lineage($managerrelations, $id), '/');
                $DB->update_record('pos_assignment', $updatedata);
                $i++;
                $pbar->update($i, $total, "Setting up manager job assignment paths - {$i}/{$total}.");
            }
        }

        // Remove unused keys.
        $keys = array(
            new xmldb_key('posassi_man_fk', XMLDB_KEY_FOREIGN, ['managerid'], 'user', 'id'),
            new xmldb_key('posassi_rep_fk', XMLDB_KEY_FOREIGN, ['reportstoid'], 'role_assignments', 'id')
        );
        foreach ($keys as $key) {
            $dbman->drop_key($posassigntable, $key);
        }

        // Remove unused fields.
        $fields = array(
            new xmldb_field('managerid'),
            new xmldb_field('managerpath'),
            new xmldb_field('reportstoid'),
        );
        foreach ($fields as $field) {
            if ($dbman->field_exists($posassigntable, $field)) {
                $dbman->drop_field($posassigntable, $field);
            }
        }

        // Add new keys.
        $keys = array(
            new xmldb_key('job_usermodified_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', array('id')),
            new xmldb_key('job_managerjaid_fk', XMLDB_KEY_FOREIGN, array('managerjaid'), 'job_assignment', array('id')),
            new xmldb_key('job_tempmanagerjaid_fk', XMLDB_KEY_FOREIGN, array('tempmanagerjaid'), 'job_assignment', array('id')),
        );
        foreach ($keys as $key) {
            $dbman->add_key($posassigntable, $key);
        }

        // Fix field types on existing fields.
        // Field idnumber is now not null.
        $field = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'shortname');
        $dbman->change_field_notnull($posassigntable, $field);
        // Field sortorder now has no default.
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'appraiserid');
        $dbman->change_field_default($posassigntable, $field);
        // Field positionassignmentdate now has no default.
        $field = new xmldb_field('positionassignmentdate', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'positionid');
        $dbman->change_field_default($posassigntable, $field);

        // Add new indexes.
        $indexes = array(
            new xmldb_index('job_id_ix', XMLDB_INDEX_UNIQUE, array('id')),
            new xmldb_index('job_useidn_ix', XMLDB_INDEX_UNIQUE, array('userid', 'idnumber')),
            new xmldb_index('job_usesor_ix', XMLDB_INDEX_UNIQUE, array('userid', 'sortorder')),
        );
        foreach ($indexes as $index) {
            if (!$dbman->index_exists($posassigntable, $index)) {
                $dbman->add_index($posassigntable, $index);
            }
        }

        // Rename table.
        $dbman->rename_table($posassigntable, 'job_assignment');

        // Part 4: Delete pos_assignment_history table.
        // This table is unused and can't contain any data (unless there are customisations).
        $posasignhisttable = new xmldb_table('pos_assignment_history');
        if ($dbman->table_exists($posasignhisttable)) {

            // If there is any data in the table then we'll skip deleting it. It's not doing any harm being there.
            // If people are using it in customisations then they'll have to upgrade it themselves.
            if ($DB->count_records('pos_assignment_history') == 0) {
                $dbman->drop_table($posasignhisttable);
            }
        }

        // Part 5: Move prog_pos_assignment data into job_assignment and remove unused table.
        $progposassigntable = new xmldb_table('prog_pos_assignment');
        if ($dbman->table_exists($progposassigntable)) {

            $sql = "UPDATE {job_assignment}
                       SET positionassignmentdate =
                           (SELECT MAX(ppa.timeassigned)
                              FROM {prog_pos_assignment} ppa
                             WHERE ppa.userid = {job_assignment}.userid
                               AND ppa.positionid = {job_assignment}.positionid
                               AND ppa.type = {job_assignment}.sortorder)
                     WHERE EXISTS
                           (SELECT 1
                              FROM {prog_pos_assignment} ppa
                             WHERE ppa.userid = {job_assignment}.userid
                               AND ppa.positionid = {job_assignment}.positionid
                               AND ppa.type = {job_assignment}.sortorder)";
            $DB->execute($sql);

            // If there is still no position assignment date, set it to the current time.
            $sql = "UPDATE {job_assignment}
                       SET positionassignmentdate = :now
                     WHERE positionassignmentdate = 0";
            $DB->execute($sql, array('now' => $now));

            $dbman->drop_table($progposassigntable);

            // Remove the default value and prevent null now that all records have a value.
            $field = new xmldb_field('positionassignmentdate', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'positionid');
            $dbman->change_field_default($jobassigntable, $field);
        }

        // Part 6: Remove unused temporary manager table.
        $tempmantable = new xmldb_table('temporary_manager');
        if ($dbman->table_exists($tempmantable)) {
            $dbman->drop_table($tempmantable);
        }

        // Part 7: Update appraisal assignments to point to new job assignment records. This assumes
        // that all primary pos_assignment records were created as sortorder 1 job assignments,
        // so there should be no changes to managers etc. Worst case, they will be tidied up by cron.

        $appruserassigntable = new xmldb_table('appraisal_user_assignment');
        if ($dbman->table_exists($appruserassigntable)) {
            // Create default appriasee sortorder 1 records where the appraisees have none already.
            $sql = "INSERT INTO {job_assignment} (userid, idnumber, sortorder, managerjapath, timecreated, timemodified, usermodified, positionassignmentdate)
                        (SELECT DISTINCT aua.userid, 1, 1, 'setme', {$now}, {$now}, {$modifiedid}, {$now}
                           FROM {appraisal_user_assignment} aua
                      LEFT JOIN {job_assignment} ja ON aua.userid = ja.userid AND ja.sortorder = 1
                          WHERE ja.id IS NULL)";
            $DB->execute($sql);

            // The user can't have a manager, so the managerjapath is trivial.
            $sql = "UPDATE {job_assignment} SET managerjapath = " . $DB->sql_concat("'/'", 'id') . " WHERE managerjapath = 'setme'";
            $DB->execute($sql);

            $field = new xmldb_field('jobassignmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');
            if (!$dbman->field_exists($appruserassigntable, $field)) {

                // Add jobassignmentid and jobassignmentlastmodified fields to appraisal_user_assignment.
                $dbman->add_field($appruserassigntable, $field);
                $field = new xmldb_field('jobassignmentlastmodified', XMLDB_TYPE_INTEGER, '18');
                $dbman->add_field($appruserassigntable, $field);

                // And a key.
                $dbman->add_key(
                    $appruserassigntable,
                    new xmldb_key('appruserassi_job_fk', XMLDB_KEY_FOREIGN, ['jobassignmentid'], 'job_assignment', ['id'])
                );

                // Update jobassignmentid and jobassignmentlastmodified fields.
                $sql = "UPDATE {appraisal_user_assignment}
                           SET jobassignmentid =
                                (SELECT ja.id
                                   FROM {job_assignment} ja
                                  WHERE ja.userid = {appraisal_user_assignment}.userid
                                    AND ja.sortorder = 1),
                               jobassignmentlastmodified =
                                (SELECT ja.timemodified
                                   FROM {job_assignment} ja
                                  WHERE ja.userid = {appraisal_user_assignment}.userid
                                    AND ja.sortorder = 1)";
                $DB->execute($sql);
            }
        }

        // Part 8: Face to face.

        // Rename field positionassignmentid to jobassignmentid.
        $table = new xmldb_table('facetoface_signups');
        $oldfield = new xmldb_field('positionassignmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if ($dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'jobassignmentid');
        }

        // Remove positiontype and positionid fields (they always ment to be consistent to positionassignmentid).
        $table = new xmldb_table('facetoface_signups');
        $oldfields = [
            new xmldb_field('positiontype'),
            new xmldb_field('positionid'),
        ];
        foreach ($oldfields as $oldfield) {
            if ($dbman->field_exists($table, $oldfield)) {
                $dbman->drop_field($table, $oldfield);
            }
        }

        // Rename field selectpositiononsignup to selectjobassignmentonsignup.
        $table = new xmldb_table('facetoface');
        $oldfield = new xmldb_field('selectpositiononsignup', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'selectjobassignmentonsignup');
        }

        // Rename field forceselectposition to forceselectjobassignment.
        $table = new xmldb_table('facetoface');
        $oldfield = new xmldb_field('forceselectposition', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'forceselectjobassignment');
        }

        // Part 9: Update program_asignments via manager hierarchies.

        // Todo: If ASSIGNTYPE_MANAGER or ASSIGNTYPE_MANAGERJA aren't defined, we may either want to throw a warning or
        // halt the upgrade.
        if (defined('ASSIGNTYPE_MANAGER') and defined('ASSIGNTYPE_MANAGERJA')) {
            $assigntype_manager = ASSIGNTYPE_MANAGER;
            $assigntype_managerja = ASSIGNTYPE_MANAGERJA;
            $now = time();
            $modifiedid = (int)get_admin()->id;

            // Create default job assignment sortorder 1 records where the manager has none. This might be the case
            // for prog_assignments where the manager no longer has any staff.
            $sql = "INSERT INTO {job_assignment} (userid, idnumber, sortorder, managerjapath, timecreated, timemodified, usermodified, positionassignmentdate)
                    (SELECT DISTINCT pa.assignmenttypeid, 1, 1, 'setme', {$now}, {$now}, {$modifiedid}, {$now}
                       FROM {prog_assignment} pa
                  LEFT JOIN {job_assignment} ja ON pa.assignmenttypeid = ja.userid AND ja.sortorder = 1
                      WHERE pa.assignmenttype = :assigntype_manager
                        AND ja.id IS NULL)";
            $params = array('assigntype_manager' => $assigntype_manager);
            $DB->execute($sql, $params);

            // The user can't have a manager, so the managerjapath is trivial.
            $sql = "UPDATE {job_assignment} SET managerjapath = " . $DB->sql_concat("'/'", 'id') . " WHERE managerjapath = 'setme'";
            $DB->execute($sql);

            if ($assigntype_manager) {
                $sql = "UPDATE {prog_assignment}
                       SET assignmenttypeid = (SELECT ja.id
                                                 FROM {job_assignment} ja
                                                WHERE ja.userid = {prog_assignment}.assignmenttypeid AND ja.sortorder = 1),
                           assignmenttype = :assigntype_managerja
                     WHERE assignmenttype = :assigntype_manager";
                $params = array('assigntype_manager' => $assigntype_manager, 'assigntype_managerja' => $assigntype_managerja);
                $DB->execute($sql, $params);
            }
            unset($assigntype_manager, $assigntype_managerja, $sql, $params);
        }
    }

    // Move signup setting from shezar_core to shezar_job.
    $settingnames = array('allowsignupposition', 'allowsignuporganisation', 'allowsignupmanager');
    foreach ($settingnames as $settingname) {
        $existingsetting = get_config('shezar_hierarchy', $settingname);
        if ($existingsetting !== false) {
            set_config($settingname, $existingsetting, 'shezar_job');
            unset_config($settingname, 'shezar_hierarchy');
        }
    }

    // Move update temporary managers task setting from shezar_core to shezar_job.
    $criteria = array(
        'component' => 'shezar_core',
        'classname' => '\shezar_core\task\update_temporary_managers_task'
    );
    $task = $DB->get_record('task_scheduled', $criteria, 'id');
    if ($task) {
        $task->component = 'shezar_job';
        $task->classname = '\shezar_job\task\update_temporary_managers_task';
        $DB->update_record('task_scheduled', $task);
    }
}