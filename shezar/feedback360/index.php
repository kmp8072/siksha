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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/feedback360/lib.php');

// Check if 360 Feedbacks are enabled.
feedback360::check_feature_enabled();

// Set up page.
require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);
$user = $DB->get_record('user', array('id' => $userid));
$systemcontext = context_system::instance();
$usercontext = context_user::instance($userid);
$strmyfeedback = get_string('myfeedback', 'shezar_feedback360');

$PAGE->set_url(new moodle_url('/shezar/feedback360/index.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);

// Check user has permission to request feedback, and set up the page.
if ($USER->id == $userid) {
    // You are viewing your own feedback.
    $viewrequestee = has_capability('shezar/feedback360:viewownreceivedfeedback360', $systemcontext);
    $viewrequested = has_capability('shezar/feedback360:viewownrequestedfeedback360', $systemcontext);
    $canmanage = has_capability('shezar/feedback360:manageownfeedback360', $systemcontext);
    if (!$viewrequestee && !$viewrequested) {
        // You can't see anything, you should't be accessing this page.
        print_error('error:accessdenied', 'shezar_feedback360');
    }

    $PAGE->set_shezar_menu_selected('feedback360');
    $PAGE->navbar->add(get_string('feedback360', 'shezar_feedback360'), new moodle_url('/shezar/feedback360/index.php'));
    $PAGE->navbar->add($strmyfeedback);
    $PAGE->set_title($strmyfeedback);
    $PAGE->set_heading($strmyfeedback);
} else if (\shezar_job\job_assignment::is_managing($USER->id, $userid)) {
    // You are a manager view a staff members feedback.
    $viewrequestee = has_capability('shezar/feedback360:viewstaffreceivedfeedback360', $usercontext);
    $viewrequested = has_capability('shezar/feedback360:viewstaffrequestedfeedback360', $usercontext);
    $canmanage = has_capability('shezar/feedback360:managestafffeedback', $usercontext);

    $userxfeedback = get_string('userxfeedback360', 'shezar_feedback360', fullname($user));
    if (shezar_feature_visible('myteam')) {
        $PAGE->set_shezar_menu_selected('myteam');
        $PAGE->navbar->add(get_string('team', 'shezar_core'), new moodle_url('/my/teammembers.php'));
    }
    $PAGE->navbar->add($userxfeedback);
    $PAGE->set_title($userxfeedback);
    $PAGE->set_heading($userxfeedback);
} else if (is_siteadmin()) {
    // Site admin can see everything.
    $viewrequestee = true;
    $viewrequested = true;
    $canmanage = true;
} else {
    // You aren't the user, their manager or an admin? throw an error!
    print_error('error:accessdenied', 'shezar_feedback360');
}

$renderer = $PAGE->get_renderer('shezar_feedback360');
$available_forms = feedback360::get_available_forms($userid);
$num_avail_forms = count($available_forms);

// Title.
$header = html_writer::start_tag('div', array('class' => 'myfeedback_header'));
if ($canmanage && $num_avail_forms > 0) {
    $header .= $renderer->request_feedback360_button($userid);
}
$header .= html_writer::end_tag('div');

if ($viewrequestee) {
    // Feedback about user and request feedback button.
    if ($userid == $USER->id) {
        $user_title = get_string('feedback360aboutyou', 'shezar_feedback360');
    } else {
        $fullname = fullname($user);
        $user_title = get_string('feedback360aboutuser', 'shezar_feedback360', $fullname);
    }

    // Join the user assignment to the feedback360 so we have the name later.
    $sql = "SELECT ua.*, fb.name, fb.anonymous, fb.status
            FROM {feedback360_user_assignment} ua
            JOIN {feedback360} fb
            ON ua.feedback360id = fb.id
            WHERE ua.userid = :uid";

    $user_assignments = $DB->get_records_sql($sql, array('uid' => $userid));

    $user_feedback = html_writer::start_tag('div', array('class' => 'user_feedback'));
    $user_feedback .= $renderer->heading($user_title);
    $user_feedback .= $renderer->myfeedback_user_table($userid, $user_assignments, $canmanage);
    $user_feedback .= html_writer::end_tag('div');
}

if ($viewrequested) {

    // Join to user so we have all their user name fields for later.
    $usernamefields = get_all_user_name_fields(true, 'u');
    $sql = "SELECT re.*, ua.feedback360id, ua.timedue, ua.userid as assignedby, {$usernamefields}
            FROM {feedback360_resp_assignment} re
            JOIN {feedback360_user_assignment} ua
            ON re.feedback360userassignmentid = ua.id
            JOIN {user} u
            ON ua.userid = u.id
            WHERE re.userid = :uid";

    $resp_assignments = $DB->get_records_sql($sql, array('uid' => $userid));

    // Give feedback about others.
    if ($USER->id == $userid) {
        $colleagues_title = get_string('feedback360aboutcolleagues', 'shezar_feedback360');
    } else {
        $colleagues_title = get_string('viewuserxresponses', 'shezar_feedback360', fullname($user));
    }

    $colleagues_feedback = html_writer::start_tag('div', array('class' => 'colleagues_feedback'));
    $colleagues_feedback .= $renderer->heading($colleagues_title);
    $colleagues_feedback .= $renderer->myfeedback_colleagues_table($userid, $resp_assignments);
    $colleagues_feedback .= html_writer::end_tag('div');
}

// Display everything.
echo $renderer->header();

echo $renderer->display_userview_header($user);

echo $header;

// Display feedback created by/for the user.
if ($viewrequestee) {
    echo html_writer::empty_tag('br');
    echo $user_feedback;
}

// Display feedback requested of the user.
if ($viewrequested) {
    echo html_writer::empty_tag('br');
    echo $colleagues_feedback;
}

echo $renderer->footer();
