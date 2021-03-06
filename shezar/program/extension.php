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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage program
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');

require_login();
require_sesskey();

$programid = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$extensionrequest = optional_param('extrequest', false, PARAM_BOOL);
$extensiondate = optional_param('extdate', '', PARAM_TEXT);
$extensionhour = optional_param('exthour', 0, PARAM_INT);
$extensionminute = optional_param('extminute', 0, PARAM_INT);
$extensionreason = optional_param('extreason', '', PARAM_TEXT);

$PAGE->set_context(context_program::instance($programid));

$OUTPUT->header();

$result = array();

if ($USER->id != $userid) {
    $result['success'] = false;
    $result['message'] = get_string('error:cannotrequestextnotuser', 'shezar_program');
    echo json_encode($result);
    return;
}

$program = new program($programid);

if (!$extensionrequest || !$extensiondate || !$extensionreason) {
    $result['success'] = false;
    $result['message'] = get_string('error:processingextrequest', 'shezar_program');
    echo json_encode($result);
    return;
}

$managers = \shezar_job\job_assignment::get_all_manager_userids($userid);
if (empty($managers)) {
    $result['success'] = false;
    $result['message'] = get_string('extensionrequestfailed:nomanager', 'shezar_program');
    echo json_encode($result);
    return;
}

// We receive the date as a string, but also need to append hour and minute so that the timestamp includes those.
// This appended string is used for processing only and is not shown to users.
$extensionhour = sprintf("%02d", $extensionhour);
$extensionminute = sprintf("%02d", $extensionminute);
$extensiondate_timestamp = shezar_date_parse_from_format(get_string('datepickerlongyearparseformat', 'shezar_core').' H:i',
    $extensiondate.' '.$extensionhour.':'.$extensionminute);  // convert to timestamp

$extension = new stdClass;
$extension->programid = $program->id;
$extension->userid = $userid;
$extension->extensiondate = $extensiondate_timestamp;

// Validated extension date to make sure it is after
// current due date and not in the past
if ($prog_completion = $DB->get_record('prog_completion', array('programid' => $program->id, 'userid' => $userid, 'coursesetid' => 0))) {
    $duedate = empty($prog_completion->timedue) ? 0 : $prog_completion->timedue;

    if ($extensiondate_timestamp < $duedate) {
        $result['success'] = false;
        $result['message'] = get_string('extensionearlierthanduedate', 'shezar_program');
        echo json_encode($result);
        return;
    }
} else {
    $result['success'] = false;
    $result['message'] = get_string('error:noprogramcompletionfound', 'shezar_program');
    echo json_encode($result);
    return;
}

$now = time();
if ($extensiondate_timestamp < $now) {
    $result['success'] = false;
    $result['message'] = get_string('extensionbeforenow', 'shezar_program');
    echo json_encode($result);
    return;
}

$extension->extensionreason = $extensionreason;
$extension->status = 0;

if ($extensionid = $DB->insert_record('prog_extension', $extension)) {

    $data = array();
    $data['extensionid'] = $extensionid;

    // We'll need this to Send requests in the managers language.
    $strmgr = get_string_manager();

    // Get record for learner requesting extension.
    $user = $DB->get_record('user', array('id' => $userid));
    $userfullname = fullname($user);

    // Send to all the users managers.
    $messagesent = false;
    foreach ($managers as $managerid) {
        $manager = core_user::get_user($managerid, '*', MUST_EXIST);

        // Create object to add into extensionrequestmessage string (for the content of the email to the manager).
        $extensiontime = userdate($extensiondate_timestamp, $strmgr->get_string('strftimedatetime', 'langconfig', null, $manager->lang), core_date::get_user_timezone($manager));
        $manageurl = new moodle_url('/shezar/program/manageextensions.php');
        $extensiondata = array(
            'extensiondatestr'      => $extensiontime,
            'extensionreason'       => $extensionreason,
            'programfullname'       => format_string($program->fullname),
            'manageurl'             => $manageurl->out()
        );

        $extension_message = new prog_extension_request_message($program->id, $extension->userid, null, null, $data);
        $managermessagedata = $extension_message->get_manager_message_data();
        $managermessagedata->subject = $strmgr->get_string('extensionrequest', 'shezar_program', $userfullname, $manager->lang);
        $managermessagedata->fullmessage = $strmgr->get_string('extensionrequestmessage', 'shezar_program', (object)$extensiondata, $manager->lang);
        $managermessagedata->contexturlname = $strmgr->get_string('manageextensionrequests', 'shezar_program', null, $manager->lang);
        $managermessagedata->infobutton = $strmgr->get_string('extensioninfo_button', 'shezar_program', null, $manager->lang);
        $managermessagedata->infotext = $strmgr->get_string('extensioninfo_text', 'shezar_program', null, $manager->lang);

        $managermessagedata->acceptbutton = $strmgr->get_string('extensionacceptbutton', 'shezar_program', null, $manager->lang);
        $managermessagedata->accepttext = $strmgr->get_string('extensionaccepttext', 'shezar_program', null, $manager->lang);

        $managermessagedata->rejectbutton = $strmgr->get_string('extensionrejectbutton', 'shezar_program', null, $manager->lang);
        $managermessagedata->rejecttext = $strmgr->get_string('extensionrejecttext', 'shezar_program', null, $manager->lang);

        $messagesent = $extension_message->send_message($manager, $user) || $messagesent;
    }

    if ($messagesent) {
        // If any of the managers have been notified mark the request as pending.
        $result['success'] = true;
        $result['message'] = get_string('pendingextension', 'shezar_program');
    } else {
        $result['success'] = false;
        $result['message'] = get_string('extensionrequestnotsent', 'shezar_program');
    }
    echo json_encode($result);
    return;
} else {
    $result['success'] = false;
    $result['message'] = get_string('extensionrequestfailed', 'shezar_program');
    echo json_encode($result);
    return;
}
