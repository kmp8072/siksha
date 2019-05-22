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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_feedback360
 */

/**
 * View answer on feedback360
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/feedback360/lib.php');
require_once($CFG->dirroot . '/shezar/feedback360/feedback360_forms.php');

// Check if 360 Feedbacks are enabled.
feedback360::check_feature_enabled();

$preview = optional_param('preview', 0, PARAM_INT);
$viewanswer = optional_param('myfeedback', 0, PARAM_INT);
$returnurl = new moodle_url('/shezar/feedback360/index.php');

$token = optional_param('token', '', PARAM_ALPHANUM);
$isexternaluser = ($token != '');

if (!$isexternaluser) {
    require_login();
    if (isguestuser()) {
        $SESSION->wantsurl = qualified_me();
        redirect(get_login_url());
    }
}

// Get response assignment object, and check who is viewing the page.
$viewasown = false;
if ($isexternaluser) {
    // Get the user's email address from the token.
    $email = $DB->get_field('feedback360_email_assignment', 'email', array('token' => $token));
    $respassignment = feedback360_responder::by_email($email, $token);
    $returnurl = new moodle_url('/shezar/feedback360/feedback.php', array('token' => $token));
    if ($respassignment) {
        $respassignment->tokenaccess = true;
    }

} else if ($preview) {
    $feedback360id = required_param('feedback360id', PARAM_INT);

    $systemcontext = context_system::instance();
    $canmanage = has_capability('shezar/feedback360:managefeedback360', $systemcontext);
    $assigned = feedback360::has_user_assignment($USER->id, $feedback360id);
    $manager = feedback360::check_managing_assigned($feedback360id, $USER->id);

    if ($assigned) {
        require_capability('shezar/feedback360:manageownfeedback360', $systemcontext);
        $viewasown = true;
    }

    if (!empty($manager)) {
        $usercontext = context_user::instance($manager[0]); // Doesn't matter which user, just check one.
        require_capability('shezar/feedback360:managestafffeedback', $usercontext);
    }

    if (!$canmanage && !$assigned && empty($manager)) {
        print_error('error:previewpermissions', 'shezar_feedback360');
    }

    $respassignment = feedback360_responder::by_preview($feedback360id);
} else if ($viewanswer) {
    $responseid = required_param('responseid', PARAM_INT);
    $respassignment = new feedback360_responder($responseid);

    if ($respassignment->subjectid != $USER->id) {
        // If you arent the owner of the feedback request.
        if (\shezar_job\job_assignment::is_managing($USER->id, $respassignment->subjectid)) {
            // Or their manager.
            $capability_context = context_user::instance($respassignment->subjectid);
            require_capability('shezar/feedback360:viewstaffreceivedfeedback360', $capability_context);
        } else if (!is_siteadmin()) {
            // Or a site admin, then you shouldnt see this page.
            throw new feedback360_exception('error:accessdenied');
        }
    } else {
        $systemcontext = context_system::instance();
        require_capability('shezar/feedback360:viewownreceivedfeedback360', $systemcontext);
        // You are the owner of the feedback request.
        $viewasown = true;
    }

    // You are viewing something that hasn't been viewed, mark it as viewed.
    if (!$respassignment->viewed) {
        $respassignment->viewed = true;
        $respassignment->save();
    }
} else {
    $feedback360id = required_param('feedback360id', PARAM_INT);
    $subjectid = required_param('userid', PARAM_INT);
    $viewas = optional_param('viewas', $USER->id, PARAM_INT);

    // If you aren't the owner of the response.
    if ($viewas != $USER->id) {
        if (\shezar_job\job_assignment::is_managing($USER->id, $viewas)) {
            // You are a manager viewing your team members responses to someone else, you need to view staff feedback capability.
            $usercontext = context_user::instance($viewas);
            require_capability('shezar/feedback360:viewstaffrequestedfeedback360', $usercontext);
        } else {
            // Otherwise you shouldn't be viewing this page.
            print_error('error:accessdenied');
        }
    } else {
        $viewasown = true;
    }
    $respassignment = feedback360_responder::by_user($viewas, $feedback360id, $subjectid);
}

if (!$respassignment) {
    shezar_set_notification(get_string('feedback360notfound', 'shezar_feedback360'),
            new moodle_url('/shezar/feedback360/index.php'), array('class' => 'notifyproblem'));
}

// Set up the page.
$pageurl = new moodle_url('/shezar/feedback360/index.php');
$PAGE->set_context(null);
$PAGE->set_url($pageurl);

if ($preview || $isexternaluser) {
    $PAGE->set_pagelayout('popup');
} else {
    $PAGE->set_pagelayout('standard');
}

if ($isexternaluser) {
    $heading = get_string('feedback360', 'shezar_feedback360');

    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    $PAGE->set_shezar_menu_selected('appraisals');
    $PAGE->navbar->add($heading);
    $PAGE->navbar->add(get_string('givefeedback', 'shezar_feedback360'));
} else if ($viewasown) {
    $heading = get_string('myfeedback', 'shezar_feedback360');

    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    $PAGE->set_shezar_menu_selected('appraisals');
    $PAGE->navbar->add(get_string('feedback360', 'shezar_feedback360'), new moodle_url('/shezar/feedback360/index.php'));
    $PAGE->navbar->add(get_string('givefeedback', 'shezar_feedback360'));
} else {
    $ownerid = $DB->get_field('feedback360_user_assignment', 'userid', array('id' => $respassignment->feedback360userassignmentid));
    $owner = $DB->get_record('user', array('id' => $ownerid));
    $userxfeedback = get_string('userxfeedback360', 'shezar_feedback360', fullname($owner));

    $PAGE->set_title($userxfeedback);
    $PAGE->set_heading($userxfeedback);
    if (shezar_feature_visible('myteam')) {
        $PAGE->set_shezar_menu_selected('myteam');
        $PAGE->navbar->add(get_string('team', 'shezar_core'), new moodle_url('/my/teammembers.php'));
    }
    $PAGE->navbar->add($userxfeedback);
    $PAGE->navbar->add(get_string('viewresponse', 'shezar_feedback360'));
}

$feedback360 = new feedback360($respassignment->feedback360id);

$backurl = null;
if ($viewanswer) {
    $backurl = new moodle_url('/shezar/feedback360/request/view.php',
            array('userassignment' => $respassignment->feedback360userassignmentid));
} else if (!empty($viewas)) {
    $backurl = new moodle_url('/shezar/feedback360/index.php',
            array('userid' => $viewas));
}
$form = new feedback360_answer_form(null, array('feedback360' => $feedback360, 'resp' => $respassignment, 'preview' => $preview,
        'backurl' => $backurl));

// Process form submission.
if ($form->is_submitted() && !$respassignment->is_completed()) {
    if ($form->is_cancelled()) {
        redirect($returnurl);
    }

    $formisvalid = $form->is_validated(); // Load the form data.
    $answers = $form->get_submitted_data();
    if ($answers->action == 'saveprogress' || ($answers->action == 'submit' && $formisvalid)) {
        // Save.
        $feedback360->save_answers($answers, $respassignment);
        $message = get_string('progresssaved', 'shezar_feedback360');
        if ($answers->action == 'submit') {
            // Mark as answered.
            $respassignment->complete();
            $message = get_string('feedbacksubmitted', 'shezar_feedback360');
        }
        shezar_set_notification($message, $returnurl, array('class' => 'notifysuccess'));
    }
    if ($answers->action == 'submit' && !$formisvalid) {
        shezar_set_notification(get_string('error:submitform', 'shezar_feedback360'), null, array('class' => 'notifyproblem'));
    }
} else if (!$preview) {
    $form->set_data($feedback360->get_answers($respassignment));
}

$jsmodule = array(
    'name' => 'shezar_feedback360_feedback',
    'fullpath' => '/shezar/feedback360/js/feedback.js',
    'requires' => array('json'));
$PAGE->requires->js_init_call('M.shezar_feedback360_feedback.init', array($form->_form->getAttribute('id')),
        false, $jsmodule);

$renderer = $PAGE->get_renderer('shezar_feedback360');

echo $renderer->header();

$numresponders = $DB->get_field('feedback360_resp_assignment', 'COUNT(id)',
    array('feedback360userassignmentid' => $respassignment->feedback360userassignmentid));
if ($preview) {
    $feedbackname = $DB->get_field_select('feedback360', 'name', 'id = :fbid', array('fbid' => $respassignment->feedback360id));
    echo $renderer->display_preview_feedback_header($respassignment, $feedbackname);
} else {
    $subjectuser = $DB->get_record('user', array('id' => $respassignment->subjectid));
    echo $renderer->display_feedback_header($respassignment, $subjectuser, $feedback360->anonymous, $numresponders);
}
$form->display();

echo $renderer->footer();
