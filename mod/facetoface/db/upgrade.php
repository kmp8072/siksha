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
 * @package mod_facetoface
 */

// This file keeps track of upgrades to
// the facetoface module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

require_once("$CFG->dirroot/mod/facetoface/db/upgradelib.php");

/**
 *
 * Sends message to administrator listing all updated
 * duplicate custom fields
 * @param array $data
 */
function facetoface_send_admin_upgrade_msg($data) {
    global $SITE;
    //No data - no need to send email
    if (empty($data)) {
        return;
    }

    $table = new html_table();
    $table->head = array('Custom field ID',
                         'Custom field original shortname',
                         'Custom field new shortname');
    $table->data = $data;
    $table->align = array ('center', 'center', 'center');

    $title    = "$SITE->fullname: Face to Face upgrade info";
    $note = 'During the last site upgrade the face-to-face module has been modified. It now
requires session custom fields to have unique shortnames. Since some of your
custom fields had duplicate shortnames, they have been renamed to remove
duplicates (see table below). This could impact on your email messages if you
reference those custom fields in the message templates.';
    $message  = html_writer::start_tag('html') . html_writer::start_tag('head') . html_writer::tag('title', $title) . html_writer::end_tag('head');
    $message .= html_writer::start_tag('body') . html_writer::tag('p', $note). html_writer::table($table,true) . html_writer::end_tag('body') . html_writer::end_tag('html');

    $admin = get_admin();

    email_to_user($admin,
                  $admin,
                  $title,
                  '',
                  $message);

}

function xmldb_facetoface_upgrade($oldversion=0) {
    global $CFG, $USER, $DB, $OUTPUT;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // TODO: remove use of facetoface API because the database may not be upgraded yet!
    require_once($CFG->dirroot . '/mod/facetoface/lib.php');

    $result = true;

    if ($result && $oldversion < 2008050500) {
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('thirdpartywaitlist');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'thirdparty');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2008061000) {
        $table = new xmldb_table('facetoface_submissions');
        $field = new xmldb_field('notificationtype');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2008080100) {
        echo $OUTPUT->notification(get_string('upgradeprocessinggrades', 'facetoface'), 'notifysuccess');
        require_once $CFG->dirroot.'/mod/facetoface/lib.php';

        $transaction = $DB->start_delegated_transaction();
        $DB->debug = false; // too much debug output

        // Migrate the grades to the gradebook
        $sql = "SELECT f.id, f.name, f.course, s.grade, s.timegraded, s.userid,
            cm.idnumber as cmidnumber
            FROM {facetoface_submissions} s
            JOIN {facetoface} f ON s.facetoface = f.id
            JOIN {course_modules} cm ON cm.instance = f.id
            JOIN {modules} m ON m.id = cm.module
            WHERE m.name='facetoface'";
        if ($rs = $DB->get_recordset_sql($sql)) {
            foreach ($rs as $facetoface) {
                $grade = new stdclass();
                $grade->userid = $facetoface->userid;
                $grade->rawgrade = $facetoface->grade;
                $grade->rawgrademin = 0;
                $grade->rawgrademax = 100;
                $grade->timecreated = $facetoface->timegraded;
                $grade->timemodified = $facetoface->timegraded;

                $result = $result && (GRADE_UPDATE_OK == facetoface_grade_item_update($facetoface, $grade));
            }
            $rs->close();
        }
        $DB->debug = true;

        // Remove the grade and timegraded fields from facetoface_submissions
        if ($result) {
            $table = new xmldb_table('facetoface_submissions');
            $field1 = new xmldb_field('grade');
            $field2 = new xmldb_field('timegraded');
            $result = $result && $dbman->drop_field($table, $field1, false, true);
            $result = $result && $dbman->drop_field($table, $field2, false, true);
        }

        $transaction->allow_commit();
    }

    if ($result && $oldversion < 2008090800) {

        // Define field timemodified to be added to facetoface_submissions
        $table = new xmldb_table('facetoface_submissions');
        $field = new xmldb_field('timecancelled');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, 0, 'timemodified');

        // Launch add field
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2009111300) {
        // New fields necessary for the training calendar
        $table = new xmldb_table('facetoface');
        $field1 = new xmldb_field('shortname');
        $field1->set_attributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, 'timemodified');
        $result = $result && $dbman->add_field($table, $field1);

        $field2 = new xmldb_field('description');
        $field2->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'shortname');
        $result = $result && $dbman->add_field($table, $field2);

        $field3 = new xmldb_field('showoncalendar');
        $field3->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'description');
        $result = $result && $dbman->add_field($table, $field3);
    }

    if ($result && $oldversion < 2009111600) {

        $table1 = new xmldb_table('facetoface_session_field');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table1->add_field('possiblevalues', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table1->add_field('required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table1->add_field('defaultvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('isfilter', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table1->add_field('showinsummary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && $dbman->create_table($table1);

        $table2 = new xmldb_table('facetoface_session_data');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('data', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && $dbman->create_table($table2);
    }

    if ($result && $oldversion < 2009111900) {
        // Remove unused field
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('closed');
        $result = $result && $dbman->drop_field($table, $field);
    }

    // Migration of old Location, Venue and Room fields
    if ($result && $oldversion < 2009112300) {
        // Create three new custom fields
        $newfield1 = new stdClass();
        $newfield1->name = 'Location';
        $newfield1->shortname = 'location';
        $newfield1->type = 0; // free text
        $newfield1->required = 1;
        if (!$locationfieldid = $DB->insert_record('facetoface_session_field', $newfield1)) {
            $result = false;
        }

        $newfield2 = new stdClass();
        $newfield2->name = 'Venue';
        $newfield2->shortname = 'venue';
        $newfield2->type = 0; // free text
        $newfield2->required = 1;
        if (!$venuefieldid = $DB->insert_record('facetoface_session_field', $newfield2)) {
            $result = false;
        }

        $newfield3 = new stdClass();
        $newfield3->name = 'Room';
        $newfield3->shortname = 'room';
        $newfield3->type = 0; // free text
        $newfield3->required = 1;
        $newfield3->showinsummary = 0;
        if (!$roomfieldid = $DB->insert_record('facetoface_session_field', $newfield3)) {
            $result = false;
        }

        // Migrate data into the new fields
        $olddebug = $DB->debug;
        $DB->debug = false; // too much debug output

        if ($rs = $DB->get_recordset('facetoface_sessions', array(), '', 'id, location, venue, room')) {
            foreach ($rs as $session) {
                $locationdata = new stdClass();
                $locationdata->sessionid = $session->id;
                $locationdata->fieldid = $locationfieldid;
                $locationdata->data = $session->location;
                $result = $result && $DB->insert_record('facetoface_session_data', $locationdata);

                $venuedata = new stdClass();
                $venuedata->sessionid = $session->id;
                $venuedata->fieldid = $venuefieldid;
                $venuedata->data = $session->venue;
                $result = $result && $DB->insert_record('facetoface_session_data', $venuedata);

                $roomdata = new stdClass();
                $roomdata->sessionid = $session->id;
                $roomdata->fieldid = $roomfieldid;
                $roomdata->data = $session->room;
                $result = $result && $DB->insert_record('facetoface_session_data', $roomdata);
            }
            $rs->close();
        }

        $DB->debug = $olddebug;

        // Drop the old fields
        $table = new xmldb_table('facetoface_sessions');
        $oldfield1 = new xmldb_field('location');
        $result = $result && $dbman->drop_field($table, $oldfield1);
        $oldfield2 = new xmldb_field('venue');
        $result = $result && $dbman->drop_field($table, $oldfield2);
        $oldfield3 = new xmldb_field('room');
        $result = $result && $dbman->drop_field($table, $oldfield3);
    }

    // Migration of old Location, Venue and Room placeholders in email templates
    if ($result && $oldversion < 2009112400) {
        $transaction = $DB->start_delegated_transaction();

        $olddebug = $DB->debug;
        $DB->debug = false; // too much debug output

        $templatedfields = array('confirmationsubject', 'confirmationinstrmngr', 'confirmationmessage',
            'cancellationsubject', 'cancellationinstrmngr', 'cancellationmessage',
            'remindersubject', 'reminderinstrmngr', 'remindermessage',
            'waitlistedsubject', 'waitlistedmessage');

        if ($rs = $DB->get_recordset('facetoface', array(), '', 'id, ' . implode(', ', $templatedfields))) {
            foreach ($rs as $activity) {
                $todb = new stdClass();
                $todb->id = $activity->id;

                foreach ($templatedfields as $fieldname) {
                    $s = $activity->$fieldname;
                    $s = str_replace('[location]', '[session:location]', $s);
                    $s = str_replace('[venue]', '[session:venue]', $s);
                    $s = str_replace('[room]', '[session:room]', $s);
                    $todb->$fieldname = $s;
                }

                $result = $result && $DB->update_record('facetoface', $todb);
            }
            $rs->close();
        }

        $DB->debug = $olddebug;

        $transaction->allow_commit();
    }

    if ($result && $oldversion < 2009120900) {
        // Create Calendar events for all existing Face-to-face sessions
        try {
            $transaction = $DB->start_delegated_transaction();

            if ($records = $DB->get_records('facetoface_sessions', '', '', '', 'id, facetoface')) {
                // Remove all exising site-wide events (there shouldn't be any)
                foreach ($records as $record) {
                    if (!facetoface_remove_session_from_calendar($record, SITEID)) {
                        $result = false;
                        throw new Exception('Could not remove session from site calendar');
                        break;
                    }
                }

                // Add new site-wide events
                foreach ($records as $record) {
                    $session = facetoface_get_session($record->id);
                    $facetoface = $DB->get_record('facetoface', 'id', $record->facetoface);

                    if (!facetoface_add_session_to_calendar($session, $facetoface, 'site')) {
                        $result = false;
                        throw new Exception('Could not add session to site calendar');
                        break;
                    }
                }
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

    }

    if ($result && $oldversion < 2009122901) {

    /// Create table facetoface_session_roles
        $table = new xmldb_table('facetoface_session_roles');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'facetoface_sessions', array('id'));
        $result = $result && $dbman->create_table($table);

    /// Create table facetoface_signups
        $table = new xmldb_table('facetoface_signups');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mailedreminder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('discountcode', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('notificationtype', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'facetoface_sessions', array('id'));
        $result = $result && $dbman->create_table($table);

    /// Create table facetoface_signups_status
        $table = new xmldb_table('facetoface_signups_status');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('signupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('statuscode', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('superceded', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, '0');
        $table->add_field('note', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('signupid', XMLDB_KEY_FOREIGN, array('signupid'), 'facetoface_signups', array('id'));
        $result = $result && $dbman->create_table($table);

    /// Migrate submissions to signups
        $table = new xmldb_table('facetoface_submissions');
        if ($dbman->table_exists($table)) {
            require_once $CFG->dirroot.'/mod/facetoface/lib.php';

            $transaction = $DB->start_delegated_transaction();

            // Get all submissions and loop through
            $rs = $DB->get_recordset('facetoface_submissions');

            foreach ($rs as $submission) {

                // Insert signup
                $signup = new stdClass();
                $signup->sessionid = $submission->sessionid;
                $signup->userid = $submission->userid;
                $signup->mailedreminder = $submission->mailedreminder;
                $signup->discountcode = $submission->discountcode;
                $signup->notificationtype = $submission->notificationtype;

                $id = $DB->insert_record('facetoface_signups', $signup);

                $signup->id = $id;

                // Check facetoface still exists (some of them are missing)
                // Also, we need the course id so we can load the grade
                $facetoface = $DB->get_record('facetoface', 'id', $submission->facetoface);
                if (!$facetoface) {
                    // If facetoface delete, ignore as it's of no use to us now
                    mtrace('Could not find facetoface instance '.$submission->facetoface);
                    continue;
                }

                // Get grade
                $grade = facetoface_get_grade($submission->userid, $facetoface->course, $facetoface->id);

                // Create initial "booked" signup status
                $status = new stdClass();
                $status->signupid = $signup->id;
                $status->statuscode = MDL_F2F_STATUS_BOOKED;
                $status->superceded = ($grade->grade > 0 || $submission->timecancelled) ? 1 : 0;
                $status->createdby = $USER->id;
                $status->timecreated = $submission->timecreated;
                $status->mailed = 0;

                $DB->insert_record('facetoface_signups_status', $status);

                // Create attended signup status
                if ($grade->grade > 0) {
                    $status->statuscode = MDL_F2F_STATUS_FULLY_ATTENDED;
                    $status->grade = $grade->grade;
                    $status->timecreated = $grade->dategraded;
                    $status->superceded = $submission->timecancelled ? 1 : 0;

                    $DB->insert_record('facetoface_signups_status', $status);
                }

                // If cancelled, create status
                if ($submission->timecancelled) {
                    $status->statuscode = MDL_F2F_STATUS_USER_CANCELLED;
                    $status->timecreated = $submission->timecancelled;
                    $status->superceded = 0;

                    $DB->insert_record('facetoface_signups_status', $status);
                }
            }

            $rs->close();
            $transaction->allow_commit();

            /// Drop table facetoface_submissions
            $table = new xmldb_table('facetoface_submissions');
            $result = $result && $dbman->drop_table($table);
        }

    // New field necessary for overbooking
        $table = new xmldb_table('facetoface_sessions');
        $field1 = new xmldb_field('allowoverbook');
        $field1->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'capacity');
        $result = $result && $dbman->add_field($table, $field1);
    }

    if ($result && $oldversion < 2010012000) {
        // New field for storing recommendations/advice
        $table = new xmldb_table('facetoface_signups_status');
        $field1 = new xmldb_field('advice');
        $field1->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null);
        $result = $result && $dbman->add_field($table, $field1);
    }

    if ($result && $oldversion < 2010012001) {
        // New field for storing manager approval requirement
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('approvalreqd');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'showoncalendar');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2010012700) {
        // New fields for storing request emails
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('requestsubject');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'reminderperiod');
        $result = $result && $dbman->add_field($table, $field);

        $field = new xmldb_field('requestinstrmngr');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'requestsubject');
        $result = $result && $dbman->add_field($table, $field);

        $field = new xmldb_field('requestmessage');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'requestinstrmngr');
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2010051000) {
        // Create Calendar events for all existing Face-to-face sessions
        $transaction = $DB->start_delegated_transaction();

        if ($records = $DB->get_records('facetoface_sessions', '', '', '', 'id, facetoface')) {
            // Remove all exising site-wide events (there shouldn't be any)
            foreach ($records as $record) {
                facetoface_remove_session_from_calendar($record, SITEID);
            }

            // Add new site-wide events
            foreach ($records as $record) {
                $session = facetoface_get_session($record->id);
                $facetoface = $DB->get_record('facetoface', 'id', $record->facetoface);

                facetoface_add_session_to_calendar($session, $facetoface, 'site');
            }
        }

        $transaction->allow_commit();

        // Add tables required for site notices
        $table1 = new xmldb_table('facetoface_notice');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table1->add_field('text', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && $dbman->create_table($table1);

        $table2 = new xmldb_table('facetoface_notice_data');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('noticeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('data', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table2->add_index('facetoface_notice_date_fieldid', XMLDB_INDEX_NOTUNIQUE, array('fieldid'));
        $result = $result && $dbman->create_table($table2);
    }

    if ($result && $oldversion < 2010100400) {
        // Remove unused mailed field
        $table = new xmldb_table('facetoface_signups_status');
        $field = new xmldb_field('mailed');
        if ($dbman->field_exists($table, $field)) {
            $result = $result && $dbman->drop_field($table, $field, false, true);
        }

    }

    // 2.0 upgrade line -----------------------------------

    if ($oldversion < 2011120701) {
        // Update existing select fields to use new seperator
        $badrows = $DB->get_records_sql(
            "
                SELECT
                    *
                FROM
                    {facetoface_session_field}
                WHERE
                    possiblevalues LIKE '%;%'
                AND possiblevalues NOT LIKE '%" . CUSTOMFIELD_DELIMITER . "%'
                AND type IN (".CUSTOMFIELD_TYPE_SELECT.",".CUSTOMFIELD_TYPE_MULTISELECT.")
            "
        );

        if ($badrows) {
            $transaction = $DB->start_delegated_transaction();

            foreach ($badrows as $bad) {
                $fixedrow = new stdClass();
                $fixedrow->id = $bad->id;
                $fixedrow->possiblevalues = str_replace(';', CUSTOMFIELD_DELIMITER, $bad->possiblevalues);
                $DB->update_record('facetoface_session_field', $fixedrow);
            }

            $transaction->allow_commit();
        }

        $bad_data_rows = $DB->get_records_sql(
            "
                SELECT
                    sd.id, sd.data
                FROM
                    {facetoface_session_field} sf
                JOIN
                    {facetoface_session_data} sd
                  ON
                    sd.fieldid=sf.id
                WHERE
                    sd.data LIKE '%;%'
                AND sd.data NOT LIKE '%". CUSTOMFIELD_DELIMITER ."%'
                AND sf.type = ".CUSTOMFIELD_TYPE_MULTISELECT
        );

        if ($bad_data_rows) {
            $transaction = $DB->start_delegated_transaction();

            foreach ($bad_data_rows as $bad) {
                $fixedrow = new stdClass();
                $fixedrow->id = $bad->id;
                $fixedrow->data = str_replace(';', CUSTOMFIELD_DELIMITER, $bad->data);
                $DB->update_record('facetoface_session_data', $fixedrow);
            }

            $transaction->allow_commit();
        }

        upgrade_mod_savepoint(true, 2011120701, 'facetoface');
    }

    if ($oldversion < 2011120702) {
        $table = new xmldb_table('facetoface_session_field');
        $index = new xmldb_index('ind_session_field_unique');
        $index->set_attributes(XMLDB_INDEX_UNIQUE, array('shortname'));

        if ($dbman->table_exists($table)) {
            //do we need to check for duplicates?
            if (!$dbman->index_exists($table, $index)) {

                //check for duplicate records and make them unique
                $replacements = array();

                $transaction = $DB->start_delegated_transaction();

                $sql = 'SELECT
                            l.id,
                            l.shortname
                        FROM
                            {facetoface_session_field} l,
                            ( SELECT
                                    MIN(id) AS id,
                                    shortname
                              FROM
                                    {facetoface_session_field}
                              GROUP BY
                                    shortname
                              HAVING COUNT(*)>1
                             ) a
                        WHERE
                            l.id<>a.id
                        AND l.shortname = a.shortname
                ';

                $rs = $DB->get_recordset_sql($sql, null);

                //$rs = facetoface_tbl_duplicate_values('facetoface_session_field','shortname');
                if ($rs !== false) {
                    foreach ($rs as $item) {
                        $data = (object)$item;
                        //randomize the value
                        $data->shortname = $DB->escape($data->shortname.'_'.$data->id);
                        $DB->update_record('facetoface_session_field', $data);
                        $replacements[]=array($item['id'], $item['shortname'], $data->shortname);
                    }
                }

                $transaction->allow_commit();
                facetoface_send_admin_upgrade_msg($replacements);

                //Apply the index
                $dbman->add_index($table, $index);
            }
        }

        upgrade_mod_savepoint(true, 2011120702, 'facetoface');
    }

    if ($oldversion < 2011120703) {

        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add the introformat field
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'intro');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('description');
        if ($dbman->field_exists($table, $field)) {

            // Move all data from description to intro
            $facetofaces = $DB->get_records('facetoface');
            foreach ($facetofaces as $facetoface) {
                $facetoface->intro = $facetoface->description;
                $facetoface->introformat = FORMAT_HTML;
                $DB->update_record('facetoface', $facetoface);
            }

            // Remove the old description field
            $dbman->drop_field($table, $field);
        }

        // facetoface savepoint reached
        upgrade_mod_savepoint(true, 2011120703, 'facetoface');
    }

    if ($oldversion < 2012140605) {
        //Remove additional html anchor reference from existing manager approval request message formats
        $links = array(
            '[Teilnehmerlink]#unbestätigt' => '[Teilnehmerlink]',
            '[attendeeslink]#unapproved' => '[attendeeslink]',
            '[enlaceasistentes] # no aprobados' => '[enlaceasistentes]',
            '[เชื่อมโยงผู้เข้าร่วมประชุม] อนุมัติ #' => '[เชื่อมโยงผู้เข้าร่วมประชุม]',
        );
        //mssql has a problem with ntext columns being used in REPLACE function calls
        $dbfamily = $DB->get_dbfamily();
        foreach ($links as $key => $replacement) {
            if ($dbfamily == 'mssql') {
                $sql = "UPDATE {facetoface} SET requestinstrmngr = CAST(REPLACE(CAST(requestinstrmngr as nvarchar(max)), ?, ?) as ntext)";
            } else {
                $sql = "UPDATE {facetoface} SET requestinstrmngr = REPLACE(requestinstrmngr, ?, ?)";
            }
            $result = $result && $DB->execute($sql, array($key, $replacement));
        }
        $stringmanager = get_string_manager();
        $langs = array("de", "en", "es", "fi", "fr", "he", "hu", "it", "ja", "nl", "pl", "pt_br",
            "sv", "th", "zh_cn");
        $strings = array("cancellationinstrmngr", "confirmationinstrmngr", "requestinstrmngr", "reminderinstrmngr");

        foreach ($langs as $lang) {
            $sql = "UPDATE {facetoface} SET ";
            $params = array();

            foreach ($strings as $str) {
                $remove = $stringmanager->get_string('setting:default' . $str . 'copybelow', 'facetoface', null, $lang);
                if ($dbfamily == 'mssql') {
                    $sql .= "{$str} = CAST(REPLACE(CAST({$str} as nvarchar(max)), ?, '') as ntext)";
                } else {
                    $sql .= "{$str} = REPLACE({$str}, ?, '')";
                }
                $params[] = $remove;

                if ($str != "reminderinstrmngr") {
                    $sql .= ", ";
                }
            }

            $result = $result && $DB->execute($sql, $params);
        }
        // facetoface savepoint reached

        upgrade_mod_savepoint(true, 2012140605, 'facetoface');
    }

    if ($oldversion < 2012140609) {
        //add a field for the user calendar entry checkbox
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('usercalentry');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 1);

        //just double check the field doesn't somehow exist
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        //update the existing showoncalendar field, change true to F2F_CAL_SITE
        $sql = 'UPDATE {facetoface}
                SET showoncalendar = ?
                WHERE showoncalendar = ?';
        $DB->execute($sql, array(F2F_CAL_SITE, F2F_CAL_COURSE));

        upgrade_mod_savepoint(true, 2012140609, 'facetoface');
    }

    if ($oldversion < 2013013000) {
        //add the usermodified field to sessions
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '20', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        //add the sessiontimezone field to sessions_dates
        $table = new xmldb_table('facetoface_sessions_dates');
        $field = new xmldb_field('sessiontimezone', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'sessionid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        //fix if no users had bad timezones set
        //first get default zone
        $fixsessions = false;

        $badzones = shezar_get_bad_timezone_list();
        $goodzones = shezar_get_clean_timezone_list();
        //see what the site config is
        if (isset($CFG->forcetimezone)) {
            $default = $CFG->forcetimezone;
        } else if (isset($CFG->timezone)) {
            $default = $CFG->timezone;
        }
        if($default == 99) {
            //both set to server local time so get system tz
            $default = date_default_timezone_get();
        }
        //only fix if the site setting is not a Moodle offset, and is in the approved list
        if (!is_float($default) && in_array($default, $goodzones)) {
            $fixsessions = true;
        }

        if ($fixsessions) {
            //check no users have deprecated or totally unknown timezones
            list($insql, $inparams) = $DB->get_in_or_equal(array_keys($badzones));
            $sql = "SELECT count(id) from {user} WHERE timezone $insql";
            $badusers = $DB->count_records_sql($sql, $inparams);
            $fullzones = array_merge(array_keys($badzones), array_values($goodzones));
            $fullzones[] = 99;
            list($insql, $inparams) = $DB->get_in_or_equal($fullzones, SQL_PARAMS_QM, 'param', false);
            $sql = "SELECT count(id) from {user} WHERE timezone $insql";
            $unknownusercount = $DB->count_records_sql($sql, $inparams);

            if ($badusers > 0 || $unknownusercount > 0) {
                //some users have bad timezones set
                //output a notice and direct to the new admin tool
                $info = get_string('badusertimezonemessage', 'tool_shezar_timezonefix');
                echo $OUTPUT->notification($info, 'notifynotice');
            } else {
                //only if the site timezone is sensible AND no users have bad zones
                $sql = 'UPDATE {facetoface_sessions_dates} SET sessiontimezone = ?';
                $DB->execute($sql, array($default));
            }
        }
        //sessions created before this upgrade may still need fixing
        $sql = "SELECT count(id) from {facetoface_sessions_dates} WHERE sessiontimezone IS NULL OR " . $DB->sql_compare_text('sessiontimezone', 255) . " = ?";
        $unfixedsessions = $DB->count_records_sql($sql, array(''));
        if ($unfixedsessions > 0) {
            $info = get_string('timezoneupgradeinfomessage', 'facetoface');
            echo $OUTPUT->notification($info, 'notifynotice');
        }
        upgrade_mod_savepoint(true, 2013013000, 'facetoface');
    }
    if ($oldversion < 2013013001) {

        // Define table facetoface_notification_tpl to be created
        $table = new xmldb_table('facetoface_notification_tpl');

        // Set up the comment for the notification templates table.
        $table->setComment('Face-to-face notification templates');

        // Adding fields to table facetoface_notification_tpl
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('managerprefix', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table facetoface_notification_tpl
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table facetoface_notification_tpl
        $table->add_index('title', XMLDB_INDEX_UNIQUE, array('title'));
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));

        // Launch create table for facetoface_notification_tpl
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2013013001, 'facetoface');
    }

    if ($result && $oldversion < 2013013002) {

        // Define table facetoface_notification to be created
        $table = new xmldb_table('facetoface_notification');

        // Set up the comment for the facetoface notification table.
        $table->setComment('Facetoface notifications');

        // Adding fields to table facetoface_notification
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('conditiontype', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('scheduleunit', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('scheduleamount', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('scheduletime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('ccmanager', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('managerprefix', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('booked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('waitlisted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cancelled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofaceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('issent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table facetoface_notification
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_key('facetofaceid', XMLDB_KEY_FOREIGN, array('facetofaceid'), 'facetoface', array('id'));

        // Adding indexes to table facetoface_notification
        $table->add_index('type', XMLDB_INDEX_NOTUNIQUE, array('type'));
        $table->add_index('title', XMLDB_INDEX_NOTUNIQUE, array('title'));
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));
        $table->add_index('issent', XMLDB_INDEX_NOTUNIQUE, array('issent'));

        // Launch create table for facetoface_notification
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2013013002, 'facetoface');
    }

    if ($oldversion < 2013013003) {

        // Define table facetoface_notification_sent to be created
        $table = new xmldb_table('facetoface_notification_sent');

        // Set up the comment for the facetoface notifications sent table.
        $table->setComment('Face-to-face notification reciepts');

        // Adding fields to table facetoface_notification_sent
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        // Adding keys to table facetoface_notification_sent
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('notificationid', XMLDB_KEY_FOREIGN, array('notificationid'), 'facetoface_notification', array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'facetoface_sessions', array('id'));

        // Launch create table for facetoface_notification_sent
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2013013003, 'facetoface');
    }

    if ($oldversion < 2013013004) {
        // Move existing face-to-face messages to the new notification system
        // Get facetoface's
        $facetofaces = $DB->get_records('facetoface');
        if ($facetofaces) {
            // Loop over facetofaces
            foreach ($facetofaces as $facetoface) {
                // Get each message and create notification
                $defaults = array();
                $defaults['facetofaceid'] = $facetoface->id;
                $defaults['courseid'] = $facetoface->course;
                $defaults['type'] = MDL_F2F_NOTIFICATION_AUTO;
                $defaults['booked'] = 0;
                $defaults['waitlisted'] = 0;
                $defaults['cancelled'] = 0;
                $defaults['issent'] = 0;
                $defaults['status'] = 1;
                $defaults['ccmanager'] = 0;

                $confirmation = new facetoface_notification($defaults, false);
                $confirmation->title = $facetoface->confirmationsubject;
                $confirmation->body = text_to_html($facetoface->confirmationmessage);
                $confirmation->conditiontype = MDL_F2F_CONDITION_BOOKING_CONFIRMATION;
                if (!empty($facetoface->confirmationinstrmngr)) {
                    $confirmation->ccmanager = 1;
                    $confirmation->managerprefix = text_to_html($facetoface->confirmationinstrmngr);
                }
                $result = $result && $confirmation->save();

                $waitlist = new facetoface_notification($defaults, false);
                $waitlist->title = $facetoface->waitlistedsubject;
                $waitlist->body = text_to_html($facetoface->waitlistedmessage);
                $waitlist->conditiontype = MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION;
                $result = $result && $waitlist->save();

                $cancellation = new facetoface_notification($defaults, false);
                $cancellation->title = $facetoface->cancellationsubject;
                $cancellation->body = text_to_html($facetoface->cancellationmessage);
                $cancellation->conditiontype = MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION;
                if (!empty($facetoface->cancellationinstrmngr)) {
                    $cancellation->ccmanager = 1;
                    $cancellation->managerprefix = text_to_html($facetoface->cancellationinstrmngr);
                }
                $result = $result && $cancellation->save();

                $reminder = new facetoface_notification($defaults, false);
                $reminder->title = $facetoface->remindersubject;
                $reminder->body = text_to_html($facetoface->remindermessage);
                $reminder->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
                $reminder->scheduleunit = MDL_F2F_SCHEDULE_UNIT_DAY;
                $reminder->scheduleamount = $facetoface->reminderperiod;
                if (!empty($facetoface->reminderinstrmngr)) {
                    $reminder->ccmanager = 1;
                    $reminder->managerprefix = text_to_html($facetoface->reminderinstrmngr);
                }
                $result = $result && $reminder->save();

                if (!empty($facetoface->approvalreqd)) {
                    $request = new facetoface_notification($defaults, false);
                    $request->title = $facetoface->requestsubject;
                    $request->body = text_to_html($facetoface->requestmessage);
                    $request->conditiontype = MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER;
                    if (!empty($facetoface->requestinstrmngr)) {
                        $request->ccmanager = 1;
                        $request->managerprefix = text_to_html($facetoface->requestinstrmngr);
                    }
                    $result = $result && $request->save();
                }
            }
        }

        // Copy over templates from lang files
        $tpl_confirmation = new stdClass();
        $tpl_confirmation->status = 1;
        $tpl_confirmation->title = get_string('setting:defaultconfirmationsubjectdefault', 'facetoface');
        $tpl_confirmation->body = text_to_html(get_string('setting:defaultconfirmationmessagedefault', 'facetoface'));
        $tpl_confirmation->managerprefix = text_to_html(get_string('setting:defaultconfirmationinstrmngrdefault', 'facetoface'));
        $DB->insert_record('facetoface_notification_tpl', $tpl_confirmation);

        $tpl_cancellation = new stdClass();
        $tpl_cancellation->status = 1;
        $tpl_cancellation->title = get_string('setting:defaultcancellationsubjectdefault', 'facetoface');
        $tpl_cancellation->body = text_to_html(get_string('setting:defaultcancellationmessagedefault', 'facetoface'));
        $tpl_cancellation->managerprefix = text_to_html(get_string('setting:defaultcancellationinstrmngrdefault', 'facetoface'));
        $DB->insert_record('facetoface_notification_tpl', $tpl_cancellation);

        $tpl_waitlist = new stdClass();
        $tpl_waitlist->status = 1;
        $tpl_waitlist->title = get_string('setting:defaultwaitlistedsubjectdefault', 'facetoface');
        $tpl_waitlist->body = text_to_html(get_string('setting:defaultwaitlistedmessagedefault', 'facetoface'));
        $DB->insert_record('facetoface_notification_tpl', $tpl_waitlist);

        $tpl_reminder = new stdClass();
        $tpl_reminder->status = 1;
        $tpl_reminder->title = get_string('setting:defaultremindersubjectdefault', 'facetoface');
        $tpl_reminder->body = text_to_html(get_string('setting:defaultremindermessagedefault', 'facetoface'));
        $tpl_reminder->managerprefix = text_to_html(get_string('setting:defaultreminderinstrmngrdefault', 'facetoface'));
        $DB->insert_record('facetoface_notification_tpl', $tpl_reminder);

        $tpl_request = new stdClass();
        $tpl_request->status = 1;
        $tpl_request->title = get_string('setting:defaultrequestsubjectdefault', 'facetoface');
        $tpl_request->body = text_to_html(get_string('setting:defaultrequestmessagedefault', 'facetoface'));
        $tpl_request->managerprefix = text_to_html(get_string('setting:defaultrequestinstrmngrdefault', 'facetoface'));
        $DB->insert_record('facetoface_notification_tpl', $tpl_request);

        // Drop columns from facetoface table
        if ($result) {
            $msg_cols = array(
                'confirmationsubject',
                'confirmationinstrmngr',
                'confirmationmessage',
                'waitlistedsubject',
                'waitlistedmessage',
                'cancellationsubject',
                'cancellationinstrmngr',
                'cancellationmessage',
                'remindersubject',
                'reminderinstrmngr',
                'remindermessage',
                'reminderperiod',
                'requestsubject',
                'requestinstrmngr',
                'requestmessage'
            );

            $table = new xmldb_table('facetoface');
            foreach ($msg_cols as $mc) {
                $field = new xmldb_field($mc);
                if ($dbman->field_exists($table, $field)) {
                    $dbman->drop_field($table, $field);
                }
            }
        }

        upgrade_mod_savepoint(true, 2013013004, 'facetoface');
    }

    if ($oldversion < 2013013005) {
        $table = new xmldb_table('facetoface_signups');
        $field = new xmldb_field('mailedreminder');

        if (!$dbman->field_exists($table, $field)) {
            // Get all sessions with reminders sent that have had
            // the reminder converted to the new style notification
            $sessions = $DB->get_records_sql(
                "
                SELECT
                    fs.sessionid,
                    ss.facetoface AS facetofaceid,
                    fn.id AS notificationid
                FROM
                    {facetoface_signups} fs
                INNER JOIN
                    {facetoface_sessions} ss
                 ON fs.sessionid = ss.id
                INNER JOIN
                    {facetoface_notification} fn
                 ON fn.facetofaceid = ss.facetoface
                WHERE
                    fs.mailedreminder = 1
                AND fn.type = ".MDL_F2F_NOTIFICATION_AUTO."
                AND fn.conditiontype = ".MDL_F2F_CONDITION_BEFORE_SESSION."
                AND fn.scheduletime IS NOT NULL
                GROUP BY
                    fs.sessionid,
                    ss.facetoface,
                    fn.id
                "
            );

            if ($sessions) {
                // Add entries to sent table
                foreach ($sessions as $session) {
                    $record = new stdClass();
                    $record->sessionid = $session->sessionid;
                    $record->notificationid = $session->notificationid;
                    $DB->insert_record('facetoface_notification_sent', $record);
                }
            }

            // Drop column from signups table, already checked it exists.
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013013005, 'facetoface');
    }

    if ($oldversion < 2013013006) {

        // Define table facetoface_room to be created
        $table = new xmldb_table('facetoface_room');

        // Set up comment for the facetoface room table.
        $table->setComment('Table for storing pre-defined facetoface room data');

        // Adding fields to table facetoface_room
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('building', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('address', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('capacity', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('custom', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Adding keys to table facetoface_room
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table facetoface_room
        $table->add_index('custom', XMLDB_INDEX_NOTUNIQUE, array('custom'));

        // Launch create table for facetoface_room
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add roomid field to facetoface_sessions table
        $table = new xmldb_table('facetoface_sessions');

        $field = new xmldb_field('roomid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'discountcost');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Populate new sesion room table with the data from the session custom fields
        $rs = $DB->get_recordset('facetoface_sessions', array(), '', 'id, capacity');

        $fieldmappings = array('room' => 'name', 'venue' => 'building', 'location' => 'address');

        foreach ($rs as $session) {
            $sql = "SELECT f.shortname, d.data
                FROM {facetoface_session_data} d
                INNER JOIN {facetoface_session_field} f ON d.fieldid = f.id
                WHERE d.sessionid = ?
                AND f.shortname IN('room', 'venue', 'location')";
            if ($data = $DB->get_records_sql($sql, array($session->id))) {
                $todb = new stdClass;
                $todb->custom = 1;
                $todb->capacity = $session->capacity;
                foreach ($data as $d) {
                    $todb->{$fieldmappings[$d->shortname]} = $d->data;
                }
                if (!$roomid = $DB->insert_record('facetoface_room', $todb)) {
                    error('Could not populate session room data from custom fields');
                }
                $todb = new stdClass;
                $todb->id = $session->id;
                $todb->roomid = $roomid;
                if (!$DB->update_record('facetoface_sessions', $todb)) {
                    error('Could not update session roomid');
                }
            }
        }

        // Remove location, room and venue custom fields and data
        $DB->delete_records_select('facetoface_session_data',
            "fieldid IN(
                SELECT id FROM {facetoface_session_field}
                WHERE shortname IN('room', 'venue', 'location'))");

        $DB->delete_records_select('facetoface_session_field',
            "shortname IN('room', 'venue', 'location')");

        upgrade_mod_savepoint(true, 2013013006, 'facetoface');
    }

    if ($oldversion < 2013013007) {

        // original table name - to long for XMLDB editor
        $table = new xmldb_table('facetoface_notification_history');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'facetoface_notification_hist');
        }

        // create new table instead
        $table = new xmldb_table('facetoface_notification_hist');

        // Set up the comment for the facetoface notification history table.
        $table->setComment('Notifications history (stores ical event information)');

        // Adding fields to table facetoface_notification_hist
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('sessiondateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('ical_uid', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->add_field('ical_method', XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_notification_hist
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('notificationid', XMLDB_KEY_FOREIGN, array('notificationid'), 'facetoface_notification', array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'facetoface_sessions', array('id'));
        $table->add_key('sessiondateid', XMLDB_KEY_FOREIGN, array('sessiondateid'), 'facetoface_sessions_dates', array('id'));
        $table->add_index('f2f_hist_userid_idx', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Launch create table for facetoface_notification_hist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, 2013013007, 'facetoface');
    }

    if ($oldversion < 2013070900) {
        // Change the cost fields to varchars instead of integers.
        $table = new xmldb_table('facetoface_sessions');
        $costfield = new xmldb_field('normalcost', XMLDB_TYPE_CHAR, '255', null, true, null, '0','details');
        $discountfield = new xmldb_field('discountcost', XMLDB_TYPE_CHAR, '255', null, true, null, '0','normalcost');
        $dbman->change_field_type($table, $costfield);
        $dbman->change_field_type($table, $discountfield);
        upgrade_mod_savepoint(true, 2013070900, 'facetoface');
    }

    if ($oldversion < 2013070901) {

        // Add manager decline notification template.
        if ($dbman->table_exists('facetoface_notification_tpl')) {
            $decline = new stdClass();
            $decline->status = 1;
            $decline->title = get_string('setting:defaultdeclinesubjectdefault', 'facetoface');
            $decline->body = text_to_html(get_string('setting:defaultdeclinemessagedefault', 'facetoface'));
            $decline->managerprefix = text_to_html(get_string('setting:defaultdeclineinstrmngrdefault', 'facetoface'));

            $DB->insert_record('facetoface_notification_tpl', $decline);
        }

        upgrade_mod_savepoint(true, 2013070901, 'facetoface');
    }

    // Re-adding the rooms upgrades because of version conflicts with 2.2, see T-11146.
    if ($oldversion < 2013090200) {

        // Define table facetoface_notification_tpl to be created
        $table = new xmldb_table('facetoface_notification_tpl');

        // Set up the comment for the notification templates table.
        $table->setComment('Face-to-face notification templates');

        // Adding fields to table facetoface_notification_tpl
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('managerprefix', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table facetoface_notification_tpl
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table facetoface_notification_tpl
        $table->add_index('title', XMLDB_INDEX_UNIQUE, array('title'));
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));

        // Launch create table for facetoface_notification_tpl
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2013090200, 'facetoface');
    }

    if ($result && $oldversion < 2013090201) {

        // Define table facetoface_notification to be created
        $table = new xmldb_table('facetoface_notification');

        // Set up the comment for the facetoface notification table.
        $table->setComment('Facetoface notifications');

        // Adding fields to table facetoface_notification
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('conditiontype', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('scheduleunit', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('scheduleamount', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('scheduletime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('ccmanager', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('managerprefix', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('booked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('waitlisted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, '0');
        $table->add_field('cancelled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofaceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('issent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table facetoface_notification
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_key('facetofaceid', XMLDB_KEY_FOREIGN, array('facetofaceid'), 'facetoface', array('id'));

        // Adding indexes to table facetoface_notification
        $table->add_index('type', XMLDB_INDEX_NOTUNIQUE, array('type'));
        $table->add_index('title', XMLDB_INDEX_NOTUNIQUE, array('title'));
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));
        $table->add_index('issent', XMLDB_INDEX_NOTUNIQUE, array('issent'));

        // Launch create table for facetoface_notification
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2013090201, 'facetoface');
    }

    if ($oldversion < 2013090202) {

        // Define table facetoface_notification_sent to be created
        $table = new xmldb_table('facetoface_notification_sent');

        // Set up the comment for the facetoface notifications sent table.
        $table->setComment('Face-to-face notification reciepts');

        // Adding fields to table facetoface_notification_sent
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        // Adding keys to table facetoface_notification_sent
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('notificationid', XMLDB_KEY_FOREIGN, array('notificationid'), 'facetoface_notification', array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'facetoface_sessions', array('id'));

        // Launch create table for facetoface_notification_sent
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2013090202, 'facetoface');
    }

    if ($oldversion < 2013090203) {
        // Move existing face-to-face messages to the new notification system

        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('confirmationinstrmngr');
        if ($dbman->field_exists($table, $field)) {
            // If this field still exists the notifications haven't been transfered yet.
            $facetofaces = $DB->get_records('facetoface');
            if ($facetofaces) {
                // Loop over facetofaces
                foreach ($facetofaces as $facetoface) {

                    // Get each message and create notification
                    $defaults = array();
                    $defaults['facetofaceid'] = $facetoface->id;
                    $defaults['courseid'] = $facetoface->course;
                    $defaults['type'] = MDL_F2F_NOTIFICATION_AUTO;
                    $defaults['booked'] = 0;
                    $defaults['waitlisted'] = 0;
                    $defaults['cancelled'] = 0;
                    $defaults['issent'] = 0;
                    $defaults['status'] = 1;
                    $defaults['ccmanager'] = 0;

                    // Booking confirmation
                    $confirmation = new facetoface_notification($defaults, false);
                    $confirmation->title = $facetoface->confirmationsubject;
                    $confirmation->body = text_to_html($facetoface->confirmationmessage);
                    $confirmation->conditiontype = MDL_F2F_CONDITION_BOOKING_CONFIRMATION;
                    if (!empty($facetoface->confirmationinstrmngr)) {
                        $confirmation->ccmanager = 1;
                        $confirmation->managerprefix = text_to_html($facetoface->confirmationinstrmngr);
                    }
                    $result = $result && $confirmation->save();

                    // Waitlist confirmation.
                    $waitlist = new facetoface_notification($defaults, false);
                    $waitlist->title = $facetoface->waitlistedsubject;
                    $waitlist->body = text_to_html($facetoface->waitlistedmessage);
                    $waitlist->conditiontype = MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION;
                    $result = $result && $waitlist->save();

                    // Booking cancellation.
                    $cancellation = new facetoface_notification($defaults, false);
                    $cancellation->title = $facetoface->cancellationsubject;
                    $cancellation->body = text_to_html($facetoface->cancellationmessage);
                    $cancellation->conditiontype = MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION;
                    if (!empty($facetoface->cancellationinstrmngr)) {
                        $cancellation->ccmanager = 1;
                        $cancellation->managerprefix = text_to_html($facetoface->cancellationinstrmngr);
                    }
                    $result = $result && $cancellation->save();

                    // Booking reminder.
                    $reminder = new facetoface_notification($defaults, false);
                    $reminder->title = $facetoface->remindersubject;
                    $reminder->body = text_to_html($facetoface->remindermessage);
                    $reminder->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
                    $reminder->scheduleunit = MDL_F2F_SCHEDULE_UNIT_DAY;
                    $reminder->scheduleamount = $facetoface->reminderperiod;
                    if (!empty($facetoface->reminderinstrmngr)) {
                        $reminder->ccmanager = 1;
                        $reminder->managerprefix = text_to_html($facetoface->reminderinstrmngr);
                    }
                    $result = $result && $reminder->save();

                    // Booking request.
                    $request = new facetoface_notification($defaults, false);
                    $request->title = $facetoface->requestsubject;
                    $request->body = text_to_html($facetoface->requestmessage);
                    $request->conditiontype = MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER;
                    if (!empty($facetoface->requestinstrmngr)) {
                        $request->ccmanager = 1;
                        $request->managerprefix = text_to_html($facetoface->requestinstrmngr);
                    }
                    $result = $result && $request->save();

                    //Add new notifications for 2.4

                    // Date time changed.
                    $datetimechange = new facetoface_notification($defaults, false);
                    $datetimechange->title = get_string('setting:defaultdatetimechangesubjectdefault', 'facetoface');
                    $datetimechange->body = text_to_html(get_string('setting:defaultdatetimechangemessagedefault', 'facetoface'));
                    $datetimechange->conditiontype = MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE;
                    $datetimechange->booked = 1;
                    $datetimechange->waitlisted = 1;
                    $result = $result && $datetimechange->save();

                    // Booking declined.
                    $decline = new facetoface_notification($defaults, false);
                    $decline->title = get_string('setting:defaultdeclinesubjectdefault', 'facetoface');
                    $decline->body = text_to_html(get_string('setting:defaultdeclinemessagedefault', 'facetoface'));
                    $decline->conditiontype = MDL_F2F_CONDITION_DECLINE_CONFIRMATION;
                    $result = $result && $decline->save();

                    // Course session trainer cancellation.
                    $sessiontrainercancel = new facetoface_notification($defaults, false);
                    $sessiontrainercancel->title = get_string('setting:defaulttrainersessioncancellationsubjectdefault', 'facetoface');
                    $sessiontrainercancel->body = text_to_html(get_string('setting:defaulttrainersessioncancellationmessagedefault', 'facetoface'));
                    $sessiontrainercancel->conditiontype = MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION;
                    $result = $result && $sessiontrainercancel->save();

                    // Course session trainer unassigned.
                    $sessiontrainerunassign = new facetoface_notification($defaults, false);
                    $sessiontrainerunassign->title = get_string('setting:defaulttrainersessionunassignedsubjectdefault', 'facetoface');
                    $sessiontrainerunassign->body = text_to_html(get_string('setting:defaulttrainersessionunassignedmessagedefault', 'facetoface'));
                    $sessiontrainerunassign->conditiontype = MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT;
                    $result = $result && $sessiontrainerunassign->save();

                    // Course trainer confirmation.
                    $trainerconfirm = new facetoface_notification($defaults, false);
                    $trainerconfirm->title = get_string('setting:defaulttrainerconfirmationsubjectdefault', 'facetoface');
                    $trainerconfirm->body = text_to_html(get_string('setting:defaulttrainerconfirmationmessagedefault', 'facetoface'));
                    $trainerconfirm->conditiontype = MDL_F2F_CONDITION_TRAINER_CONFIRMATION;
                    $result = $result && $trainerconfirm->save();
                }
            }

            // Drop columns from facetoface table
            if ($result) {
                $msg_cols = array(
                    'confirmationsubject',
                    'confirmationinstrmngr',
                    'confirmationmessage',
                    'waitlistedsubject',
                    'waitlistedmessage',
                    'cancellationsubject',
                    'cancellationinstrmngr',
                    'cancellationmessage',
                    'remindersubject',
                    'reminderinstrmngr',
                    'remindermessage',
                    'reminderperiod',
                    'requestsubject',
                    'requestinstrmngr',
                    'requestmessage'
                );

                $table = new xmldb_table('facetoface');
                foreach ($msg_cols as $mc) {
                    $field = new xmldb_field($mc);
                    if ($dbman->field_exists($table, $field)) {
                        $dbman->drop_field($table, $field);
                    }
                }
            }
        }

        // If the templates tables exists but there aren't any templates.
        if ($dbman->table_exists('facetoface_notification_tpl')) {
            $count_templates = $DB->count_records('facetoface_notification_tpl');
            if ($count_templates == 0) {
                // Copy over templates from lang files
                $tpl_confirmation = new stdClass();
                $tpl_confirmation->status = 1;
                $tpl_confirmation->title = get_string('setting:defaultconfirmationsubjectdefault', 'facetoface');
                $tpl_confirmation->body = text_to_html(get_string('setting:defaultconfirmationmessagedefault', 'facetoface'));
                $tpl_confirmation->managerprefix = text_to_html(get_string('setting:defaultconfirmationinstrmngrdefault', 'facetoface'));
                $DB->insert_record('facetoface_notification_tpl', $tpl_confirmation);

                $tpl_cancellation = new stdClass();
                $tpl_cancellation->status = 1;
                $tpl_cancellation->title = get_string('setting:defaultcancellationsubjectdefault', 'facetoface');
                $tpl_cancellation->body = text_to_html(get_string('setting:defaultcancellationmessagedefault', 'facetoface'));
                $tpl_cancellation->managerprefix = text_to_html(get_string('setting:defaultcancellationinstrmngrdefault', 'facetoface'));
                $DB->insert_record('facetoface_notification_tpl', $tpl_cancellation);

                $tpl_waitlist = new stdClass();
                $tpl_waitlist->status = 1;
                $tpl_waitlist->title = get_string('setting:defaultwaitlistedsubjectdefault', 'facetoface');
                $tpl_waitlist->body = text_to_html(get_string('setting:defaultwaitlistedmessagedefault', 'facetoface'));
                $DB->insert_record('facetoface_notification_tpl', $tpl_waitlist);

                $tpl_reminder = new stdClass();
                $tpl_reminder->status = 1;
                $tpl_reminder->title = get_string('setting:defaultremindersubjectdefault', 'facetoface');
                $tpl_reminder->body = text_to_html(get_string('setting:defaultremindermessagedefault', 'facetoface'));
                $tpl_reminder->managerprefix = text_to_html(get_string('setting:defaultreminderinstrmngrdefault', 'facetoface'));
                $DB->insert_record('facetoface_notification_tpl', $tpl_reminder);

                $tpl_request = new stdClass();
                $tpl_request->status = 1;
                $tpl_request->title = get_string('setting:defaultrequestsubjectdefault', 'facetoface');
                $tpl_request->body = text_to_html(get_string('setting:defaultrequestmessagedefault', 'facetoface'));
                $tpl_request->managerprefix = text_to_html(get_string('setting:defaultrequestinstrmngrdefault', 'facetoface'));
                $DB->insert_record('facetoface_notification_tpl', $tpl_request);

                $tpl_decline = new stdClass();
                $tpl_decline->status = 1;
                $tpl_decline->title = get_string('setting:defaultdeclinesubjectdefault', 'facetoface');
                $tpl_decline->body = text_to_html(get_string('setting:defaultdeclinemessagedefault', 'facetoface'));
                $tpl_decline->managerprefix = text_to_html(get_string('setting:defaultdeclineinstrmngrdefault', 'facetoface'));
                $DB->insert_record('facetoface_notification_tpl', $tpl_decline);
            }
        }

        upgrade_mod_savepoint(true, 2013090203, 'facetoface');
    }

    if ($oldversion < 2013090204) {
        // Get all sessions with reminders sent that have had
        // the reminder converted to the new style notification
        $table = new xmldb_table('facetoface_signups');
        $field = new xmldb_field('mailedreminder');
        if ($dbman->field_exists($table, $field)) {
            $sessions = $DB->get_records_sql(
                "
                SELECT
                    fs.sessionid,
                    ss.facetoface AS facetofaceid,
                    fn.id AS notificationid
                FROM
                    {facetoface_signups} fs
                INNER JOIN
                    {facetoface_sessions} ss
                 ON fs.sessionid = ss.id
                INNER JOIN
                    {facetoface_notification} fn
                 ON fn.facetofaceid = ss.facetoface
                WHERE
                    fs.mailedreminder = 1
                AND fn.type = ".MDL_F2F_NOTIFICATION_AUTO."
                AND fn.conditiontype = ".MDL_F2F_CONDITION_BEFORE_SESSION."
                AND fn.scheduletime IS NOT NULL
                GROUP BY
                    fs.sessionid,
                    ss.facetoface,
                    fn.id
                "
            );

            // If the notification_sent table exists but is empty.
            if ($dbman->table_exists('facetoface_notification_sent')) {
                $count_notifications = $DB->count_records('facetoface_notification_sent');
                if ($count_notifications == 0) {
                    // Loop through all the sessions.
                    if ($sessions) {
                        // And add entries to sent table
                        foreach ($sessions as $session) {
                            $record = new stdClass();
                            $record->sessionid = $session->sessionid;
                            $record->notificationid = $session->notificationid;
                            $DB->insert_record('facetoface_notification_sent', $record);
                        }
                    }
                }
            }

            // Drop column from signups table
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013090204, 'facetoface');
    }

    if ($oldversion < 2013090205) {

        // Define table facetoface_room to be created
        $table = new xmldb_table('facetoface_room');

        // Set up comment for the facetoface room table.
        $table->setComment('Table for storing pre-defined facetoface room data');

        // Adding fields to table facetoface_room
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('building', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('address', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('capacity', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('custom', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Adding keys to table facetoface_room
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table facetoface_room
        $table->add_index('custom', XMLDB_INDEX_NOTUNIQUE, array('custom'));

        // Launch create table for facetoface_room
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add roomid field to facetoface_sessions table
        $table = new xmldb_table('facetoface_sessions');

        $field = new xmldb_field('roomid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'discountcost');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Populate new sesion room table with the data from the session custom fields
            $rs = $DB->get_recordset('facetoface_sessions', array(), '', 'id, capacity');

            $fieldmappings = array('room' => 'name', 'venue' => 'building', 'location' => 'address');

            foreach ($rs as $session) {
                $sql = "SELECT f.shortname, d.data
                    FROM {facetoface_session_data} d
                    INNER JOIN {facetoface_session_field} f ON d.fieldid = f.id
                    WHERE d.sessionid = ?
                    AND f.shortname IN('room', 'venue', 'location')";
                if ($data = $DB->get_records_sql($sql, array($session->id))) {
                    $todb = new stdClass;
                    $todb->custom = 1;
                    $todb->capacity = $session->capacity;
                    foreach ($data as $d) {
                        $todb->{$fieldmappings[$d->shortname]} = $d->data;
                    }
                    if (!$roomid = $DB->insert_record('facetoface_room', $todb)) {
                        error('Could not populate session room data from custom fields');
                    }
                    $todb = new stdClass;
                    $todb->id = $session->id;
                    $todb->roomid = $roomid;
                    if (!$DB->update_record('facetoface_sessions', $todb)) {
                        error('Could not update session roomid');
                    }
                }
            }

            // Remove location, room and venue custom fields and data
            $DB->delete_records_select('facetoface_session_data',
                "fieldid IN(
                    SELECT id FROM {facetoface_session_field}
                    WHERE shortname IN('room', 'venue', 'location'))");

            $DB->delete_records_select('facetoface_session_field',
                "shortname IN('room', 'venue', 'location')");
        }

        upgrade_mod_savepoint(true, 2013090205, 'facetoface');
    }

    if ($oldversion < 2013090206) {

        // original table name - to long for XMLDB editor
        $table = new xmldb_table('facetoface_notification_history');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'facetoface_notification_hist');
        }

        // create new table instead
        $table = new xmldb_table('facetoface_notification_hist');

        // Set up the comment for the facetoface notification history table.
        $table->setComment('Notifications history (stores ical event information)');

        // Adding fields to table facetoface_notification_hist
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('sessiondateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('ical_uid', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->add_field('ical_method', XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_notification_hist
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('notificationid', XMLDB_KEY_FOREIGN, array('notificationid'), 'facetoface_notification', array('id'));
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'facetoface_sessions', array('id'));
        $table->add_key('sessiondateid', XMLDB_KEY_FOREIGN, array('sessiondateid'), 'facetoface_sessions_dates', array('id'));
        $table->add_index('f2f_hist_userid_idx', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Launch create table for facetoface_notification_hist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, 2013090206, 'facetoface');
    }

    if ($oldversion < 2013092000) {
        // Define field archived to be added to facetoface_signups.
        $table = new xmldb_table('facetoface_signups');
        $field = new xmldb_field('archived', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'notificationtype');

        // Conditionally launch add field archived.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field multiplesessions to be added to facetoface.
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('multiplesessions', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'usercalentry');

        // Conditionally launch add field multiplesessions.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2013092000, 'facetoface');
    }

    if ($oldversion < 2013101500) {
        // Define field "advice" to be dropped.
        $table = new xmldb_table('facetoface_signups_status');
        $field = new xmldb_field('advice');

        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2013101500, 'facetoface');
    }

    if ($oldversion < 2013102100) {

        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('completionstatusrequired', XMLDB_TYPE_CHAR, '255');

        // Conditionally add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2013102100, 'facetoface');
    }

    if ($oldversion < 2013103000) {
        // Adding foreign keys.
        $tables = array(
            'facetoface' => array(
                new xmldb_key('face_cou_fk', XMLDB_KEY_FOREIGN, array('course'), 'course', 'id')),
            'facetoface_session_roles' => array(
                new xmldb_key('facesessrole_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id')),
            'facetoface_sessions' => array(
                new xmldb_key('facesess_roo_fk', XMLDB_KEY_FOREIGN, array('roomid'), 'facetoface_room', 'id'),
                new xmldb_key('facesess_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id')),
            'facetoface_signups' => array(
                new xmldb_key('facesign_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id')),
            'facetoface_signups_status' => array(
                new xmldb_key('facesignstat_cre_fk', XMLDB_KEY_FOREIGN, array('createdby'), 'user', 'id')),
            'facetoface_session_data' => array(
                new xmldb_key('facesessdata_fie_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_session_field', 'id'),
                new xmldb_key('facesessdata_ses_fk', XMLDB_KEY_FOREIGN, array('sessionid'), 'facetoface_sessions', 'id')),
            'facetoface_notice_data' => array(
                new xmldb_key('facenotidata_fie_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_session_field', 'id'),
                new xmldb_key('facenotidata_not_fk', XMLDB_KEY_FOREIGN, array('noticeid'), 'facetoface_notice', 'id')),
            'facetoface_notification' => array(
                new xmldb_key('facenoti_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id')),
            'facetoface_notification_hist' => array(
                new xmldb_key('facenotihist_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id')));


        foreach ($tables as $tablename => $keys) {
            $table = new xmldb_table($tablename);
            foreach ($keys as $key) {
                $dbman->add_key($table, $key);
            }
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2013103000, 'facetoface');
    }

    if ($oldversion < 2013120100) {

        $strmgr = get_string_manager();
        $langs = array_keys($strmgr->get_list_of_translations());
        foreach ($langs as $lang) {

            if ($lang == 'en' || $strmgr->get_string('facetoface', 'facetoface', null, $lang) !== $strmgr->get_string('facetoface', 'facetoface', null, 'en')) {

                $f2flabel = 'Face-to-face';
                $courselabel = $strmgr->get_string('course', 'moodle', null, $lang);

                $body_key = "/{$courselabel}:\s*\[facetofacename\]/";
                $body_replacement = "{$courselabel}:   [coursename]<br />\n{$f2flabel}:   [facetofacename]";

                $title_key = "/{$courselabel}/";
                $title_replacement = "{$f2flabel}";

                $managerprefix_key = "/{$courselabel}:\s*\[facetofacename\]/";
                $managerprefix_replacement = "{$courselabel}:   [coursename]<br />\n{$f2flabel}:   [facetofacename]";

                $records = $DB->get_records('facetoface_notification_tpl', null, '', 'id, title, body, managerprefix');
                foreach($records as $row) {

                    $row->body = preg_replace($body_key, $body_replacement, $row->body);
                    $row->title = preg_replace($title_key, $title_replacement, $row->title);
                    $row->managerprefix = preg_replace($managerprefix_key, $managerprefix_replacement, $row->managerprefix);
                    $result = $DB->update_record('facetoface_notification_tpl', $row);
                }

                $records = $DB->get_records('facetoface_notification', null, '', 'id, title, body, managerprefix');
                foreach($records as $row) {

                    $row->body = preg_replace($body_key, $body_replacement, $row->body);
                    $row->title = preg_replace($title_key, $title_replacement, $row->title);
                    $row->managerprefix = preg_replace($managerprefix_key, $managerprefix_replacement, $row->managerprefix);
                    $result = $DB->update_record('facetoface_notification', $row);
                }
            }
        }
        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2013120100, 'facetoface');
    }

    if ($oldversion < 2014021300) {
        $table = new xmldb_table('facetoface_session_field');
        $field = new xmldb_field('isfilter');

        if ($dbman->field_exists($table, $field)) {
            // Get custom fields marked as filters.
            $selectedfilters = $DB->get_fieldset_select('facetoface_session_field', 'id', 'isfilter = 1');
            // Activate room, building, and address as default filters.
            $selectedfilters = array_merge($selectedfilters, array('room', 'building', 'address'));
            $calendarfilters = count($selectedfilters) ? implode(',', $selectedfilters) : '';
            // Store the selected filters in the DB.
            set_config('facetoface_calendarfilters', $calendarfilters);
            // Remove isfilter field (now unnecessary).
            $dbman->drop_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014021300, 'facetoface');
    }

    // Add extra 'manager reservations' settings.
    if ($oldversion < 2014022000) {

        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('managerreserve', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'completionstatusrequired');
        $field->setComment('Can managers make reservations/bookings on behalf of their team');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('maxmanagerreserves', XMLDB_TYPE_INTEGER, '7', null, null, null, '1', 'managerreserve');
        $field->setComment('How many reservations can each manager make');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('reservecanceldays', XMLDB_TYPE_INTEGER, '7', null, null, null, '1', 'maxmanagerreserves');
        $field->setComment('Number days before the session when all unconfirmed reservations are deleted');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('reservedays', XMLDB_TYPE_INTEGER, '7', null, null, null, '2', 'reservecanceldays');
        $field->setComment('Number days before the session when reservations are closed');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Record the ID of managers when they reserve/book spaces on a session.
        // Define field bookedby to be added to facetoface_signups.
        $table = new xmldb_table('facetoface_signups');
        $field = new xmldb_field('bookedby', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'archived');
        $field->setComment('The manager who reserved / booked this space');

        // Conditionally launch add field bookedby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Insert the templates for the new notification types.
        // Cancel reservation.
        $tpl_cancelreservation = new stdClass();
        $tpl_cancelreservation->status = 1;
        $tpl_cancelreservation->ccmanager = 0;
        $tpl_cancelreservation->title = get_string('setting:defaultcancelreservationsubjectdefault', 'facetoface');
        $tpl_cancelreservation->body = text_to_html(get_string('setting:defaultcancelreservationmessagedefault', 'facetoface'));
        $DB->insert_record('facetoface_notification_tpl', $tpl_cancelreservation);

        // Cancel all reservations.
        $tpl_cancelallreservations = new stdClass();
        $tpl_cancelallreservations->status = 1;
        $tpl_cancelallreservations->ccmanager = 0;
        $tpl_cancelallreservations->title = get_string('setting:defaultcancelallreservationssubjectdefault', 'facetoface');
        $tpl_cancelallreservations->body = text_to_html(get_string('setting:defaultcancelallreservationsmessagedefault', 'facetoface'));
        $DB->insert_record('facetoface_notification_tpl', $tpl_cancelallreservations);

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014022000, 'facetoface');
    }

    if ($oldversion < 2014041500) {
        // Fix incorrect timezone information for Indianapolis.
        $sql = "UPDATE {facetoface_sessions_dates} SET sessiontimezone = ? WHERE sessiontimezone = ?";
        $DB->execute($sql, array('America/Indiana/Indianapolis', 'America/Indianapolis'));
        upgrade_mod_savepoint(true, 2014041500, 'facetoface');
    }

    if ($oldversion < 2014061600) {

        // Create the a userid field for the facetoface_notification_sent table.
        $table = new xmldb_table('facetoface_notification_sent');
        $field = new xmldb_field('userid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, null);

        // Only run the upgrade if the userid field doesn't exist.
        if (!$dbman->field_exists($table, $field)) {
            // Set time to unlimited as this could take a while.
            core_php_time_limit::raise(0);

            // Wrap this in a transaction so we can't possibly wipe old records without adding the new.
            $transaction = $DB->start_delegated_transaction();

            // Get all facetoface notification sent records to be updated.
            $sql = "SELECT fns.*, fn.facetofaceid, fn.type, fn.conditiontype,
                        fn.booked, fn.waitlisted, fn.cancelled, fn.status, fn.issent
                    FROM {facetoface_notification_sent} fns
                    JOIN {facetoface_notification} fn
                    ON fns.notificationid = fn.id
                    WHERE fn.issent != 0
                    ORDER BY fn.facetofaceid, fns.sessionid";
            $notifications = $DB->get_records_sql($sql);
            $notificationssent = array();

            $total = count($notifications);
            if ($total > 0) {
                $index = 0;
                $pbar = new progress_bar('notificationsentupgrade', 500, true);
            }

            // Clear the old records out of the table before putting the new records in.
            $DB->delete_records('facetoface_notification_sent');

            // Add the userid field and foreign key to the facetoface_notification_sent table.
            $dbman->add_field($table, $field);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

            // Create new sent records for ALL previous records to prevent resending spam.
            foreach ($notifications as $notification) {
                $index++;
                $recipients = array();
                $status = array();

                // Attempt to match the correct set of recipients.
                switch ($notification->type) {
                    case MDL_F2F_NOTIFICATION_MANUAL :
                    case MDL_F2F_NOTIFICATION_SCHEDULED :
                        // Manual and scheduled notifications are user made and should have one of these set.
                        if (!empty($notification->booked)) {
                            // Need to check which type of booked recipients.
                            if ($notification->booked == MDL_F2F_RECIPIENTS_ALLBOOKED) {
                                $status[] = MDL_F2F_STATUS_BOOKED;
                            } else if ($notification->booked == MDL_F2F_RECIPIENTS_ATTENDED) {
                                // Exclude partially attended, see _get_recipients() in the facetoface notification class.
                                $status[] = MDL_F2F_STATUS_FULLY_ATTENDED;
                            } else if ($notification->booked == MDL_F2F_RECIPIENTS_NOSHOWS) {
                                $status[] = MDL_F2F_STATUS_NO_SHOW;
                            }
                        }

                        if (!empty($notification->waitlisted)) {
                            $status[] = MDL_F2F_STATUS_WAITLISTED;
                        }

                        if (!empty($notification->cancelled)) {
                            $status[] = MDL_F2F_STATUS_USER_CANCELLED;
                        }

                        // Default to all users if we don't have the data, to stop any potential resending.
                        if (empty($status)) {
                            $status[] = MDL_F2F_STATUS_BOOKED;
                            $status[] = MDL_F2F_STATUS_WAITLISTED;
                        }

                        break;
                    case MDL_F2F_NOTIFICATION_AUTO :
                        $trainers = array();
                        $trainers[] = MDL_F2F_CONDITION_TRAINER_CONFIRMATION;
                        $trainers[] = MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION;
                        $trainers[] = MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT;

                        if (in_array($notification->conditiontype, $trainers)) {
                            $params = array('sessionid' => $notification->sessionid);
                            $recipients = $DB->get_fieldset_select('facetoface_session_roles', 'userid', $params);
                        } else if ($notification->conditiontype == MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION) {
                            $status[] = MDL_F2F_STATUS_USER_CANCELLED;
                        } else if ($notification->conditiontype == MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER) {
                            $status[] = MDL_F2F_STATUS_REQUESTED;
                        } else if ($notification->conditiontype == MDL_F2F_CONDITION_BOOKING_CONFIRMATION) {
                            $status[] = MDL_F2F_STATUS_APPROVED;
                        } else if ($notification->conditiontype == MDL_F2F_CONDITION_DECLINE_CONFIRMATION) {
                            $status[] = MDL_F2F_STATUS_DECLINED;
                        } else if ($notification->conditiontype == MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION) {
                            $status[] = MDL_F2F_STATUS_WAITLISTED;
                        } else {
                            $status[] = MDL_F2F_STATUS_WAITLISTED;
                            $status[] = MDL_F2F_STATUS_BOOKED;
                        }
                        break;
                }

                // Don't bother getting any recipients if there aren't any status set.
                if (!empty($status)) {
                    list($statussql, $statusparams) = $DB->get_in_or_equal($status);
                    $sql = "SELECT DISTINCT fs.userid
                            FROM {facetoface_signups} fs
                            JOIN {facetoface_signups_status} fss
                            ON fss.signupid = fs.id
                            WHERE fs.sessionid = ?
                            AND fss.superceded <> 1
                            AND fss.statuscode {$statussql}";
                    $params = array_merge(array($notification->sessionid), $statusparams);
                    $recipients = $DB->get_records_sql($sql, $params);
                }

                foreach ($recipients as $recipient) {
                    // Create records to be added to the facetoface_notification_sent table.
                    $record = new stdClass;
                    $record->notificationid = $notification->notificationid;
                    $record->sessionid = $notification->sessionid;
                    $record->userid = $recipient->userid;
                    $notificationssent[] = $record;
                }
                $pbar->update($index, $total, "Creating new notification sent data - record $index/$total");
            }

            // Split array into chuncks of 500 and bulk insert.
            $todbs = array_chunk($notificationssent, 500);
            foreach ($todbs as $todb) {
                $DB->insert_records_via_batch('facetoface_notification_sent', $todb);
            }

            $transaction->allow_commit();
        }


        upgrade_mod_savepoint(true, 2014061600, 'facetoface');
    }

    if ($oldversion < 2014082200) {

        // Changing the default of field waitlisted on table facetoface_notification to 0.
        $table = new xmldb_table('facetoface_notification');
        $field = new xmldb_field('waitlisted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'booked');
        $dbman->change_field_default($table, $field);

        // Define key userid (foreign) to be dropped form facetoface_notification_sent.
        $table = new xmldb_table('facetoface_notification_sent');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $dbman->drop_key($table, $key); // We cannot check for key existence, just drop and recreate later.

        // Changing the default of field userid on table facetoface_notification_sent to 0.
        $table = new xmldb_table('facetoface_notification_sent');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'sessionid');
        $dbman->change_field_default($table, $field);

        // Define key userid (foreign) to be readded to facetoface_notification_sent.
        $table = new xmldb_table('facetoface_notification_sent');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $dbman->add_key($table, $key);

        // Define key facesess_use_fk (foreign) to be dropped form facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');
        $key = new xmldb_key('facesess_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', array('id'));
        $dbman->drop_key($table, $key); // We cannot check for key existence, just drop and recreate later.

        // Make sure there are no nulls before changing to not null.
        $DB->execute("UPDATE {facetoface_sessions} SET usermodified = 0 WHERE usermodified IS NULL");

        // Changing nullability of field usermodified on table facetoface_sessions to not null.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'timemodified');
        $dbman->change_field_notnull($table, $field);

        // Define key facesess_use_fk (foreign) to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');
        $key = new xmldb_key('facesess_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', array('id'));
        $dbman->add_key($table, $key);

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014082200, 'facetoface');
    }

    if ($oldversion < 2014091700) {
        // Fix the default settings on the standard scheduled notifications.
        $bookedconditions = array(MDL_F2F_CONDITION_BEFORE_SESSION,
                                MDL_F2F_CONDITION_AFTER_SESSION,
                                MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE,
                                );
        list($statussql, $statusparams) = $DB->get_in_or_equal($bookedconditions);
        $params = array_merge(array(1, MDL_F2F_NOTIFICATION_AUTO), $statusparams);
        $sql = "UPDATE {facetoface_notification}
                SET booked = ?
                WHERE type = ?
                AND conditiontype $statussql";
        $DB->execute($sql, $params);

        // Now fix the three standard cancellation messages.
        $cancelconditions = array(MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION,
                                MDL_F2F_CONDITION_RESERVATION_CANCELLED,
                                MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED);
        list($statussql, $statusparams) = $DB->get_in_or_equal($cancelconditions);
        $params = array_merge(array(1, MDL_F2F_NOTIFICATION_AUTO), $statusparams);
        $sql = "UPDATE {facetoface_notification}
                SET cancelled = ?
                WHERE type = ?
                AND conditiontype $statussql";
        $DB->execute($sql, $params);

        // Inform waitlisted learners of session datetime changes.
        $sql = "UPDATE {facetoface_notification}
                SET waitlisted = ?
                WHERE type = ?
                AND conditiontype = ?";
        $DB->execute($sql, array(1, MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE));
        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014091700, 'facetoface');
    }
    
    // shezar 2.6.x upgrade line - bump all version numbers below after merge from t2-release-26 if necessary.
    
    // Add new selfapproval and selfapprovaltandc fields.
    if ($oldversion < 2014092300) {

        // Define field selfapproval to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('selfapproval', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'usermodified');
        $field->setComment('Allow self approval.');

        // Conditionally launch add field selfapproval.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field selfapprovaltandc to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('selfapprovaltandc', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'reservedays');
        $field->setComment('Terms and conditions to display when to users when self approval is enabled');

        // Conditionally launch add field selfapprovaltandc and set to default value.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $defaultvalue = get_string('selfapprovaltandccontents', 'facetoface');
            $DB->execute("UPDATE {facetoface} SET selfapprovaltandc = ?", array($defaultvalue));
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014092300, 'facetoface');
    }

    if ($oldversion < 2014092301) {

        // Define field declareinterest to be added to facetoface.
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('declareinterest', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completionstatusrequired');
        $field->setComment('Allow users to declare interest in the facetoface');

        // Conditionally launch add field declareinterest.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('interestonlyiffull', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'declareinterest');
        $field->setComment('Only allow users to declare interest if all sessions are full');

        // Conditionally launch add field interestonlyiffull.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table facetoface_interest to be created.
        $table = new xmldb_table('facetoface_interest');
        $table->setComment('Users who have declared interest in a facetoface session');

        // Adding fields to table facetoface_interest.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('facetoface', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timedeclared', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reason', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_interest.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table facetoface_interest.
        $table->add_index('facetoface', XMLDB_INDEX_NOTUNIQUE, array('facetoface'));
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch create table for facetoface_interest.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014092301, 'facetoface');
    }

    // Add new 'mincapacity' and 'cutoff' fields.
    if ($oldversion < 2014100900) {

        $table = new xmldb_table('facetoface_sessions');

        $field = new xmldb_field('mincapacity', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'usermodified');
        $field->setComment('The minimum number of people for this session to take place.');

        // Conditionally launch add field mincapacity.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Field defaults to 24 hours (86400 seconds).
        $field = new xmldb_field('cutoff', XMLDB_TYPE_INTEGER, '10', null, null, null, '86400', 'mincapacity');
        $field->setComment('The number of seconds before the session start by which the minimum capacity should be reached');

        // Conditionally launch add field cutoff.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2014100900, 'facetoface');
    }

    if ($oldversion < 2014100901) {
        // Define field allowcancellationsdefault to be added to facetoface.
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('allowcancellationsdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'reservedays');

        // Conditionally launch add field allowcancellationsdefault.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field allowcancellations to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('allowcancellations', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'roomid');

        // Conditionally launch add field allowcancellations.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014100901, 'facetoface');
    }

    if ($oldversion < 2014102100) {

        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('waitlisteveryone', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'allowoverbook');
        $field->setComment('Will everyone be added to the waiting list');

        // Conditionally launch add field waitlisteveryone.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2014102100, 'facetoface');
    }

    if ($oldversion < 2014102200) {

        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('allowsignupnotedefault', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 1);
        $field->setComment("Allow 'User sign-up note' default");

        // Just double check the field doesn't somehow exist.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field selfapproval to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('availablesignupnote', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $field->setComment('User sign-up note');

        // Conditionally launch add field selfapproval.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014102200, 'facetoface');
    }

    /* T-13006: It should not be possible for a signup status record to have a statuscode of
     * MDL_F2F_STATUS_APPROVED unless there has been an error (failed to sign up to full
     *  session due to someone filling the last spot). This upgrade cancels all signups with
     * statuscode MDL_F2F_STATUS_APPROVED and displays the list of affected users during upgrade.
     */
    if ($oldversion < 2014110701) {
        // Find signup status records with statuscode MDL_F2F_STATUS_APPROVED.
        $sql = "SELECT fss.id AS signupstatusid,
                       f.id AS facetofaceid,
                       fs.sessionid,
                       s.datetimeknown,
                       c.id AS courseid,
                       f.name AS facetofacename,
                       u.id AS userid,
                       u.*
                  FROM {facetoface_signups_status} fss
                  JOIN {facetoface_signups} fs ON fs.id = fss.signupid
                  JOIN {facetoface_sessions} s ON s.id = fs.sessionid
                  JOIN {facetoface} f ON f.id = s.facetoface
                  JOIN {user} u ON u.id = fs.userid
                  JOIN {course} c ON c.id = f.course
                 WHERE fss.statuscode = ?
                   AND fss.superceded = 0
                 ORDER BY fss.timecreated DESC";
        $affectedusersignups = $DB->get_records_sql($sql, array(MDL_F2F_STATUS_APPROVED));

        $affected = "";
        $timenow = time();
        foreach ($affectedusersignups as $usersignup) {
            $cm = get_coursemodule_from_instance("facetoface", $usersignup->facetofaceid, $usersignup->courseid);

            // Update the record.
            $DB->set_field('facetoface_signups_status', 'statuscode', MDL_F2F_STATUS_DECLINED,
                    array('id' => $usersignup->signupstatusid));

            // Add a log message.
            upgrade_log(UPGRADE_LOG_NOTICE, 'mod_facetoface', 'Invalid user signup cancelled: userid ' .
                    $usersignup->userid . ', facetofaceid ' . $usersignup->facetofaceid);

            // Calculate strings for upgrade output.
            $userurl = new moodle_url('/user/view.php', array('id' => $usersignup->userid));
            $f2furl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $usersignup->sessionid));
            $userstring = html_writer::link($userurl, format_string(fullname($usersignup)));
            $f2fstring = html_writer::link($f2furl, format_string($usersignup->facetofacename));

            // Add the string to the set of results.
            if ($affected) {
                $affected .= "<br>";
            }
            $affected .= get_string('upgradefixstatusapprovedlimbousersdetail', 'facetoface',
                    array('user' => $userstring, 'f2f' => $f2fstring));
        }

        if ($affected) {
            // Display a message indicating that invalid records were found and fixed.
            $message = get_string('upgradefixstatusapprovedlimbousersdescription', 'facetoface', $affected);
            echo $OUTPUT->notification($message, 'notifynotice');
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2014110701, 'facetoface');
    }

    if ($oldversion < 2014110703) {

        // Add cancellationcutoffdefault to the facetoface table.
        $table = new xmldb_table('facetoface');

        // Field defaults to 24 hours (86400 seconds).
        $field = new xmldb_field('cancellationscutoffdefault', XMLDB_TYPE_INTEGER, '10', null, true, null, '86400', 'allowcancellationsdefault');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add cancellationcutoff to the facetoface_sessions table.
        $table = new xmldb_table('facetoface_sessions');

        // Field defaults to 24 hours (86400 seconds).
        $field = new xmldb_field('cancellationcutoff', XMLDB_TYPE_INTEGER, '10', null, true, null, '86400', 'allowcancellations');
        $field->setComment('The number of seconds before the session start when the user is allowed to cancel');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2014110703, 'facetoface');
    }

    if ($oldversion < 2014110704) {

        // Define tables for facetoface session customfields.
        $table = new xmldb_table('facetoface_session_info_field');

        // Adding fields to table session_info_field.
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
        $table->add_field('showinsummary', XMLDB_TYPE_INTEGER, '1', null, true, null, '1');

        // Adding keys to table session_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for session_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table session_info_data to be created.
        $table = new xmldb_table('facetoface_session_info_data');

        // Adding fields to table session_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofacesessionid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table session_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sessioninfodata_fieldid_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_session_info_field', array('id'));
        $table->add_key('sessioninfodata_sessionid_fk', XMLDB_KEY_FOREIGN, array('facetofacesessionid'), 'facetoface_sessions', array('id'));

        // Conditionally launch create table for session_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('facetoface_session_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('sessioninfodatapara_dataid_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'facetoface_session_info_data', array('id'));
        $table->add_index('sessioninfodatapara_value_ix', null, array('value'));

        // Conditionally launch create table for session_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define tables for signup note customfields.
        $table = new xmldb_table('facetoface_signup_info_field');

        // Adding fields to table signup_info_field.
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

        // Adding keys to table signup_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for signup_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table facetoface_signup_info_data to be created.
        $table = new xmldb_table('facetoface_signup_info_data');

        // Adding fields to table session_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofacesignupid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_signup_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('signupinfodata_fielid_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_signup_info_field', array('id'));
        $table->add_key('signupinfodata_signupid_fk', XMLDB_KEY_FOREIGN, array('facetofacesignupid'), 'facetoface_signups_status', array('id'));

        // Conditionally launch create table for facetoface_signup_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('facetoface_signup_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('signupinfodatapara_dataid_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'facetoface_signup_info_data', array('id'));
        $table->add_index('signupinfodatapara_value_ix', null, array('value'));

        // Conditionally launch create table for facetoface_signup_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define tables for cancellation note customfields.
        $table = new xmldb_table('facetoface_cancellation_info_field');

        // Adding fields to table cancellation_info_field.
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

        // Adding keys to table cancellation_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for cancellation_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table cancellation_info_data to be created.
        $table = new xmldb_table('facetoface_cancellation_info_data');

        // Adding fields to table cancellation_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofacecancellationid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table cancellation_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cancellationinfodata_fieldid_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_cancellation_info_field', array('id'));
        $table->add_key('cancellationinfodata_cancellationid_fk', XMLDB_KEY_FOREIGN, array('facetofacecancellationid'), 'facetoface_signups_status', array('id'));

        // Conditionally launch create table for cancellation_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('facetoface_cancellation_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cancellationinfodatapara_dataid_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'facetoface_cancellation_info_data', array('id'));
        $table->add_index('cancellationinfodatapara_value_ix', null, array('value'));

        // Conditionally launch create table for cancellation_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update facetoface notice data.
        $table = new xmldb_table('facetoface_notice_data');
        $field = new xmldb_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);

        if ($dbman->table_exists($table) && $dbman->field_exists($table, 'data')) {
            $dbman->change_field_type($table, $field);
        }

        upgrade_mod_savepoint(true, 2014110704, 'facetoface');
    }

    if ($oldversion < 2014110705) {

        // Passing data to the new signup customfield table.
        require_once($CFG->dirroot . '/mod/facetoface/db/install.php');

        // Create signup and cancellation notes.
        list($signupfieldid, $cancellationfieldid) = facetoface_create_signup_cancellation_customfield_notes();

        // Pass all signup and cancellation information to the new tables.
        $status = array(MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_WAITLISTED, MDL_F2F_STATUS_BOOKED);
        list($insql, $inparam) = $DB->get_in_or_equal($status);
        $sql = "INSERT INTO {facetoface_signup_info_data}
                    (fieldid, facetofacesignupid, data)
                SELECT  ". $signupfieldid .", id, note
                  FROM {facetoface_signups_status}
                 WHERE statuscode {$insql}
                   AND superceded = 0
                   AND " . $DB->sql_isnotempty('facetoface_signups_status', 'note', true, true);
        $DB->execute($sql, $inparam);

        $sql = "INSERT INTO {facetoface_cancellation_info_data}
                    (fieldid, facetofacecancellationid, data)
                SELECT  ". $cancellationfieldid .", id, note
                  FROM {facetoface_signups_status}
                 WHERE statuscode = :cancelled
                   AND superceded = 0
                   AND " . $DB->sql_isnotempty('facetoface_signups_status', 'note', true, true);
        $DB->execute($sql, array('cancelled' => MDL_F2F_STATUS_USER_CANCELLED));

        upgrade_mod_savepoint(true, 2014110705, 'facetoface');
    }

    if ($oldversion < 2014110706) {

        // Passing the old facetoface session customfield data to the new tables.
        // Migrate text types.
        $sortorder = $DB->get_field('facetoface_session_info_field',
            '(CASE WHEN MAX(sortorder) IS NULL THEN 0 ELSE MAX(sortorder) END) + 1', array());
        $sql = "INSERT INTO {facetoface_session_info_field}
                       (shortname, datatype, description, sortorder,hidden, locked, required, forceunique, defaultdata, fullname)
                SELECT shortname, 'text', '', " . $sortorder . ", 0, 0, required, 0, defaultvalue, name
                  FROM {facetoface_session_field}
                 WHERE type = :texttype";
        $DB->execute($sql, array('texttype' => CUSTOMFIELD_TYPE_TEXT));

        upgrade_mod_savepoint(true, 2014110706, 'facetoface');
    }

    if ($oldversion < 2014110707) {

        // Migrate menu and multiselect types.
        list($insql, $inparam) = $DB->get_in_or_equal(array(CUSTOMFIELD_TYPE_SELECT, CUSTOMFIELD_TYPE_MULTISELECT));
        $sql = "SELECT *
                  FROM {facetoface_session_field}
                 WHERE type {$insql}";
        $rs = $DB->get_recordset_sql($sql, $inparam);

        $todb = array();
        $sortorder = $DB->get_field('facetoface_session_info_field',
            '(CASE WHEN MAX(sortorder) IS NULL THEN 0 ELSE MAX(sortorder) END) + 1', array());
        foreach ($rs as $item) {
            upgrade_set_timeout();
            $infofield = new stdClass();
            $infofield->shortname = $item->shortname;
            $infofield->fullname = $item->name;
            $infofield->sortorder = $sortorder++;
            $infofield->required = $item->required;
            $infofield->hidden = 0;
            $infofield->locked = 0;
            $infofield->forceunique = 0;
            $infofield->showinsummary = $item->showinsummary;
            if ($item->type == CUSTOMFIELD_TYPE_SELECT) {
                $infofield->datatype = 'menu';
                $infofield->defaultdata = $item->defaultvalue;
                $infofield->param1 = implode("\n", explode(CUSTOMFIELD_DELIMITER, $item->possiblevalues));
            }
            else {
                $infofield->datatype = 'multiselect';
                $infofield->defaultdata = null;
                $values = explode(CUSTOMFIELD_DELIMITER, $item->possiblevalues);
                $defaulvalue = $item->defaultvalue;
                $options = array();
                foreach ($values as $value) {
                    $default = ($value == $defaulvalue) ? "1" : "0";
                    $options[] = array('option' => $value, 'icon' => '', 'default' => $default, 'delete' => 0);
                }
                $infofield->param1 = json_encode($options);
            }
            $todb[] = $infofield;
        }
        $rs->close();

        if (!empty($todb)) {
            // This table is new and should not contain any data with type different than text.
            $invalidrecords = "DELETE FROM {facetoface_session_info_field} WHERE datatype !=:texttype";
            $DB->execute($invalidrecords, array('texttype' => 'text'));
            $DB->insert_records_via_batch('facetoface_session_info_field', $todb);
            unset($todb);
        }

        upgrade_mod_savepoint(true, 2014110707, 'facetoface');
    }

    if ($oldversion < 2014110708) {

        // Insert all info data.
        $sql = "INSERT INTO {facetoface_session_info_data}
                       (data, fieldid, facetofacesessionid)
                SELECT fsd.data, fsif.id as fieldid, fsd.sessionid as facetofacesessionid
                  FROM {facetoface_session_data} fsd
            INNER JOIN {facetoface_session_field} fsf
                    ON fsd.fieldid = fsf.id
            INNER JOIN {facetoface_session_info_field} fsif
                    ON fsif.shortname = fsf.shortname";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2014110708, 'facetoface');
    }

    if ($oldversion < 2014110709) {

        // Correct the format multiselect and add new data to facetoface_session_info_data_param.
        $sql = "SELECT fsid.id, fsid.data
                  FROM {facetoface_session_info_data} fsid
            INNER JOIN {facetoface_session_info_field} fsif
                    ON fsid.fieldid = fsif.id
                 WHERE datatype =:multiselecttype";
        $rs = $DB->get_recordset_sql($sql, array('multiselecttype' => 'multiselect'));

        $todb = array();
        foreach ($rs as $item) {
            upgrade_set_timeout();
            $dataformated = array();
            $options = explode(CUSTOMFIELD_DELIMITER, $item->data);
            foreach ($options as $option) {
                $dataformated[md5($option)] = array('option' => $option, 'icon' => '', 'default' => "1", 'delete' => 0);
                $paramdata = new stdClass();
                $paramdata->dataid = $item->id;
                $paramdata->value = md5($option);
                $todb[] = $paramdata;
            }
            $DB->set_field('facetoface_session_info_data', 'data', json_encode($dataformated), array('id' => $item->id));
        }
        $rs->close();

        if (!empty($todb)) {
            // This table is new and shouldn't have data, delete data if found before inserting.
            $DB->delete_records('facetoface_session_info_data_param');
            $DB->insert_records_via_batch('facetoface_session_info_data_param', $todb);
            unset($todb);
        }

        upgrade_mod_savepoint(true, 2014110709, 'facetoface');
    }

    if ($oldversion < 2014110710) {

        // Data should be passed now. We can delete the tables.
        $table = new xmldb_table('facetoface_session_field');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('facetoface_session_data');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2014110710, 'facetoface');
    }

    if ($oldversion < 2014110711) {

        // Transform multiselect data in facetoface_notice_data.
        $sql = 'SELECT fnd.id, fnd.data
                  FROM {facetoface_notice_data} fnd
            INNER JOIN {facetoface_session_info_field} sif
                    ON fnd.fieldid = sif.id
                 WHERE sif.datatype = :datatype';
        $sitenoticedata = $DB->get_records_sql($sql, array('datatype' => 'multiselect'));
        foreach ($sitenoticedata as $noticedata) {
            $values = explode(CUSTOMFIELD_DELIMITER, $noticedata->data);
            $options = array();
            foreach ($values as $value) {
                $default = "1";
                $options[] = array('option' => $value, 'icon' => '', 'default' => $default, 'delete' => 0);
            }
            $noticedata->data = json_encode($options);
            $DB->update_record('facetoface_notice_data', $noticedata);
        }

        upgrade_mod_savepoint(true, 2014110711, 'facetoface');
    }

    if ($oldversion < 2015061700) {

        // Reset the room for all f2f sessions linked to a deleted room.
        $sql = "UPDATE {facetoface_sessions}
                   SET roomid = 0
                 WHERE roomid NOT IN (SELECT id
                                        FROM {facetoface_room}
                                     )";
        $DB->execute($sql);

        // Duplicate custom rooms for duplicate sessions.
        $sql = "SELECT fs1.*
                  FROM {facetoface_sessions} fs1
                  JOIN {facetoface_room} fr
                    ON fs1.roomid = fr.id
                 WHERE fr.custom = 1
                   AND EXISTS (SELECT id
                                  FROM {facetoface_sessions} fs2
                                 WHERE fs2.id <> fs1.id
                                   AND fs2.roomid = fs1.roomid
                               )";
        $sessions = $DB->get_records_sql($sql);

        foreach ($sessions as $session) {
            // Duplicate the room.
            $room = $DB->get_record('facetoface_room', array('id' => $session->roomid));
            unset($room->id);
            $newroomid = $DB->insert_record('facetoface_room', $room);

            // Update the session.
            $session->roomid = $newroomid;
            $DB->update_record('facetoface_sessions', $session);
        }

        // Clear any orphaned custom rooms.
        $sql = "DELETE FROM {facetoface_room}
                 WHERE custom = 1
                   AND id NOT IN (SELECT DISTINCT roomid
                                    FROM {facetoface_sessions}
                                 )";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2015061700, 'facetoface');
    }

    if ($oldversion < 2015091000) {
        // We need to validate the content of these language strings to make sure that they are not too long for the database field
        // they are about to be written to.
        $titles = array(
            'setting:defaultconfirmationsubjectdefault' => get_string('setting:defaultconfirmationsubjectdefault', 'facetoface'),
            'setting:defaultwaitlistedsubjectdefault' => get_string('setting:defaultwaitlistedsubjectdefault', 'facetoface'),
            'setting:defaultcancellationsubjectdefault' => get_string('setting:defaultcancellationsubjectdefault', 'facetoface'),
            'setting:defaultdeclinesubjectdefault' => get_string('setting:defaultdeclinesubjectdefault', 'facetoface'),
            'setting:defaultremindersubjectdefault' => get_string('setting:defaultremindersubjectdefault', 'facetoface'),
            'setting:defaultrequestsubjectdefault' => get_string('setting:defaultrequestsubjectdefault', 'facetoface'),
            'setting:defaultdatetimechangesubjectdefault' => get_string('setting:defaultdatetimechangesubjectdefault', 'facetoface'),
            'setting:defaulttrainerconfirmationsubjectdefault' => get_string('setting:defaulttrainerconfirmationsubjectdefault', 'facetoface'),
            'setting:defaulttrainersessioncancellationsubjectdefault' => get_string('setting:defaulttrainersessioncancellationsubjectdefault', 'facetoface'),
            'setting:defaulttrainersessionunassignedsubjectdefault' => get_string('setting:defaulttrainersessionunassignedsubjectdefault', 'facetoface'),
            'setting:defaultcancelreservationsubjectdefault' => get_string('setting:defaultcancelreservationsubjectdefault', 'facetoface'),
            'setting:defaultcancelallreservationssubjectdefault' => get_string('setting:defaultcancelallreservationssubjectdefault', 'facetoface')
        );
        foreach ($titles as $key => $title) {
            if (core_text::strlen($title) > 255) {
                // We choose to truncate here. If we throw an exception like we should then the user won't be able to add face to face
                // sessions and the user may not be able to edit the language pack to fix it. Thus we truncate and debug.
                $titles[$key] = core_text::substr($title, 0, 255);
                debugging('A face to face notification title was truncated due to its length: ' . $key, DEBUG_NORMAL);
            }
        }

        // Define field reference to be added to facetoface_notification_tpl.
        $table = new xmldb_table('facetoface_notification_tpl');
        $field = new xmldb_field('reference', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'status');

        // Conditionally launch add field reference.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $currenttemplates = $DB->get_records('facetoface_notification_tpl');

            $defaulttemplates = array();

            $tpl_confirmation = new stdClass();
            $tpl_confirmation->reference = 'confirmation';
            $tpl_confirmation->title = $titles['setting:defaultconfirmationsubjectdefault'];
            $tpl_confirmation->body = text_to_html(get_string('setting:defaultconfirmationmessagedefault', 'facetoface'));
            $tpl_confirmation->managerprefix = text_to_html(get_string('setting:defaultconfirmationinstrmngrdefault', 'facetoface'));
            $defaulttemplates[] = $tpl_confirmation;

            $tpl_cancellation = new stdClass();
            $tpl_cancellation->reference = 'cancellation';
            $tpl_cancellation->title = $titles['setting:defaultcancellationsubjectdefault'];
            $tpl_cancellation->body = text_to_html(get_string('setting:defaultcancellationmessagedefault', 'facetoface'));
            $tpl_cancellation->managerprefix = text_to_html(get_string('setting:defaultcancellationinstrmngrdefault', 'facetoface'));
            $defaulttemplates[] = $tpl_cancellation;

            $tpl_waitlist = new stdClass();
            $tpl_waitlist->reference = 'waitlist';
            $tpl_waitlist->title = $titles['setting:defaultwaitlistedsubjectdefault'];
            $tpl_waitlist->body = text_to_html(get_string('setting:defaultwaitlistedmessagedefault', 'facetoface'));
            $defaulttemplates[] = $tpl_waitlist;

            $tpl_reminder = new stdClass();
            $tpl_reminder->reference = 'reminder';
            $tpl_reminder->title = $titles['setting:defaultremindersubjectdefault'];
            $tpl_reminder->body = text_to_html(get_string('setting:defaultremindermessagedefault', 'facetoface'));
            $tpl_reminder->managerprefix = text_to_html(get_string('setting:defaultreminderinstrmngrdefault', 'facetoface'));
            $defaulttemplates[] = $tpl_reminder;

            $tpl_request = new stdClass();
            $tpl_request->reference = 'request';
            $tpl_request->title = $titles['setting:defaultrequestsubjectdefault'];
            $tpl_request->body = text_to_html(get_string('setting:defaultrequestmessagedefault', 'facetoface'));
            $tpl_request->managerprefix = text_to_html(get_string('setting:defaultrequestinstrmngrdefault', 'facetoface'));
            $defaulttemplates[] = $tpl_request;

            $tpl_decline = new stdClass();
            $tpl_decline->reference = 'decline';
            $tpl_decline->title = $titles['setting:defaultdeclinesubjectdefault'];
            $tpl_decline->body = text_to_html(get_string('setting:defaultdeclinemessagedefault', 'facetoface'));
            $tpl_decline->managerprefix = text_to_html(get_string('setting:defaultdeclineinstrmngrdefault', 'facetoface'));
            $defaulttemplates[] = $tpl_decline;

            $tpl_timechanged = new stdClass();
            $tpl_timechanged->reference = 'timechange';
            $tpl_timechanged->title = $titles['setting:defaultdatetimechangesubjectdefault'];
            $tpl_timechanged->body = text_to_html(get_string('setting:defaultdatetimechangemessagedefault', 'facetoface'));
            $defaulttemplates[] = $tpl_timechanged;

            $tpl_trainercancel = new stdClass();
            $tpl_trainercancel->reference = 'trainercancel';
            $tpl_trainercancel->title = $titles['setting:defaulttrainersessioncancellationsubjectdefault'];
            $tpl_trainercancel->body = text_to_html(get_string('setting:defaulttrainersessioncancellationmessagedefault', 'facetoface'));
            $defaulttemplates[] = $tpl_trainercancel;

            $tpl_trainerunassign = new stdClass();
            $tpl_trainerunassign->reference = 'trainerunassign';
            $tpl_trainerunassign->title = $titles['setting:defaulttrainersessionunassignedsubjectdefault'];
            $tpl_trainerunassign->body = text_to_html(get_string('setting:defaulttrainersessionunassignedmessagedefault', 'facetoface'));
            $defaulttemplates[] = $tpl_trainerunassign;

            $tpl_trainerconfirm = new stdClass();
            $tpl_trainerconfirm->reference = 'trainerconfirm';
            $tpl_trainerconfirm->title = $titles['setting:defaulttrainerconfirmationsubjectdefault'];
            $tpl_trainerconfirm->body = text_to_html(get_string('setting:defaulttrainerconfirmationmessagedefault', 'facetoface'));
            $defaulttemplates[] = $tpl_trainerconfirm;

            $tpl_allreservationcancel = new stdClass();
            $tpl_allreservationcancel->reference = 'allreservationcancel';
            $tpl_allreservationcancel->title = $titles['setting:defaultcancelallreservationssubjectdefault'];
            $tpl_allreservationcancel->body = text_to_html(get_string('setting:defaultcancelallreservationsmessagedefault', 'facetoface'));
            $defaulttemplates[] = $tpl_allreservationcancel;

            $tpl_reservationcancelation = new stdClass();
            $tpl_reservationcancelation->reference = 'reservationcancel';
            $tpl_reservationcancelation->title = $titles['setting:defaultcancelreservationsubjectdefault'];
            $tpl_reservationcancelation->body = text_to_html(get_string('setting:defaultcancelreservationmessagedefault', 'facetoface'));
            $defaulttemplates[] = $tpl_reservationcancelation;

            foreach ($defaulttemplates as $default) {
                foreach ($currenttemplates as $template) {
                    if ($template->title === $default->title) {
                        $update = new stdClass();
                        $update->id = $template->id;
                        $update->reference = $default->reference;

                        // Add reference to template.
                        $DB->update_record('facetoface_notification_tpl', $update);

                        continue 2;
                    }
                }

                // If we haven't found a record for this template then we will need to create it.
                $todb = clone $default;
                $todb->status = 1;

                $DB->insert_record('facetoface_notification_tpl', $todb);
            }
        }

        // Define field id to be added to facetoface_notification.
        $table = new xmldb_table('facetoface_notification');
        $field = new xmldb_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'facetofaceid');

        // Conditionally launch add field templateid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Get all notifications (there could be lots of these).
            $notifications = $DB->get_records('facetoface_notification', array('templateid' => 0));

            // Now get all the existing F2F notifications that match the updated templates and assign the appropriate templateid.
            $currenttemplates = $DB->get_records('facetoface_notification_tpl');
            foreach ($currenttemplates as $template) {
                $updates = array();

                foreach ($notifications as $notification) {
                    $notificationbody = $notification->body;
                    $notificationbody = strip_tags($notificationbody);
                    $notificationbody = preg_replace("/\s*/", '', $notificationbody);

                    $templatebody = $template->body;
                    $templatebody = strip_tags($templatebody);
                    $templatebody = preg_replace("/\s*/", '', $templatebody);

                    $notificationtitle = $notification->title;
                    $notificationtitle = strip_tags($notificationtitle);
                    $notificationtitle = preg_replace("/\s*/", '', $notificationtitle);

                    $templatetitle = $template->title;
                    $templatetitle = strip_tags($templatetitle);
                    $templatetitle = preg_replace("/\s*/", '', $templatetitle);

                    $hasmgrprefix = (empty($default->managerprefix)) ? false : true;
                    if ($hasmgrprefix) {
                        $notificationmanagerprefix = $notification->managerprefix;
                        $notificationmanagerprefix = strip_tags($notificationmanagerprefix);
                        $notificationmanagerprefix = preg_replace("/\s*/", '', $notificationmanagerprefix);

                        $templatemanagerprefix = $template->managerprefix;
                        $templatemanagerprefix = strip_tags($templatemanagerprefix);
                        $templatemanagerprefix = preg_replace("/\s*/", '', $templatemanagerprefix);

                        $managerprefixmatch = $notificationmanagerprefix == $templatemanagerprefix ? true : false;
                    }

                    $bodymatch = $notificationbody == $templatebody ? true : false;
                    $titlematch = $notificationtitle == $templatetitle ? true : false;

                    if ($titlematch && $bodymatch) {
                        if (!$hasmgrprefix || ($hasmgrprefix && $managerprefixmatch)) {
                            $updates[] = $notification->id;

                            unset($notifications[$notification->id]);
                        }
                    }

                    // There could be a lot of results so do it in batches to avoid IN calls
                    // with too many parameters.
                    if (count($updates) >= 1000) {
                        list($insql, $inparams) = $DB->get_in_or_equal($updates);

                        // Do update query.
                        $sql = "UPDATE {facetoface_notification} SET templateid = ? WHERE id {$insql}";
                        $params = array($template->id);
                        $params = array_merge($params, $inparams);

                        $DB->execute($sql, $params);

                        // Reset updates array.
                        $updates = array();
                    }
                }

                // Update all remaining records.
                if (count($updates) > 0) {
                    list($insql, $inparams) = $DB->get_in_or_equal($updates);

                    // Do update query.
                    $sql = "UPDATE {facetoface_notification} SET templateid = ? WHERE id {$insql}";
                    $params = array($template->id);
                    $params = array_merge($params, $inparams);

                    $DB->execute($sql, $params);
                }
            }
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2015091000, 'facetoface');
    }

    if ($oldversion < 2015100201) {
        // Update reminder auto notifications with default value if they were updated.
        $params = array(1, MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_CONDITION_BEFORE_SESSION);
        $sql = "UPDATE {facetoface_notification}
                SET booked = ?
                WHERE type = ? AND conditiontype = ?";
        $DB->execute($sql, $params);

        // Update session_change auto notifications with default values if they were updated.
        $params = array(1, 1, MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE);
        $sql = "UPDATE {facetoface_notification}
                SET booked = ?, waitlisted = ?
                WHERE type = ?
                AND conditiontype = ?";
        $DB->execute($sql, $params);

        // Update cancellation auto notifications with default value if they were updated.
        $params = array(1, MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION);
        $sql = "UPDATE {facetoface_notification}
                SET cancelled = ?
                WHERE type = ? AND conditiontype = ?";
        $DB->execute($sql, $params);

        // Update defaultcancelreservationsubjectdefault auto notifications with default value if they were updated.
        $params = array(1, MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_CONDITION_RESERVATION_CANCELLED);
        $sql = "UPDATE {facetoface_notification}
                SET cancelled = ?
                WHERE type = ? AND conditiontype = ?";
        $DB->execute($sql, $params);

        // Update defaultcancelallreservationssubjectdefault auto notifications with default value if they were updated.
        $params = array(1, MDL_F2F_NOTIFICATION_AUTO, MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED);
        $sql = "UPDATE {facetoface_notification}
                SET cancelled = ?
                WHERE type = ? AND conditiontype = ?";
        $DB->execute($sql, $params);

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2015100201, 'facetoface');
    }

    if ($oldversion < 2016022300) {
        // Define field sendcapacityemail to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('sendcapacityemail', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'availablesignupnote');

        // Conditionally launch add field sendcapacityemail.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2016022300, 'facetoface');
    }

    if ($oldversion < 2016022301) {
        // Changing nullability of field mincapacity on table facetoface_sessions to null.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('mincapacity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'selfapproval');

        // Ensure that we don't have any records with mincapacity=null by setting all of those to 0.
        $DB->execute('UPDATE {facetoface_sessions} SET mincapacity = 0 WHERE mincapacity IS NULL');

        // Launch change of nullability for field mincapacity.
        $dbman->change_field_notnull($table, $field);

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016022301, 'facetoface');
    }

    if ($oldversion < 2016022400) {

        // Define field registrationtimestart to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');

        $field = new xmldb_field('registrationtimestart', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'sendcapacityemail');
        // Conditionally launch add field registrationtimestart.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('registrationtimefinish', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'registrationtimestart');
        // Conditionally launch add field registrationtimefinish.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Find all existing rooms.
        $rooms = $DB->get_records('facetoface_room');

        // Define tables for room customfields.
        $table = new xmldb_table('facetoface_room_info_field');

        // Adding fields to table room_info_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
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

        // Adding keys to table room_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for room_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table facetoface_room_info_data to be created.
        $table = new xmldb_table('facetoface_room_info_data');

        // Adding fields to table session_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofaceroomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_room_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('roominfodata_fielid_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_room_info_field', array('id'));
        $table->add_key('roominfodata_roomid_fk', XMLDB_KEY_FOREIGN, array('facetofaceroomid'), 'facetoface_room', array('id'));

        // Conditionally launch create table for facetoface_room_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('facetoface_room_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('roominfodatapara_dataid_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'facetoface_room_info_data', array('id'));
        $table->add_index('roominfodatapara_value_ix', null, array('value'));

        // Conditionally launch create table for facetoface_room_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $buildingrecord = $DB->get_record('facetoface_room_info_field', array('datatype' => 'text', 'shortname' => 'building'));
        if (!$buildingrecord) {
            // Create new 'Building' custom field.
            $data = new stdClass();
            $data->datatype = "text";
            $data->shortname = "building";
            $data->description = "";
            $data->sortorder = $DB->get_field(
                'facetoface_room_info_field',
                '(CASE WHEN MAX(sortorder) IS NULL THEN 0 ELSE MAX(sortorder) END) + 1',
                []
            );
            $data->hidden = false;
            $data->locked = false;
            $data->required = false;
            $data->forceunique = false;
            $data->defaultdata = null;
            $data->param1 = null;
            $data->param2 = null;
            $data->param3 = null;
            $data->param4 = null;
            $data->param5 = null;
            $data->fullname = "Building";

            $buildingfieldid = $DB->insert_record('facetoface_room_info_field', $data);
        } else {
            $buildingfieldid = $buildingrecord->id;
        }

        $addressrecord = $DB->get_record('facetoface_room_info_field', array('datatype' => 'location', 'shortname' => 'location'));
        if (!$addressrecord) {
            // Create new 'Location' custom field.
            $data = new stdClass();
            $data->shortname = "location";
            $data->datatype = "location";
            $data->description = "";
            $data->sortorder = $DB->get_field(
                'facetoface_room_info_field',
                '(CASE WHEN MAX(sortorder) IS NULL THEN 0 ELSE MAX(sortorder) END) + 1',
                []
            );
            $data->hidden = false;
            $data->locked = false;
            $data->required = false;
            $data->forceunique = false;
            $data->defaultdata = null;
            $data->param1 = null;
            $data->param2 = null;
            $data->param3 = null;
            $data->param4 = null;
            $data->param5 = null;
            $data->fullname = "Location";

            $addressfieldid = $DB->insert_record('facetoface_room_info_field', $data);
        } else {
            $addressfieldid = $addressrecord->id;
        }

        require_once($CFG->dirroot . '/shezar/customfield/field/location/define.class.php');

        // Migrate the values from the old columns to the new customfields
        foreach ($rooms as $room) {
            if (!empty($room->address)) {
                $data = new stdClass();
                $room->size = 'medium';
                $room->view = 'map';
                $room->display = 'address';
                $data->data = customfield_define_location::prepare_location_data($room);
                $data->fieldid = $addressfieldid;
                $data->facetofaceroomid = $room->id;
                $DB->insert_record('facetoface_room_info_data', $data);
            }

            if (!empty($room->building)) {
                $data = new stdClass();
                $data->data = $room->building;
                $data->fieldid = $buildingfieldid;
                $data->facetofaceroomid = $room->id;
                $DB->insert_record('facetoface_room_info_data', $data);
            }
        }

        // Drop the old columns
        $roomtable = new xmldb_table('facetoface_room');
        $addressfield = new xmldb_field('address');
        $buildingfield = new xmldb_field('building');

        if ($dbman->field_exists($roomtable, $addressfield)) {
            $dbman->drop_field($roomtable, $addressfield);
        }

        if ($dbman->field_exists($roomtable, $buildingfield)) {
            $dbman->drop_field($roomtable, $buildingfield);
        }

        // Create new columns.
        $usercreatedfield = new xmldb_field('usercreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $usermodifiedfield = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $hiddenfield = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($roomtable, $usercreatedfield)) {
            $dbman->add_field($roomtable, $usercreatedfield);
            $dbman->add_key(
                $roomtable,
                new xmldb_key('faceroom_creaid_fk', XMLDB_KEY_FOREIGN, array('usercreated'), 'user', array('id'))
            );
        }

        if (!$dbman->field_exists($roomtable, $usermodifiedfield)) {
            $dbman->add_field($roomtable, $usermodifiedfield);
            $dbman->add_key(
                $roomtable,
                new xmldb_key('faceroom_modiid_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', array('id'))
            );
        }

        if (!$dbman->field_exists($roomtable, $hiddenfield)) {
            $dbman->add_field($roomtable, $hiddenfield);
        }

        // Create roomid in facetoface_sessions_dates.
        $sessionsdatestable = new xmldb_table('facetoface_sessions_dates');
        $roomidfield = new xmldb_field('roomid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        if (!$dbman->field_exists($sessionsdatestable, $roomidfield)) {
            $dbman->add_field($sessionsdatestable, $roomidfield);
            $dbman->add_key(
                $sessionsdatestable,
                new xmldb_key('facesessdate_roomid_fk', XMLDB_KEY_FOREIGN, array('roomid'), 'facetoface_room', array('id'))
            );
        }

        // Drop roomid and datetimeknown from facetoface_sessions.
        $sessionstable = new xmldb_table('facetoface_sessions');
        $roomidfield = new xmldb_field('roomid');
        $datetimeknownfield = new xmldb_field('datetimeknown');
        if ($dbman->field_exists($sessionstable, $roomidfield)) {
            // Move roomid to facetoface_sessions_dates from facetoface_sessions.
            // MySQL has different from pgsql and sqlsrc syntax (update ... set ... from select ... syntax)
            // so doing it in loop.
            $sessions = $DB->get_records('facetoface_sessions', null, '', 'id,roomid');
            foreach ($sessions as $session) {
                $DB->set_field('facetoface_sessions_dates', 'roomid', $session->roomid, array('sessionid' => $session->id));
            }
            $dbman->drop_key(
                    $sessionstable,
                    new xmldb_key('facesess_roo_fk', XMLDB_KEY_FOREIGN, array('roomid'), 'facetoface_room', array('id'))
            );
            $dbman->drop_field($sessionstable, $roomidfield);
        }

        if ($dbman->field_exists($sessionstable, $datetimeknownfield)) {
            $datetimeunknownsessions = $DB->get_records('facetoface_sessions', array('datetimeknown' => '0'), '', 'id');
            foreach($datetimeunknownsessions as $datetimeunknownsession) {
                // Remove any session dates that were part of a session with datetimeknown set to zero.
                $DB->delete_records('facetoface_sessions_dates', array('sessionid' => $datetimeunknownsession->id));
            }
            // Now drop the datetimeknown field.
            $dbman->drop_field($sessionstable, $datetimeknownfield);
        }


        // Define tables for facetoface_asset.
        $table = new xmldb_table('facetoface_asset');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('custom', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        // Adding keys to table facetoface_asset.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('usercreated_fk', XMLDB_KEY_FOREIGN, array('usercreated'), 'user', 'id');
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id');

        // Conditionally launch create table for facetoface_asset.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define tables for asset customfields.
        $table = new xmldb_table('facetoface_asset_info_field');

        // Adding fields to table asset_info_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
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

        // Adding keys to table asset_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for asset_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table facetoface_asset_info_data to be created.
        $table = new xmldb_table('facetoface_asset_info_data');

        // Adding fields to table session_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofaceassetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_asset_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('assetinfodata_fielid_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_asset_info_field', array('id'));
        $table->add_key('assetinfodata_assetid_fk', XMLDB_KEY_FOREIGN, array('facetofaceassetid'), 'facetoface_asset', array('id'));

        // Conditionally launch create table for facetoface_asset_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('facetoface_asset_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('assetinfodatapara_dataid_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'facetoface_asset_info_data', array('id'));
        $table->add_index('assetinfodatapara_value_ix', null, array('value'));

        // Conditionally launch create table for facetoface_asset_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add asset to session dates many-to-many relationship.
        $table = new xmldb_table('facetoface_asset_dates');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('sessionsdateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('assetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('faceassedate_sess_fk', XMLDB_KEY_FOREIGN, array('sessionsdateid'), 'facetoface_sessions_dates', array('id'));
        $table->add_key('faceassedate_asse_fk', XMLDB_KEY_FOREIGN, array('assetid'), 'facetoface_asset', array('id'));

        // Conditionally launch create table for facetoface_asset_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2016022400, 'facetoface');
    }

    /* Approval changes
     *
     * define('APPROVAL_NONE', 0);
     * define('APPROVAL_SELF', 1);
     * define('APPROVAL_ROLE', 2);
     * define('APPROVAL_MANAGER', 4);
     * define('APPROVAL_ADMIN', 8);
     */
    if ($oldversion < 2016022900) {
        $f2f_table = new xmldb_table('facetoface');
        $f2fsign_table = new xmldb_table('facetoface_signups');

        // Create new columns.

        $field = new xmldb_field('approvaltype');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($f2f_table, $field)) {
            $dbman->add_field($f2f_table, $field);
        }

        $field = new xmldb_field('approvalrole');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($f2f_table, $field)) {
            $dbman->add_field($f2f_table, $field);
        }

        $field = new xmldb_field('approvalterms');
        $field->set_attributes(XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($f2f_table, $field)) {
            $dbman->add_field($f2f_table, $field);
        }

        $field = new xmldb_field('approvaladmins');
        $field->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($f2f_table, $field)) {
            $dbman->add_field($f2f_table, $field);
        }

        $field = new xmldb_field('managerid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($f2fsign_table, $field)) {
            $dbman->add_field($f2fsign_table, $field);
        }



        // Migrate settings to the new fields then drop the outdated columns.
        $field = new xmldb_field('approvalreqd', XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($f2f_table, $field)) {

            // Set approvaltype to self approval where every session has self approval enabled.
            $selfappsql = 'UPDATE {facetoface}
                              SET approvaltype = 1
                            WHERE approvalreqd = 1
                              AND id NOT IN (SELECT DISTINCT(facetoface)
                                               FROM {facetoface_sessions}
                                              WHERE selfapproval = 0
                                            )';
            $DB->execute($selfappsql);

            // Then update the sessions that have been successfully ported.
            $sessionsql = 'UPDATE {facetoface_sessions}
                              SET selfapproval = 0
                            WHERE facetoface IN (SELECT id
                                                   FROM {facetoface}
                                                  WHERE approvaltype = 1
                                                )';
            $DB->execute($sessionsql);

            // Set manager approval for all Face-to-face with approval required that haven't been set to self approval.
            $manappsql = 'UPDATE {facetoface} SET approvaltype = 4 WHERE approvalreqd = 1 AND approvaltype != 1';
            $DB->execute($manappsql);

            // Set no approval for the rest of the Face-to-face that didn't have approval required.
            $noappsql = 'UPDATE {facetoface} SET approvaltype = 0 WHERE approvalreqd = 0';
            $DB->execute($noappsql);

            $dbman->drop_field($f2f_table, $field);
        }

        $field = new xmldb_field('selfapprovaltandc', XMLDB_TYPE_TEXT, 'big', null, null, null, null);
        if ($dbman->field_exists($f2f_table, $field)) {
            // Migrate settings to the new fields.
            $termsandcon = 'UPDATE {facetoface} SET approvalterms = selfapprovaltandc';
            $DB->execute($termsandcon); // Copy the terms and conditions to the new field.

            $dbman->drop_field($f2f_table, $field);
        }

        // Now add the new notification templates.
        $newtemplates = array();
        if (!$DB->record_exists('facetoface_notification_tpl', array('reference' => 'rolerequest'))) {
            $tpl_role = new stdClass();
            $tpl_role->reference = 'rolerequest';
            $tpl_role->title = get_string('setting:defaultrolerequestsubjectdefault', 'facetoface');
            $tpl_role->body = text_to_html(get_string('setting:defaultrolerequestmessagedefault', 'facetoface'));
            $tpl_role->managerprefix = text_to_html(get_string('setting:defaultrolerequestinstrmngrdefault', 'facetoface'));
            $tpl_role->status = 1;

            // Return ID so we can use it when creating notifications.
            $tpl_role->id = $DB->insert_record('facetoface_notification_tpl', $tpl_role);

            $newtemplates[] = $tpl_role;
        }

        if (!$DB->record_exists('facetoface_notification_tpl', array('reference' => 'adminrequest'))) {
            $tpl_admin = new stdClass();
            $tpl_admin->reference = 'adminrequest';
            $tpl_admin->title = get_string('setting:defaultadminrequestsubjectdefault', 'facetoface');
            $tpl_admin->body = text_to_html(get_string('setting:defaultadminrequestmessagedefault', 'facetoface'));
            $tpl_admin->managerprefix = text_to_html(get_string('setting:defaultadminrequestinstrmngrdefault', 'facetoface'));
            $tpl_admin->status = 1;

            // Return ID so we can use it when creating notifications.
            $tpl_admin->id = $DB->insert_record('facetoface_notification_tpl', $tpl_admin);

            $newtemplates[] = $tpl_admin;
        }

        if (!empty($newtemplates)) {
            $facetofacerecords = $DB->get_records('facetoface');
            foreach ($newtemplates as $template) {
                $defaults = array();
                $defaults['type'] = MDL_F2F_NOTIFICATION_AUTO;
                $defaults['booked'] = 0;
                $defaults['waitlisted'] = 0;
                $defaults['cancelled'] = 0;
                $defaults['issent'] = 0;
                $defaults['status'] = 1;
                $defaults['ccmanager'] = $template->reference == 'rolerequest' ? 0 : 1;
                $defaults['templateid'] = $template->id;

                $condition = $template->reference == 'rolerequest' ? MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE : MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN;

                // Add a default notification to all existing facetofaces.
                foreach ($facetofacerecords as $facetoface) {
                    $defaults['facetofaceid'] = $facetoface->id;
                    $defaults['courseid'] = $facetoface->course;

                    $notification = new facetoface_notification($defaults, false);
                    $notification->title = $template->title;
                    $notification->body = $template->body;
                    $notification->managerprefix = $template->managerprefix;
                    $notification->conditiontype = $condition;

                    $notification->save();
                }
            }

            // Set the facetoface_approvaloptions setting to the options existing in previous versions
            set_config('facetoface_approvaloptions', 'approval_none,approval_self,approval_manager');
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016022900, 'facetoface');
    }

    if ($oldversion < 2016030100) {

        // Drop Site Notices.
        $table = new xmldb_table('facetoface_notice');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('facetoface_notice_data');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016030100, 'facetoface');
    }

    if ($oldversion < 2016030900) {

        // We only want to add the notification once, as its the last step we'll only
        // add it if we are making any XMLDB changes.
        // As they are only made once if this step is re-run no structure changes will be made
        // and we'll skip adding the notification.
        $adjustingstructure = false;

        // Define field sendcapacityemail to be added to facetoface_sessions.
        $table = new xmldb_table('facetoface_sessions');

        // Define field cancelledstatus to be added to facetoface_sessions.
        $field = new xmldb_field('cancelledstatus', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'registrationtimefinish');
        // Conditionally launch add field cancelledstatus.
        if (!$dbman->field_exists($table, $field)) {
            $adjustingstructure = true;
            $dbman->add_field($table, $field);
        }

        // Define table facetoface_sessioncancel_info_field to be created.
        $table = new xmldb_table('facetoface_sessioncancel_info_field');

        // Adding fields to table facetoface_sessioncancel_info_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
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

        // Adding keys to table facetoface_sessioncancel_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for facetoface_sessioncancel_info_field.
        if (!$dbman->table_exists($table)) {
            $adjustingstructure = true;
            $dbman->create_table($table);
        }

        // Define table facetoface_sessioncancel_info_data to be created.
        $table = new xmldb_table('facetoface_sessioncancel_info_data');

        // Adding fields to table facetoface_sessioncancel_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('facetofacecancellationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_sessioncancel_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cancellationinfodata_fieldid_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'facetoface_sessioncancel_info_field', array('id'));
        $table->add_key('cancellationinfodata_cancellationid_fk', XMLDB_KEY_FOREIGN, array('facetofacecancellationid'), 'facetoface_signups_status', array('id'));

        // Conditionally launch create table for facetoface_sessioncancel_info_data.
        if (!$dbman->table_exists($table)) {
            $adjustingstructure = true;
            $dbman->create_table($table);
        }

        // Define table facetoface_sessioncancel_info_data_param to be created.
        $table = new xmldb_table('facetoface_sessioncancel_info_data_param');

        // Adding fields to table facetoface_sessioncancel_info_data_param.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table facetoface_sessioncancel_info_data_param.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cancellationinfodatapara_dataid_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'facetoface_sessioncancel_info_data', array('id'));

        // Adding indexes to table facetoface_sessioncancel_info_data_param.
        $table->add_index('cancellationinfodatapara_value_ix', XMLDB_INDEX_NOTUNIQUE, array('value'));

        // Conditionally launch create table for facetoface_sessioncancel_info_data_param.
        if (!$dbman->table_exists($table)) {
            $adjustingstructure = true;
            $dbman->create_table($table);
        }

        // Add session cancellation notification template.
        if ($adjustingstructure && !$DB->record_exists('facetoface_notification_tpl', array('reference' => 'sessioncancellation'))) {
            $sessioncancel = new stdClass();
            $sessioncancel->reference = 'sessioncancellation';
            $sessioncancel->status = 1;
            $sessioncancel->title = get_string('setting:defaultsessioncancellationsubjectdefault', 'facetoface');
            $sessioncancel->body = text_to_html(get_string('setting:defaultsessioncancellationmessagedefault', 'facetoface'));
            $sessioncancel->managerprefix = text_to_html(get_string('setting:defaultsessioncancellationinstrmngrcopybelow', 'facetoface'));
            $DB->insert_record('facetoface_notification_tpl', $sessioncancel);
        }

        // Unset the var - no need to keep it around.
        unset($adjustingstructure);

        upgrade_mod_savepoint(true, 2016030900, 'facetoface');
    }

    if ($oldversion < 2016031000) {
        // Add Sign Up period expired notification.

        // We need to ensure that this notification does not already exist.
        // We can check the reference for this.
        $reference = 'registrationexpired';
        if (!$DB->record_exists('facetoface_notification_tpl', array('reference' => $reference))) {

            // Prepare the common strings.
            $title = get_string('setting:defaultregistrationexpiredsubjectdefault', 'facetoface');
            $body = text_to_html(get_string('setting:defaultregistrationexpiredmessagedefault_v9', 'facetoface'));

            // Add the template.
            $tpl_expired = new stdClass();
            $tpl_expired->reference = 'registrationexpired';
            $tpl_expired->status = 1;
            $tpl_expired->title = $title;
            $tpl_expired->body = $body;
            $tpl_expired->managerprefix = text_to_html(get_string('setting:defaultregistrationexpiredinstrmngr', 'facetoface'));
            $templateid = $DB->insert_record('facetoface_notification_tpl', $tpl_expired, true);

            // Add the noticifations to existing facetoface activities.
            $facetofaces = $DB->get_records('facetoface', null, '', 'id,course');
            if ($facetofaces) {
                // Loop over facetofaces.
                foreach ($facetofaces as $facetoface) {

                    // Get each message and create notification.
                    $defaults = array();
                    $defaults['facetofaceid'] = $facetoface->id;
                    $defaults['courseid'] = $facetoface->course;
                    $defaults['type'] = MDL_F2F_NOTIFICATION_AUTO;
                    $defaults['booked'] = 0;
                    $defaults['waitlisted'] = 0;
                    $defaults['cancelled'] = 0;
                    $defaults['issent'] = 0;
                    $defaults['status'] = 1;
                    $defaults['ccmanager'] = 0;
                    $defaults['templateid'] = $templateid;

                    $signupperiodexpired = new facetoface_notification($defaults, false);
                    $signupperiodexpired->title = $title;
                    $signupperiodexpired->body = $body;
                    $signupperiodexpired->conditiontype = MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED;
                    $result = $result && $signupperiodexpired->save();
                }
            }
            // Unset some of the structures we've used, particularly facetofaces as it may be HUGE.
            unset($title, $body, $facetofaces, $facetoface, $defaults, $tpl_expired, $signupperiodexpired);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016031000, 'facetoface');
    }

    if ($oldversion < 2016031100) {

        $stringsexist = true;
        // Strings for placeholders. This would require that any code base where this installation ran, would
        // have these strings in the f2f lang file or we'll be needing to skip these changes.
        $alldates = '[alldates]';
        $f2flangstrings = array();
        $f2flangstrings['loopstart'] = '[#sessions]';
        $f2flangstrings['loopend'] = '[/sessions]';
        $f2flangstrings['sessionstarttime'] = '[session:starttime]';
        $f2flangstrings['sessionstartdate'] = '[session:startdate]';
        $f2flangstrings['sessionfinishtime'] = '[session:finishtime]';
        $f2flangstrings['sessionfinishdate'] = '[session:finishdate]';
        $f2flangstrings['timezone'] = '[session:timezone]';
        $f2flangstrings['duration'] = '[session:duration]';

        // Strings for previous template defaults just prior to 9.0. Uses their reference as keys.
        $oldtemplatedefaults = array();
        $oldtemplatedefaults['confirmation'] = text_to_html(get_string('setting:defaultconfirmationmessagedefault', 'facetoface'));
        $oldtemplatedefaults['cancellation'] = text_to_html(get_string('setting:defaultcancellationmessagedefault', 'facetoface'));
        $oldtemplatedefaults['waitlist'] = [
            text_to_html(get_string('setting:defaultwaitlistedmessagedefault_v27', 'facetoface')),
            text_to_html(get_string('setting:defaultwaitlistedmessagedefault', 'facetoface'))
        ];
        $oldtemplatedefaults['reminder'] = text_to_html(get_string('setting:defaultremindermessagedefault', 'facetoface'));
        $oldtemplatedefaults['request'] = text_to_html(get_string('setting:defaultrequestmessagedefault', 'facetoface'));
        $oldtemplatedefaults['rolerequest'] = text_to_html(get_string('setting:defaultrolerequestmessagedefault', 'facetoface'));
        $oldtemplatedefaults['adminrequest'] = text_to_html(get_string('setting:defaultadminrequestmessagedefault', 'facetoface'));
        $oldtemplatedefaults['decline'] = text_to_html(get_string('setting:defaultdeclinemessagedefault', 'facetoface'));
        $oldtemplatedefaults['timechange'] = text_to_html(get_string('setting:defaultdatetimechangemessagedefault', 'facetoface'));
        $oldtemplatedefaults['trainercancel'] = text_to_html(get_string('setting:defaulttrainersessioncancellationmessagedefault', 'facetoface'));
        $oldtemplatedefaults['trainerunassign'] = text_to_html(get_string('setting:defaulttrainersessionunassignedmessagedefault', 'facetoface'));
        $oldtemplatedefaults['trainerconfirm'] = text_to_html(get_string('setting:defaulttrainerconfirmationmessagedefault', 'facetoface'));
        $oldtemplatedefaults['allreservationcancel'] = text_to_html(get_string('setting:defaultcancelallreservationsmessagedefault', 'facetoface'));
        $oldtemplatedefaults['reservationcancel'] = text_to_html(get_string('setting:defaultcancelreservationmessagedefault', 'facetoface'));
        $oldtemplatedefaults['sessioncancellation'] = text_to_html(get_string('setting:defaultsessioncancellationmessagedefault', 'facetoface'));

        // Strings for new template defaults with new placeholder variables introduced in 9.0. Uses their reference as keys.
        $newtemplatedefaults = array();
        $newtemplatedefaults['confirmation'] = text_to_html(get_string('setting:defaultconfirmationmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['cancellation'] = text_to_html(get_string('setting:defaultcancellationmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['waitlist'] = text_to_html(get_string('setting:defaultwaitlistedmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['reminder'] = text_to_html(get_string('setting:defaultremindermessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['request'] = text_to_html(get_string('setting:defaultrequestmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['rolerequest'] = text_to_html(get_string('setting:defaultrolerequestmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['adminrequest'] = text_to_html(get_string('setting:defaultadminrequestmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['decline'] = text_to_html(get_string('setting:defaultdeclinemessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['timechange'] = text_to_html(get_string('setting:defaultdatetimechangemessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['trainercancel'] = text_to_html(get_string('setting:defaulttrainersessioncancellationmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['trainerunassign'] = text_to_html(get_string('setting:defaulttrainersessionunassignedmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['trainerconfirm'] = text_to_html(get_string('setting:defaulttrainerconfirmationmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['allreservationcancel'] = text_to_html(get_string('setting:defaultcancelallreservationsmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['reservationcancel'] = text_to_html(get_string('setting:defaultcancelreservationmessagedefault_v9', 'facetoface'));
        $newtemplatedefaults['sessioncancellation'] = text_to_html(get_string('setting:defaultsessioncancellationmessagedefault_v9', 'facetoface'));

        if (!is_string($alldates) or (strpos($alldates, '[[') !== false)) {
            $stringsexist = false;
        }

        foreach($f2flangstrings as $f2flangstring) {
            if (!is_string($f2flangstring) or (strpos($f2flangstring, '[[') !== false)) {
                $stringsexist = false;
            }
        }

        // This will hold templates that were found to match the pre-9.0 defaults. With format array(id => reference).
        $templateswithdefaults = array();
        $oldtemplatedefaultbodies = array();

        // Only make actual changes if all the strings exist.
        if ($stringsexist) {
            // Create the string that will replace alldates.
            // This may not translate properly (to rtl for instance). But this mimics the format taken
            // by alldates anyway. If alldates wasn't being used (perhaps for that reason), this won't replace anything.
            $replacement = $f2flangstrings['loopstart'];
            $replacement .= $f2flangstrings['sessionstartdate'].', '.$f2flangstrings['sessionstarttime'];
            $replacement .= ' - ';
            $replacement .= $f2flangstrings['sessionfinishdate'].', '.$f2flangstrings['sessionfinishtime'];
            $replacement .= ' ' . $f2flangstrings['timezone'] . "<br>";
            $replacement .= $f2flangstrings['loopend'];

            // Update notification templates.
            $templates = $DB->get_records('facetoface_notification_tpl');
            foreach ($templates as $template) {
                $savethisrecord = false;
                if (isset($template->title) && (strpos($template->title, $alldates) !== false)) {
                    $template->title = str_replace($alldates, $replacement, $template->title);
                    $savethisrecord = true;
                }
                if (isset($template->body)) {
                    $wasdefault = false;
                    if (isset($oldtemplatedefaults[$template->reference])) {
                        // Make same structure for every template upgrade.
                        if (!is_array($oldtemplatedefaults[$template->reference])) {
                            $oldtemplatedefaults[$template->reference] = [$oldtemplatedefaults[$template->reference]];
                        }

                        foreach ($oldtemplatedefaults[$template->reference] as $oldtemplate) {
                            if (isset($template->reference) && (strcmp($template->body, $oldtemplate) === 0)) {
                                // The template's body is an exact match with the string in the lang file.
                                // We'll update it with the new string in the lang file.
                                $template->body = $newtemplatedefaults[$template->reference];
                                $templateswithdefaults[$template->id] = $template->reference;
                                $oldtemplatedefaultbodies[$template->id] = $oldtemplate;
                                $savethisrecord = true;
                                $wasdefault = true;
                                break;
                            }
                        }
                    }

                    // If not found.
                    if (!$wasdefault && strpos($template->body, $alldates) !== false) {
                        // If the template didn't match the default in the lang file, we'll still
                        // replace the [alldates] placeholders, but can't really replace [session:location]
                        // and similar placeholders as the way these work has changed.
                        $template->body = str_replace($alldates, $replacement, $template->body);
                        $savethisrecord = true;
                    }
                }
                if (isset($template->managerprefix)) {
                    // Special case for manager prefix during upgrade from 2.4.x
                    if (strcmp($template->managerprefix, text_to_html(get_string('setting:defaultrequestinstrmngrdefault_v24', 'facetoface'))) === 0) {
                        $template->managerprefix = text_to_html(get_string('setting:defaultrequestinstrmngrdefault', 'facetoface'));
                        $savethisrecord = true;
                    } else if (strpos($template->managerprefix, $alldates) !== false) {
                        $template->managerprefix = str_replace($alldates, $replacement, $template->managerprefix);
                        $savethisrecord = true;
                    }
                }
                if ($savethisrecord) {
                    $DB->update_record('facetoface_notification_tpl', $template);
                }
            }

            // Update notifications
            $f2f_notifications = $DB->get_records('facetoface_notification');
            foreach ($f2f_notifications as $f2f_notification) {
                $savethisrecord = false;
                if (isset($f2f_notification->title) && (strpos($f2f_notification->title, $alldates) !== false)) {
                    $f2f_notification->title = str_replace($alldates, $replacement, $f2f_notification->title);
                    $savethisrecord = true;
                }
                if (isset($f2f_notification->body)) {
                    if (isset($templateswithdefaults[$f2f_notification->templateid])) {
                        // This notification uses a template that matched the default lang string.
                        $reference = $templateswithdefaults[$f2f_notification->templateid];
                        if (strcmp($f2f_notification->body, $oldtemplatedefaultbodies[$f2f_notification->templateid]) === 0) {
                            // This notification also matched the same default lang string. So we'll update it
                            // to the new default.
                            $f2f_notification->body = $newtemplatedefaults[$reference];
                            $savethisrecord = true;
                        }
                    } else if (strpos($f2f_notification->body, $alldates) !== false) {
                        // If the notification didn't match the default in the lang file, we'll still
                        // replace the [alldates] placeholders, but can't really replace [session:location]
                        // and similar placeholders as the way these work has changed.
                        $f2f_notification->body = str_replace($alldates, $replacement, $f2f_notification->body);
                        $savethisrecord = true;
                    }
                }
                if (isset($f2f_notification->managerprefix)) {
                    // Special case for manager prefix during upgrade from 2.4.x
                    if (strcmp($f2f_notification->managerprefix, text_to_html(get_string('setting:defaultrequestinstrmngrdefault_v24', 'facetoface'))) === 0) {
                        $f2f_notification->managerprefix = text_to_html(get_string('setting:defaultrequestinstrmngrdefault', 'facetoface'));
                        $savethisrecord = true;
                    } else if (strpos($f2f_notification->managerprefix, $alldates) !== false) {
                        $f2f_notification->managerprefix = str_replace($alldates, $replacement, $f2f_notification->managerprefix);
                        $savethisrecord = true;
                    }
                }
                if ($savethisrecord) {
                    $DB->update_record('facetoface_notification', $f2f_notification);
                }

            }
        }
        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016031100, 'facetoface');
    }

    if ($oldversion < 2016031102) {

        // Remove note field.
        $table = new xmldb_table('facetoface_signups_status');
        $field = new xmldb_field('note');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // We only ever want to do this once, do it more than once and you're going to have data loss.
        // Thus we will only perform the migration if a special config variable does not already exist.
        // We do not use get config as this has caching.
        $configparams = array(
            'plugin' => 'facetoface',
            'name' => 'upgrade_customfieldmigration_signup',
        );
        if (!$DB->record_exists('config_plugins', $configparams)) {

            // Migrate the customfield data for signups and cancellations.
            //
            // TL-6962 found that signup and cancellation custom field data was being stored against the
            // signup status id. This meant that every time the signup status changed the data was essentially lost.
            // While it was still in the system it was no longer displayed.
            // After much discussion it was decided that signup and cancellation custom field data should be saved against
            // the signup itself rather than the signup status.
            // This meant that we needed to migrate the signup and cancellation custom field data updating reference table id.
            // Because the new ids may exist in the data records already the only safe way to do this is to create a temp table
            // that stores the mappings against the data ids.
            // This way we can accurately and safely update the reference id keeping the data ids in tack.
            //
            // This essentially only takes the data we want and drops the redundant data from the system as we no longer
            // want to keep it.

            // Statuscode 10 = MDL_F2F_STATUS_USER_CANCELLED.
            mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_signup_info_data', 'facetofacesignupid');

            $configparams['value'] = 'done';
            $DB->insert_record('config_plugins', $configparams);
        }

        // Now that we have done signup data we must also do cancellation data.
        $configparams = array(
            'plugin' => 'facetoface',
            'name' => 'upgrade_customfieldmigration_cancellation',
        );
        if (!$DB->record_exists('config_plugins', $configparams)) {

            mod_facetoface_migrate_session_signup_customdata($DB, $dbman, 'facetoface_cancellation_info_data', 'facetofacecancellationid');

            $configparams['value'] = 'done';
            $DB->insert_record('config_plugins', $configparams);
        }
        unset($configparams);

        upgrade_mod_savepoint(true, 2016031102, 'facetoface');
    }

    if ($oldversion < 2016042700) {
        // Cleanup settings after manageremail removal.
        unset_config('facetoface_addchangemanageremail');
        unset_config('facetoface_manageraddressformat');
        unset_config('facetoface_manageraddressformatreadable');

        upgrade_mod_savepoint(true, 2016042700, 'facetoface');
    }

    if ($oldversion < 2016050200) {
        $DB->set_field('facetoface_signup_info_field', 'fullname', 'Requests for session organiser', array(
            'datatype' => 'text',
            'shortname' => 'signupnote',
            'fullname' => 'Signup note'
        ));
        upgrade_mod_savepoint(true, 2016050200, 'facetoface');
    }

    if ($oldversion < 2016051100) {

        $table = new xmldb_table('facetoface_room');
        $field = new xmldb_field('allowconflicts', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'capacity');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('type');
        if ($dbman->field_exists($table, $field)) {
            $DB->execute("UPDATE {facetoface_room} SET allowconflicts = 1 WHERE type = 'external'");

            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('facetoface_asset');
        $field = new xmldb_field('allowconflicts', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'name');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('type');
        if ($dbman->field_exists($table, $field)) {
            $DB->execute("UPDATE {facetoface_asset} SET allowconflicts = 1 WHERE type = 'external'");

            $dbman->drop_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016051100, 'facetoface');
    }

    if ($oldversion < 2016051800) {
        $table = new xmldb_table('facetoface_notification');
        $field = new xmldb_field('requested');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add the registration closure notification.

        // We need to ensure that this notification does not already exist.
        // We can check the reference for this.
        $reference = 'registrationclosure';
        if (!$DB->record_exists('facetoface_notification_tpl', array('reference' => $reference))) {

            // Prepare the common strings.
            $title = get_string('setting:defaultpendingreqclosuresubjectdefault', 'facetoface');
            $body = text_to_html(get_string('setting:defaultpendingreqclosuremessagedefault_v9', 'facetoface'));
            $mgrprefix = text_to_html(get_string('setting:defaultpendingreqclosureinstrmngrcopybelow', 'facetoface'));

            // Add the template.
            $tpl_regclose = new stdClass();
            $tpl_regclose->reference = 'registrationclosure';
            $tpl_regclose->status = 1;
            $tpl_regclose->title = $title;
            $tpl_regclose->ccmanager = 1;
            $tpl_regclose->requested = 1;
            $tpl_regclose->body = $body;
            $tpl_regclose->managerprefix = $mgrprefix;
            $templateid = $DB->insert_record('facetoface_notification_tpl', $tpl_regclose, true);

            // Add the noticifations to existing facetoface activities.
            $facetofaces = $DB->get_records('facetoface', null, '', 'id,course');
            if ($facetofaces) {
                // Loop over facetofaces.
                foreach ($facetofaces as $facetoface) {

                    // Get each message and create notification.
                    $defaults = array();
                    $defaults['facetofaceid'] = $facetoface->id;
                    $defaults['courseid'] = $facetoface->course;
                    $defaults['type'] = MDL_F2F_NOTIFICATION_AUTO;
                    $defaults['booked'] = 0;
                    $defaults['requested'] = 1;
                    $defaults['waitlisted'] = 0;
                    $defaults['cancelled'] = 0;
                    $defaults['issent'] = 0;
                    $defaults['status'] = 1;
                    $defaults['ccmanager'] = 1;
                    $defaults['templateid'] = $templateid;

                    $registrationclosure = new facetoface_notification($defaults, false);
                    $registrationclosure->title = $title;
                    $registrationclosure->body = $body;
                    $registrationclosure->managerprefix = $mgrprefix;
                    $registrationclosure->conditiontype = MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS;
                    $result = $result && $registrationclosure->save();
                }
            }
            // Unset some of the structures we've used, particularly facetofaces as it may be HUGE.
            unset($title, $body, $mgrprefix, $facetofaces, $facetoface, $defaults, $tpl_regclose, $registrationclosure);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016051800, 'facetoface');
    }

    if ($oldversion < 2016052500) {
        // Savepoint reached.
        mod_facetoface_calendar_search_config_upgrade();
        upgrade_mod_savepoint(true, 2016052500, 'facetoface');
    }

    // Delete all the orphaned signup and cancellation custom fields.
    if ($oldversion < 2016060100) {
        require_once("$CFG->dirroot/mod/facetoface/db/upgradelib.php");

        // Delete all the orphaned signup custom fields.
        mod_facetoface_delete_orphaned_customfield_data('signup');

        // Delete all the orphaned cancellation custom fields.
        mod_facetoface_delete_orphaned_customfield_data('cancellation');

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016060100, 'facetoface');
    }

    if ($oldversion < 2016061400) {
        // Get rid of duplicates before adding unique index.
        $sql = "SELECT MIN(id) AS id, sessionsdateid, assetid, COUNT(*) AS dupcount
                  FROM {facetoface_asset_dates}
              GROUP BY sessionsdateid, assetid
                HAVING COUNT(*) > 1";
        $duplicates = $DB->get_records_sql($sql);
        $sql = "DELETE
                  FROM {facetoface_asset_dates}
                 WHERE id <> :id AND sessionsdateid = :sessionsdateid AND assetid = :assetid";
        foreach ($duplicates as $duplicate) {
            $duplicate = (array)$duplicate;
            unset($duplicate['dupcount']);
            $DB->execute($sql, $duplicate);
        }

        // Define index sessionsdateid-assetid (unique) to be added to facetoface_asset_dates.
        $table = new xmldb_table('facetoface_asset_dates');
        $index = new xmldb_index('sessionsdateid-assetid', XMLDB_INDEX_UNIQUE, array('sessionsdateid', 'assetid'));

        // Conditionally launch add index sessionsdateid-assetid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016061400, 'facetoface');
    }

    // Update room and asset files to use mod_facetoface component name.
    if ($oldversion < 2016061600) {

        $fs = get_file_storage();
        $syscontextid = context_system::instance()->id;

        // Find all existing rooms and update the component name of any saved files to mod_facetoface.
        $rooms = $DB->get_records('facetoface_room');
        foreach ($rooms as $room) {
            $files = $fs->get_area_files($syscontextid, 'facetoface', 'room', $room->id);
            foreach ($files as $orgfile) {

                if ($orgfile->get_filename() === ".") {
                    continue;
                }

                $newfile = array(
                    'component' => 'mod_facetoface',
                    'filearea' => 'room',
                    'itemid' => $room->id
                );
                $fs->create_file_from_storedfile($newfile, $orgfile);
                $orgfile->delete();
            }
        }

        // Find all existing assets and update the component name of any saved files to mod_facetoface.
        $assets = $DB->get_records('facetoface_asset');
        foreach ($assets as $asset) {
            $files = $fs->get_area_files($syscontextid, 'facetoface', 'asset', $asset->id);
            foreach ($files as $orgfile) {

                if ($orgfile->get_filename() === ".") {
                    continue;
                }

                $newfile = array(
                    'component' => 'mod_facetoface',
                    'filearea' => 'asset',
                    'itemid' => $asset->id
                );
                $fs->create_file_from_storedfile($newfile, $orgfile);
                $orgfile->delete();
            }
        }

        upgrade_mod_savepoint(true, 2016061600, 'facetoface');
    }

    if ($oldversion < 2016062400) {
        // Remove allowsignupnotedefault field from facetoface table.
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('allowsignupnotedefault');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Remove availablesignupnote field from facetoface_sessions table.
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('availablesignupnote');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016062400, 'facetoface');
    }

    if ($oldversion < 2016070400) {
        // Uninstall the removed block_facetoface.
        if (!file_exists("$CFG->dirroot/blocks/facetoface")) {
            uninstall_plugin('block', 'facetoface');
        }

        upgrade_mod_savepoint(true, 2016070400, 'facetoface');

    }

    if ($oldversion < 2016070500) {
        // Unlink rooms from cancelled events,
        // orphaned custom rooms get deleted automatically from cleanup task.
        $sql = "SELECT fsd.id
                  FROM {facetoface_sessions_dates} fsd
                  JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
                 WHERE fsd.roomid > 0 AND fs.cancelledstatus = 1";
        $records = $DB->get_recordset_sql($sql);
        foreach ($records as $record) {
            $DB->set_field('facetoface_sessions_dates', 'roomid', 0, array('id' => $record->id));
        }
        $records->close();

        upgrade_mod_savepoint(true, 2016070500, 'facetoface');
    }

    if ($oldversion < 2016071200) {
        // Unlink assets from cancelled events,
        // orphaned custom assets get deleted automatically from cleanup task.
        $sql = "SELECT fad.id
                  FROM {facetoface_asset_dates} fad
                  JOIN {facetoface_sessions_dates} fsd ON (fsd.id = fad.sessionsdateid)
                  JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
                 WHERE fs.cancelledstatus = 1";
        $records = $DB->get_recordset_sql($sql);
        foreach ($records as $record) {
            $DB->delete_records('facetoface_asset_dates', array('id' => $record->id));
        }
        $records->close();

        upgrade_mod_savepoint(true, 2016071200, 'facetoface');
    }

    if ($oldversion < 2016080900) {
        // Drop the old column
        $table = new xmldb_table('facetoface_sessions');
        $field = new xmldb_field('duration');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2016080900, 'facetoface');
    }

    if ($oldversion < 2016080901) {

        // Upgrade the two broken timeconfig settings.
        $sql = 'UPDATE {config_plugins} SET name=:new WHERE name=:old';
        $DB->execute($sql, ['old' => 'facetoface/defaultstarttime_minutes', 'new' => 'defaultstarttime_minutes']);
        $DB->execute($sql, ['old' => 'facetoface/defaultfinishtime_minutes', 'new' => 'defaultfinishtime_minutes']);

        upgrade_mod_savepoint(true, 2016080901, 'facetoface');
    }

    if ($oldversion < 2016092000) {

        // Alter facetoface_sessioncancel_info_data table to ensure customfield id field is unique.
        // Rename the facetofacecancellationid field to facetofacesessioncancelid if the facetoface_sessioncancel_info_data
        // table exists and create new key.

        // Define table facetoface_sessioncancel_info_data to be adjusted.
        $table = new xmldb_table('facetoface_sessioncancel_info_data');
        $field = new xmldb_field('facetofacecancellationid');

        // Conditionally launch if the table and field exists.
        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {

            // Define key cancellationinfodata_cancellationid_fk (foreign) to be dropped form facetoface_sessioncancel_info_data.
            $key = new xmldb_key('cancellationinfodata_cancellationid_fk', XMLDB_KEY_FOREIGN, array('facetofacecancellationid'), 'facetoface_sessions', array('id'));

            // Launch drop key cancellationinfodata_sessioncancelid_fk.
            $dbman->drop_key($table, $key);

            // Rename field facetofacecancellationid on table facetoface_sessioncancel_info_data to facetofacesessioncancelid.
            $field = new xmldb_field('facetofacecancellationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'fieldid');

            // Launch rename field facetofacecancellationid.
            $dbman->rename_field($table, $field, 'facetofacesessioncancelid');

            // Define key cancellationinfodata_sessioncancelid_fk (foreign) to be added to facetoface_sessioncancel_info_data.
            $key = new xmldb_key('cancellationinfodata_sessioncancelid_fk', XMLDB_KEY_FOREIGN, array('facetofacesessioncancelid'), 'facetoface_sessions', array('id'));

            // Launch add key cancellationinfodata_sessioncancelid_fk.
            $dbman->add_key($table, $key);

            // Take care of files.
            mod_facetoface_fix_cancellationid_files();
        }

        upgrade_mod_savepoint(true, 2016092000, 'facetoface');
    }
    if ($oldversion < 2016092800) {
        mod_facetoface_upgrade_notification_titles();
        mod_facetoface_fix_trainercancel_body();
        mod_facetoface_fix_defaultrequestinstrmngrdefault();

        // Copy settings for "Select job assignment on sign up".
        $selectpositiononsignupglobal = get_config(null, 'facetoface_selectpositiononsignupglobal');
        if (!empty($selectpositiononsignupglobal)) {
            set_config('facetoface_selectjobassignmentonsignupglobal', true);
            unset_config('facetoface_selectpositiononsignupglobal');
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2016092800, 'facetoface');
    }
    return $result;
}
