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

/**
 * Facetoface module upgrade related helper functions
 *
 * @package    mod_facetoface
 * @author     Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Helper function to aid in the migration of signup custom field data.
 *
 * $CFG->facetoface_customfield_migration_behaviour is used to determine behaviour.
 * If facetoface_customfield_migration_behaviour is not set then this will map the last non-empty data as the users current data.
 * Alternatively facetoface_customfield_migration_behaviour can be set to "latest" in which case the latest record regardless of
 * whether it is empty or not is restored.
 *
 * @param moodle_database $db
 * @param database_manager $dbman
 * @param string $tablename The name of the data table.
 * @param string $field The name of the field that is used as the id reference on the table.
 */
function mod_facetoface_migrate_session_signup_customdata(moodle_database $db, database_manager $dbman, $tablename, $field) {
    global $CFG;

    $temptable = new xmldb_table('facetoface_migration_temp');
    $temptable->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $temptable->add_field('signupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $temptable->add_field('statusid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    $dbman->create_table($temptable);

    if ($field === 'facetofacecancellationid') {
        $comparison = '=';
    } else {
        $comparison = '<>';
    }

    $transaction = $db->start_delegated_transaction();

    // Populate the mapping table.
    if (isset($CFG->facetoface_customfield_migration_behaviour) && $CFG->facetoface_customfield_migration_behaviour === 'latest') {
        $sql = "INSERT INTO {facetoface_migration_temp} (dataid, statusid, signupid)
                     SELECT d.id, ss.statusid, ss.signupid
                       FROM {{$tablename}} d
                       JOIN (SELECT MAX(s.id) AS statusid, s.signupid
                                FROM {facetoface_signups_status} s
                               WHERE s.statuscode {$comparison} 10
                            GROUP BY s.signupid
                            ) ss ON ss.statusid = d.{$field}";
        $db->execute($sql);
    } else {
        $sql = "INSERT INTO {facetoface_migration_temp} (dataid, statusid, signupid)
                     SELECT d.id, ss.statusid, ss.signupid
                       FROM {{$tablename}} d
                       JOIN (SELECT MAX(s.id) AS statusid, s.signupid
                                FROM {facetoface_signups_status} s
                                JOIN {{$tablename}} t ON t.{$field} = s.id
                               WHERE s.statuscode {$comparison} 10 AND t.data <> ''
                            GROUP BY s.signupid
                            ) ss ON ss.statusid = d.{$field}";
        $db->execute($sql);
    }

    // First drop all redundant rows, if we try the update before this then we bust the null constraint for rows that
    // will be redundant.
    $sql = "DELETE FROM {{$tablename}}
                  WHERE id NOT IN (
                      SELECT dataid
                        FROM {facetoface_migration_temp})";
    $db->execute($sql);

    // Now update the rows that remain to point to the signupid rather than the statusid.
    $sql = "UPDATE {{$tablename}}
               SET {$field} = (
                    SELECT signupid
                      FROM {facetoface_migration_temp}
                     WHERE {facetoface_migration_temp}.dataid = {{$tablename}}.id)";
    $db->execute($sql);

    $transaction->allow_commit();

    $dbman->drop_table($temptable);
}

/**
 * Upgrade calendar search config settings to support new customfield search
 */
function mod_facetoface_calendar_search_config_upgrade() {
    $exconfig = get_config(null, 'facetoface_calendarfilters');
    $newconfig = array();
    if (!empty($exconfig)) {
        $exall = explode(',', $exconfig);
        $roomfound = false;
        foreach ($exall as $val) {
            if ($val == 'room' || $val == 'address') {
                if (!$roomfound) {
                    $newconfig[] = 'room_1';
                }
                $roomfound = true;
            }
            else if ($val == 'building') {
                $newconfig[] = 'room_2';
            }
            else if (is_number($val)) {
                // Previous version supported only session customfields and stored them as numbers.
                $newconfig[] = 'sess_' . $val;
            } else {
                // Other fields we pass through (in case of double run of upgrade).
                $newconfig[] = $val;
            }
        }
    }

    set_config('facetoface_calendarfilters', implode(',', $newconfig));
}

/*
 * Delete orphaned custom field data.
 *
 * @param string          $type The type of customfield e.g. signup/cancellation
 */
function mod_facetoface_delete_orphaned_customfield_data($type) {
    global $DB;

    $foreignkey = "facetoface{$type}id";
    $customfield_table = "facetoface_{$type}_info_data";
    $customparam_table = "facetoface_{$type}_info_data_param";

    $customfieldids = $DB->get_fieldset_select(
        $customfield_table,
        'id',
        $foreignkey . ' NOT IN (SELECT s.id FROM {facetoface_signups} s)'
    );

    $managablechunks = array_chunk($customfieldids, $DB->get_max_in_params());

    $transaction = $DB->start_delegated_transaction();
    foreach ($managablechunks as $chunk) {
        if (!empty($chunk)) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($chunk);
            $DB->delete_records_select($customparam_table, "dataid {$sqlin}", $inparams);
            $DB->delete_records_select($customfield_table, "id {$sqlin}", $inparams);
        }
    }
    $transaction->allow_commit();
}

/**
 * Copy files from facetofacecancellation* to facetofacesessioncancel* file area to make them available after field rename
 */
function mod_facetoface_fix_cancellationid_files() {
    global $DB;

    // Get itemid's for user cancelation.
    $usersql = "
    SELECT fcid.id
    FROM {facetoface_cancellation_info_data} fcid
    INNER JOIN {facetoface_cancellation_info_field} fcif ON (fcif.id = fcid.fieldid)
    WHERE fcif.datatype IN ('file', 'textarea')
    ";
    $usercancelrecords = $DB->get_records_sql($usersql, []);
    $usercancelids = empty($usercancelrecords) ? [] : array_keys($usercancelrecords);

    // Get itemid's for session cancelation.
    $sessionsql = "
    SELECT fscid.id
    FROM {facetoface_sessioncancel_info_data} fscid
    INNER JOIN {facetoface_sessioncancel_info_field} fscif ON (fscif.id = fscid.fieldid)
    WHERE fscif.datatype IN ('file', 'textarea')
    ";
    $sessioncancelrecords = $DB->get_records_sql($sessionsql, []);

    if (!empty($sessioncancelrecords)) {
        $sessioncancelids = array_keys($sessioncancelrecords);

        // Fix filearea.
        $fs = get_file_storage();
        $confcontext = context_system::instance();
        $component = 'shezar_customfield';
        $areas = [
            'facetofacecancellation_filemgr' => 'facetofacesessioncancel_filemgr',
            'facetofacecancellation' => 'facetofacesessioncancel'
        ];

        foreach($areas as $oldarea => $newarea) {
            $files = $fs->get_area_files($confcontext->id, $component, $oldarea);
            if (is_array($files)) {
                $itemidstodel = [];
                foreach ($files as $file) {
                    if (in_array($file->get_itemid(), $sessioncancelids)) {
                        $fs->create_file_from_storedfile(array('filearea' => $newarea), $file);

                        $itemid = $file->get_itemid();
                        if (!in_array($itemid, $usercancelids) && !empty($itemid)) {
                            // This file is not used in user cancellation. Remove it.
                            $itemidstodel[$itemid] = 1;
                        }
                    }
                }
                if (!empty($itemidstodel)) {
                    foreach ($itemidstodel as $itemid => $unused) {
                        $fs->delete_area_files($confcontext->id, $component, $oldarea, $itemid);
                    }
                }
            }
        }
    }
}

/**
 * Upgrade notifications titles from facetoface to seminar.
 */
function mod_facetoface_upgrade_notification_titles() {
    global $DB;
    // Upgrade notification titles.
    // Strings for previous template defaults just prior to 9.0. Uses their reference as keys.
    $oldtemplatedefaults = array();
    $oldtemplatedefaults['confirmation'] = get_string('setting:defaultconfirmationsubjectdefault', 'facetoface');
    $oldtemplatedefaults['cancellation'] = get_string('setting:defaultcancellationsubjectdefault', 'facetoface');
    $oldtemplatedefaults['waitlist'] = get_string('setting:defaultwaitlistedsubjectdefault', 'facetoface');
    $oldtemplatedefaults['reminder'] = get_string('setting:defaultremindersubjectdefault', 'facetoface');
    $oldtemplatedefaults['request'] = get_string('setting:defaultrequestsubjectdefault', 'facetoface');
    $oldtemplatedefaults['decline'] = get_string('setting:defaultdeclinesubjectdefault', 'facetoface');
    $oldtemplatedefaults['timechange'] = get_string('setting:defaultdatetimechangesubjectdefault', 'facetoface');
    $oldtemplatedefaults['trainercancel'] = get_string('setting:defaulttrainersessioncancellationsubjectdefault', 'facetoface');
    $oldtemplatedefaults['trainerunassign'] = get_string('setting:defaulttrainersessionunassignedsubjectdefault', 'facetoface');
    $oldtemplatedefaults['trainerconfirm'] = get_string('setting:defaulttrainerconfirmationsubjectdefault', 'facetoface');
    $oldtemplatedefaults['allreservationcancel'] = get_string('setting:defaultcancelallreservationssubjectdefault', 'facetoface');
    $oldtemplatedefaults['reservationcancel'] = get_string('setting:defaultcancelreservationsubjectdefault', 'facetoface');

    // Strings for new template defaults with new placeholder variables introduced in 9.0. Uses their reference as keys.
    $newtemplatedefaults = array();
    $newtemplatedefaults['confirmation'] = get_string('setting:defaultconfirmationsubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['cancellation'] = get_string('setting:defaultcancellationsubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['waitlist'] = get_string('setting:defaultwaitlistedsubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['reminder'] = get_string('setting:defaultremindersubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['request'] = get_string('setting:defaultrequestsubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['decline'] = get_string('setting:defaultdeclinesubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['timechange'] = get_string('setting:defaultdatetimechangesubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['trainercancel'] = get_string('setting:defaulttrainersessioncancellationsubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['trainerunassign'] = get_string('setting:defaulttrainersessionunassignedsubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['trainerconfirm'] = get_string('setting:defaulttrainerconfirmationsubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['allreservationcancel'] = get_string('setting:defaultcancelallreservationssubjectdefault_v9', 'facetoface');
    $newtemplatedefaults['reservationcancel'] = get_string('setting:defaultcancelreservationsubjectdefault_v9', 'facetoface');

    // This will hold templates that were found to match the pre-9.0 defaults. With format array(id => reference).
    $templateswithdefaults = array();

    // Update notification templates.
    $templates = $DB->get_records('facetoface_notification_tpl');
    foreach ($templates as $template) {
        if (isset($template->title) && isset($template->reference) && isset($oldtemplatedefaults[$template->reference])
                && (strcmp($template->title, $oldtemplatedefaults[$template->reference]) === 0)) {
            $template->title = $newtemplatedefaults[$template->reference];
            $templateswithdefaults[$template->id] = $template->reference;
            $DB->update_record('facetoface_notification_tpl', $template);
        }
    }

    // Update notifications.
    $f2f_notifications = $DB->get_records('facetoface_notification');
    foreach ($f2f_notifications as $f2f_notification) {
        if (isset($f2f_notification->title) && isset($templateswithdefaults[$f2f_notification->templateid])) {
            // This notification uses a template that matched the default lang string.
            $reference = $templateswithdefaults[$f2f_notification->templateid];
            if (strcmp($f2f_notification->title, $oldtemplatedefaults[$reference]) === 0) {
                // This notification also matched the same default lang string. So we'll update it
                // to the new default.
                $f2f_notification->title = $newtemplatedefaults[$reference];
                $DB->update_record('facetoface_notification', $f2f_notification);
            }
        }
    }
}

/**
 * During upgrade from 2.9 and prior to 9.0rc1 notification message for trainercancel had wrong string in lang file
 * (different from original) as result it was not updated, but dates format was changed anyway (as fallback scenario).
 * String is fixed now, but we still need to cover upgrades from 9.0rc1 to 9+ by refixing this particualr string.
 * It make sense only for English version.
 */
function mod_facetoface_fix_trainercancel_body() {
    global $DB;
    // This is resulting template
    $wrongtemplate = '<div class="text_to_html">This is to advise that your assigned training session the following course has been cancelled:<br />
<br />
***SESSION CANCELLED***<br />
<br />
Participant:   [firstname] [lastname]<br />
Course:   [coursename]<br />
Face-to-face:   [facetofacename]<br />
<br />
Duration:   [duration]<br />
Date(s):<br />
[#sessions][session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]<br>[/sessions]<br />
<br />
Location:   [session:location]<br />
Venue:   [session:venue]<br />
Room:   [session:room]<br />
</div>';

    $newtemplate = text_to_html(get_string('setting:defaulttrainersessioncancellationmessagedefault_v9', 'facetoface'));
    // Update notification templates.
    $template = $DB->get_record('facetoface_notification_tpl', ['reference' => 'trainercancel']);
    if (!empty($template) && strcmp($template->body, $wrongtemplate) === 0) {
        $template->body = $newtemplate;
        $DB->update_record('facetoface_notification_tpl', $template);

        // Upgrade acitivities template.
        $f2f_notifications = $DB->get_records('facetoface_notification', ['templateid' => $template->id]);
        if (!empty($f2f_notifications)) {
            foreach ($f2f_notifications as $notification) {
                if (strcmp($notification->body, $wrongtemplate) === 0) {
                    $notification->body = $newtemplate;
                    $DB->update_record('facetoface_notification', $notification);
                }
            }
        }
    }
}

/**
 * During upgrade from 2.4 and prior to 9.0rc1 notification message for defaultrequestinstrmngrdefault had different from original
 * string in lang file as result it was not updated, but dates format was changed anyway (as fallback scenario).
 * String is fixed now, but we still need to cover upgrades from 9.0rc1 to 9+ by refixing this particualr string.
 * It make sense only for English version.
 */
function mod_facetoface_fix_defaultrequestinstrmngrdefault() {
    global $DB;
    // This is resulting template
    $wrongtemplate = '<div class="text_to_html">This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as their Team Leader / Manager.<br />
<br />
Course:   [coursename]<br />
Face-to-face:   [facetofacename]<br />
Cost:   [cost]<br />
<br />
Duration:   [duration]<br />
Date(s):<br />
[#sessions][session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]<br>[/sessions]<br />
<br />
Location:   [session:location]<br />
Venue:   [session:venue]<br />
Room:   [session:room]<br />
<br />
Please follow the link below to approve the request:<br />
[attendeeslink]<br />
<br />
</div>';

    $newtemplate = text_to_html(get_string('setting:defaultrequestinstrmngrdefault', 'facetoface'));
    // Update notification templates.
    $template = $DB->get_record('facetoface_notification_tpl', ['reference' => 'request']);
    if (!empty($template) && strcmp($template->managerprefix, $wrongtemplate) === 0) {
        $template->managerprefix = $newtemplate;
        $DB->update_record('facetoface_notification_tpl', $template);

        // Upgrade acitivities template.
        $f2f_notifications = $DB->get_records('facetoface_notification', ['templateid' => $template->id]);
        if (!empty($f2f_notifications)) {
            foreach ($f2f_notifications as $notification) {
                if (strcmp($notification->managerprefix, $wrongtemplate) === 0) {
                    $notification->managerprefix = $newtemplate;
                    $DB->update_record('facetoface_notification', $notification);
                }
            }
        }
    }
}