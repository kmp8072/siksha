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
 * @package tool
 * @subpackage tool_shezar_timezonefix
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tooltimezonefix');
$badzones = shezar_get_bad_timezone_list();
$goodzones = core_date::get_list_of_timezones();
$errors = array();
$notifications = array();

if ($data = data_submitted()) {
    require_sesskey();

    foreach ($data as $key => $value) {
        if(substr($key, 0, 8) == 'badzone_') {
            $badzone = substr($key, 8);
            //moodle changes periods in element names to an underscore so we need to adjust for the UTC offsets
            if (!isset($badzones[$badzone]) && strpos($badzone, '_') !== false) {
                $offset = str_replace('_', '.', $badzone);
                //if it still doesn't match it's probably an unknown zone so leave it alone
                if (isset($badzones[$offset])) {
                    $badzone = $offset;
                }
            }
            $a = new stdClass();
            $a->badzone = $badzone;
            $a->replacewith = $value;
            $sql = "UPDATE {user} set timezone = ? WHERE timezone = ?";
            if ($DB->execute($sql, array($value, $badzone))) {
                $notifications[] = get_string('updatetimezonesuccess', 'tool_shezar_timezonefix', $a);
            } else {
                $errors[] = get_string('error:updatetimezone', 'tool_shezar_timezonefix', $a);
            }
        }
    }
}
$strheader = get_string('pluginname', 'tool_shezar_timezonefix');
echo $OUTPUT->header();

echo $OUTPUT->heading($strheader);
foreach ($errors as $error) {
    echo $OUTPUT->notification($error, 'notifyproblem');
}
foreach ($notifications as $note) {
    echo $OUTPUT->notification($note, 'notifysuccess');
}
echo $OUTPUT->notification(get_string('infomessage', 'tool_shezar_timezonefix'), 'notifymessage');

$defaultzone = core_date::get_server_timezone();

//first find really strange stuff that we don't understand at all (may have come from e.g. shezar_sync)
$unknownusercount = 0;
$unknownzones = array();
$fullzones = array_merge(array_keys($badzones), array_keys($goodzones));
$fullzones[] = 99;
list($insql, $inparams) = $DB->get_in_or_equal($fullzones, SQL_PARAMS_QM, 'param', false);
$sql = "SELECT count(id) from {user} WHERE timezone $insql";
$unknownusercount = $DB->count_records_sql($sql, $inparams);
if ($unknownusercount > 0) {
    $sql = "SELECT DISTINCT timezone from {user} WHERE timezone $insql";
    $unknownzones = $DB->get_fieldset_sql($sql, $inparams);
    $a = new stdClass();
    $a->numusers = $unknownusercount;
    $a->badzonelist = implode(", ", $unknownzones);
    echo $OUTPUT->notification(get_string('error:unknownzones', 'tool_shezar_timezonefix', $a), 'notifyproblem');
}


list($insql, $inparams) = $DB->get_in_or_equal(array_keys($badzones));
$sql = "SELECT count(id) from {user} WHERE timezone $insql";
$badusercount = $DB->count_records_sql($sql, $inparams);
$totalbad = $badusercount + $unknownusercount;

if ($totalbad > 0) {
    echo $OUTPUT->notification(get_string('numbadusertimezones', 'tool_shezar_timezonefix', $totalbad), 'notifynotice');
    list($insql, $inparams) = $DB->get_in_or_equal(array_keys($badzones));
    $sql = "SELECT DISTINCT timezone from {user} WHERE timezone $insql";
    $badzonestofix = $DB->get_fieldset_sql($sql, $inparams);
    $table = new html_table();
    $table->attributes = array('class' => 'generalbox boxaligncenter fullwidth');
    $table->head = array(
        get_string('badzone', 'tool_shezar_timezonefix'),
        get_string('numusers', 'tool_shezar_timezonefix'),
        get_string('replacewith', 'tool_shezar_timezonefix'),
    );
    foreach ($badzonestofix as $zone) {
        $cells = array();
        $cells[] = $zone;
        $sql = "SELECT count(id) from {user} WHERE timezone = ?";
        $badusers = $DB->count_records_sql($sql, array($zone));
        $cells[] = $badusers;
        //select pre-set to suggested replacement
        $replace = (isset($badzones[$zone])) ? $badzones[$zone] : $defaultzone;
        $cells[] = html_writer::select($goodzones, 'badzone_' . $zone, $replace);
        $row = new html_table_row($cells);
        $table->data[] = $row;
    }
    foreach ($unknownzones as $zone) {
        $cells = array();
        $cells[] = $zone;
        $sql = "SELECT count(id) from {user} WHERE timezone = ?";
        $unknownusers = $DB->count_records_sql($sql, array($zone));
        $cells[] = $unknownusers;
        //select pre-set to suggested replacement
        $cells[] = html_writer::select($goodzones, 'badzone_' . $zone, $defaultzone);
        $row = new html_table_row($cells);
        $table->data[] = $row;
    }
    $output = html_writer::start_tag('form', array('method' => 'post', 'action' => $PAGE->url->out()));
    $output .= html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    $output .= html_writer::table($table);
    $output .= $OUTPUT->single_submit(get_string('updatetimezones', 'tool_shezar_timezonefix'));
    $output .= html_writer::end_tag('form');
    echo $output;
} else {
    echo $OUTPUT->notification(get_string('nobadusertimezones', 'tool_shezar_timezonefix'), 'notifysuccess');
}
echo $OUTPUT->footer();
