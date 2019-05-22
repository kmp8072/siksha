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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar
 * @subpackage shezar_feedback360
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

// URL params.
$assigid = required_param('userassignment', PARAM_INT);

// Set up some variables.
if (!$user_assignment = $DB->get_record('feedback360_user_assignment', array('id' => $assigid))) {
    print_error('userassignmentnotfound', 'shezar_feedback360');
}

$userid = $user_assignment->userid;
$context = context_system::instance();
$usercontext = context_user::instance($userid);

// Check some permissions before going any further.
if ($userid == $USER->id) {
    // You are viewing your own feedback.
    require_capability('shezar/feedback360:viewownreceivedfeedback360', $context);
} else if (!is_siteadmin()) {
    // Skip this check if you are a site admin.
    if (!\shezar_job\job_assignment::is_managing($USER->id, $userid)) {
        print_error('error:accessdenied', 'shezar_feedback360');
    }

    // You are a manager view a staff members feedback.
    require_capability('shezar/feedback360:viewstaffreceivedfeedback360', $usercontext);
}


$feedback = $DB->get_record('feedback360', array('id' => $user_assignment->feedback360id));
$strviewrequest = get_string('viewrequest', 'shezar_feedback360');
$requested_sql = 'SELECT MAX(ra.timeassigned)
                     FROM {feedback360_resp_assignment} ra
                     WHERE ra.feedback360userassignmentid = :uaid';
$requested_param = array('uaid' => $user_assignment->id);
$requested_time = $DB->get_field_sql($requested_sql, $requested_param);
$requested = get_string('requested', 'shezar_feedback360') .
        userdate($requested_time, get_string('strftimedate', 'langconfig'));
$timedue = '';
if (!empty($user_assignment->timedue)) {
    $timedue = get_string('timedue', 'shezar_feedback360') .
            userdate($user_assignment->timedue, get_string('strftimedate', 'langconfig'));
}

// Set up the page.
$PAGE->set_url(new moodle_url('/shezar/feedback360/request/view.php'), array('userassignment' => $assigid));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

$owner = $DB->get_record('user', array('id' => $userid));
if ($USER->id == $userid) {
    $strmyfeedback = get_string('myfeedback', 'shezar_feedback360');
    $PAGE->set_shezar_menu_selected('feedback360');
    $PAGE->navbar->add(get_string('feedback360', 'shezar_feedback360'), new moodle_url('/shezar/feedback360/index.php'));
    $PAGE->navbar->add($strmyfeedback);
    $PAGE->set_title($strviewrequest);
    $PAGE->set_heading($strviewrequest);
} else {
    $userxfeedback = get_string('userxfeedback360', 'shezar_feedback360', fullname($owner));
    if (shezar_feature_visible('myteam')) {
        $PAGE->set_shezar_menu_selected('myteam');
        $PAGE->navbar->add(get_string('team', 'shezar_core'), new moodle_url('/my/teammembers.php'));
    }
    $PAGE->navbar->add($userxfeedback);
    $PAGE->set_title($userxfeedback);
    $PAGE->set_heading($userxfeedback);
}

$PAGE->navbar->add($strviewrequest);

if ($feedback->anonymous) {
    $orderfields = "ra.timecompleted DESC, ra.id";
} else {
    $orderfields = "u.firstname, u.lastname";
}

// Get all the associated resp_assignments to go through and form the table.
$usernamefields = get_all_user_name_fields(true, 'u');
$resp_sql = "SELECT ra.*, ea.email, {$usernamefields}
               FROM {feedback360_resp_assignment} ra
          LEFT JOIN {feedback360_email_assignment} ea
                 ON ra.feedback360emailassignmentid = ea.id
               JOIN {user} u
                 ON ra.userid = u.id
              WHERE ra.feedback360userassignmentid = :uaid
                AND u.deleted = 0
           ORDER BY {$orderfields}";
$resp_params = array('uaid' => $user_assignment->id);
$resp_assignments = $DB->get_records_sql($resp_sql, $resp_params);

$renderer = $PAGE->get_renderer('shezar_feedback360');

// Output the page.
echo $renderer->header();

echo $renderer->display_userview_header($DB->get_record('user', array('id' => $userid)));

echo $renderer->heading(format_string($feedback->name));
echo html_writer::start_tag('div', array('class' => 'requestdates'));
echo $requested . ' ' . $timedue;
echo html_writer::end_tag('div');

echo $renderer->view_request_infotable($user_assignment, $resp_assignments, $feedback->anonymous);

$backurl = new moodle_url('/shezar/feedback360/index.php',
        array('userid' => $userid));
echo html_writer::link($backurl, get_string('back'), array('class' => 'backlink'));

echo $renderer->footer();
