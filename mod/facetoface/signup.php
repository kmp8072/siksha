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
 * @package mod_facetoface
 */

require_once '../../config.php';
require_once 'lib.php';
require_once 'signup_form.php';

$s = required_param('s', PARAM_INT); // facetoface session ID
$backtoallsessions = optional_param('backtoallsessions', 0, PARAM_BOOL);

if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance("facetoface", $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

/** @var enrol_shezar_facetoface_plugin $enrol */
$enrol = enrol_get_plugin('shezar_facetoface');
$sessions = $enrol->get_enrolable_sessions($course->id);
$sessionkeys = array_keys($sessions);
$candirectenrol = in_array($s, $sessionkeys);
if ($candirectenrol) {
    // F2f direct enrolment is enabled for this session.
    require_login();
} else {
    // F2f direct enrolment is not enabled here, the user must have the ability to sign up for sessions
    // in this f2f as normal.
    require_login($course, false, $cm);
    require_capability('mod/facetoface:view', $context);
}

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

$pagetitle = format_string($facetoface->name);

$PAGE->set_cm($cm);
$PAGE->set_url('/mod/facetoface/signup.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

// Guests can't signup for a session, so offer them a choice of logging in or going back.
if (isguestuser()) {
    $loginurl = $CFG->wwwroot.'/login/index.php';
    if (!empty($CFG->loginhttps)) {
        $loginurl = str_replace('http:','https:', $loginurl);
    }

    echo $OUTPUT->header();
    $out = html_writer::tag('p', get_string('guestsno', 'facetoface')) . '\n\n' . html_writer::tag('p', get_string('liketologin', 'facetoface'));
    echo $OUTPUT->confirm($out, $loginurl, get_local_referer(false));
    echo $OUTPUT->footer();
    exit();
}

$showdiscountcode = (!get_config(null, 'facetoface_hidecost') && !get_config(null, 'facetoface_hidediscount') && $session->discountcost > 0);

$approvaltype = $facetoface->approvaltype;

$approvalterms = format_text($facetoface->approvalterms, FORMAT_PLAIN);
$approvaladmins = $facetoface->approvaladmins;

$facetoface_allowwaitlisteveryone = get_config(null, 'facetoface_allowwaitlisteveryone');
$waitlisteveryone = !empty($facetoface_allowwaitlisteveryone) && $session->waitlisteveryone;
$signupbywaitlist = facetoface_is_signup_by_waitlist($session);

$session->managerids   = \shezar_job\job_assignment::get_all_manager_userids($USER->id);
$session->trainerroles = facetoface_get_trainer_roles(context_course::instance($course->id));
$session->trainers     = facetoface_get_trainers($session->id);

if ($facetoface->approvaltype == APPROVAL_ROLE) {
    if (!$session->trainerroles || !$session->trainers) {
        print_error('error:missingrequiredrole', 'facetoface', $returnurl);
    }
}

$params = compact('session', 'facetoface', 'backtoallsessions', 'managerid', 'showdiscountcode', 'waitlisteveryone', 'signupbywaitlist');
$mform = new mod_facetoface_signup_form(null, $params, 'post', '', array('name' => 'signupform'));

// Setup custom javascript
local_js(array(
    shezar_JS_DIALOG,
    shezar_JS_TREEVIEW
));

$PAGE->requires->strings_for_js(array('selectmanager'), 'mod_facetoface');
$jsmodule = array(
        'name' => 'facetoface_managerselect',
        'fullpath' => '/mod/facetoface/js/manager.js',
        'requires' => array('json'));
$selected_manager = dialog_display_currently_selected(get_string('currentmanager', 'mod_facetoface'), 'manager');
$args = array(
    'userid' => $USER->id,
    'fid' => $facetoface->id,
    'manager' => $selected_manager,
    'sesskey' => sesskey()
);

$PAGE->requires->js_init_call('M.facetoface_managerselect.init', $args, false, $jsmodule);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) { // Form submitted
    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    // If multiple sessions are allowed then just check against this session
    // Otherwise check against all sessions
    $multisessionid = ($facetoface->multiplesessions ? $session->id : null);
    if (!facetoface_session_has_capacity($session, $context) && (!$session->allowoverbook)) {
        print_error('sessionisfull', 'facetoface', $returnurl);
    } else if (facetoface_get_user_submissions($facetoface->id, $USER->id, MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_FULLY_ATTENDED, $multisessionid)) {
        print_error('alreadysignedup', 'facetoface', $returnurl);
    } else if (!empty($session->registrationtimestart) && ($session->registrationtimestart > time())) {
        $refresh = new moodle_url('/mod/facetoface/signup.php', array('s' => $session->id));
        redirect($refresh);
    } else if (!empty($session->registrationtimefinish) && ($session->registrationtimefinish < time())) {
        $refresh = new moodle_url('/mod/facetoface/signup.php', array('s' => $session->id));
        redirect($refresh);
    }

    $params = array();
    $params['discountcode']     = $fromform->discountcode;
    $params['notificationtype'] = $fromform->notificationtype;

    $f2fselectedjobassignmentelemid = 'selectedjobassignment_' . $session->facetoface;

    if (property_exists($fromform, $f2fselectedjobassignmentelemid)) {
        $params['jobassignmentid'] = $fromform->$f2fselectedjobassignmentelemid;
    }

    $managerselect = get_config(null, 'facetoface_managerselect');
    if ($managerselect && isset($fromform->managerid)) {
        $params['managerselect'] = $fromform->managerid;
    }

    $cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    if (!is_enrolled($context, $USER)) {
        // Check for and attempt to enrol via the shezar_facetoface enrolment plugin.
        $enrolments = enrol_get_plugins(true);
        $instances = enrol_get_instances($course->id, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'shezar_facetoface') {
                $data = clone($fromform);
                $data->sid = array($session->id);
                $enrolments[$instance->enrol]->enrol_shezar_facetoface($instance, $data, $course, $returnurl);
            }
        }
    }

    $result = facetoface_user_import($course, $facetoface, $session, $USER->id, $params);
    if ($result['result'] === true) {
        $signup = facetoface_get_attendee($session->id, $USER->id);
        $fromform->id = $signup->submissionid;
        customfield_save_data($fromform, 'facetofacesignup', 'facetoface_signup');

        switch ($facetoface->approvaltype) {
            case APPROVAL_NONE:
               $message = get_string('bookingcompleted', 'facetoface');
               $cssclass = 'notifymessage';
               break;
            case APPROVAL_SELF:
                $message = get_string('bookingcompleted', 'facetoface');
                $cssclass = 'notifymessage';
                break;
            case APPROVAL_ROLE:
                $rolenames = role_fix_names(get_all_roles());
                $rolename = $rolenames[$facetoface->approvalrole]->localname;
                $message = get_string('bookingcompleted_roleapprovalrequired', 'facetoface', $rolename);
                $cssclass = 'notifymessage';
                break;
            case APPROVAL_MANAGER:
                $message = get_string('bookingcompleted_approvalrequired', 'facetoface');
                $cssclass = 'notifymessage';
                break;
            case APPROVAL_ADMIN:
                $message = get_string('bookingcompleted_approvalrequired', 'facetoface');
                $cssclass = 'notifymessage';
                break;
            default:
                // TODO - this is unreachable now, we need to test this waitlisting.
                $strmessage = $signupbywaitlist ? 'joinwaitlistcompleted' : 'bookingcompleted';
                $message = get_string($strmessage, 'facetoface');
                $cssclass = 'notifysuccess';
                break;
        }

        if ($session->cntdates
            && isset($facetoface->confirmationinstrmngr)
            && !empty($facetoface->confirmationstrmngr)) {
            $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') .
                get_string('confirmationsentmgr', 'facetoface');
        } else {
            if ($fromform->notificationtype != MDL_F2F_NONE) {
                $message .= html_writer::empty_tag('br') . html_writer::empty_tag('br') .
                        get_string('confirmationsent', 'facetoface');
            }
        }

        shezar_set_notification($message, $returnurl, array('class' => $cssclass));
    } else {
        if ((isset($result['conflict']) && $result['conflict']) || isset($result['nogoodpos'])) {
            shezar_set_notification($result['result'], $returnurl);
        } else {
            print_error('error:problemsigningup', 'facetoface', $returnurl);
        }
    }

    redirect($returnurl);
}

echo $OUTPUT->header();

$strheading = $signupbywaitlist ? 'waitlistfor' : 'signupfor';
$heading = get_string($strheading, 'facetoface', $facetoface->name);

$viewattendees = has_capability('mod/facetoface:viewattendees', $context);
$multisessionid = ($facetoface->multiplesessions ? $session->id : null);
$signedup = facetoface_check_signup($facetoface->id, $multisessionid);
if ($signedup and $signedup != $session->id) {
    print_error('error:signedupinothersession', 'facetoface', $returnurl);
}

echo $OUTPUT->box_start();
echo $OUTPUT->heading($heading);

$timenow = time();

// Add booking information.
$session->bookedsession = null;
if ($bookedsession = facetoface_get_user_submissions($facetoface->id, $USER->id,
    MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_BOOKED, $session->id)) {
    $session->bookedsession = reset($bookedsession);
}

if ($session->cntdates && facetoface_has_session_started($session, $timenow)) {
    $inprogress_str = get_string('cannotsignupsessioninprogress', 'facetoface');
    $over_str = get_string('cannotsignupsessionover', 'facetoface');

    $errorstring = facetoface_is_session_in_progress($session, $timenow) ? $inprogress_str : $over_str;

    echo $OUTPUT->notification($errorstring, 'notifyproblem');
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer($course);
    exit();
}

if (!facetoface_get_attendee($session->id, $USER->id)
    && !facetoface_session_has_capacity($session, $context, MDL_F2F_STATUS_WAITLISTED)
    && !$session->allowoverbook) {
        echo $OUTPUT->notification(get_string('sessionisfull', 'facetoface'), 'notifyproblem');
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer($course);
        exit();
}

echo facetoface_print_session($session, $viewattendees, false, false, $signedup);

if ($signedup) {
    if (facetoface_allow_user_cancellation($session)) {
        // Cancellation link.
        $canceltext = facetoface_is_user_on_waitlist($session) ? get_string('cancelwaitlist', 'facetoface') : get_string('cancelbooking', 'facetoface');
        echo html_writer::link(new moodle_url('cancelsignup.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions)), $canceltext, array('title' => $canceltext));
        echo ' &ndash; ';
    }
    // See attendees link.
    if ($viewattendees) {
        echo html_writer::link(new moodle_url('attendees.php', array('s' => $session->id, 'backtoallsessions' => $backtoallsessions)), get_string('seeattendees', 'facetoface'), array('title' => get_string('seeattendees', 'facetoface')));
    }

    echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), array('title' => get_string('goback', 'facetoface')));
// Don't allow signup to proceed if a manager is required.
} else if (!has_capability('mod/facetoface:signup', $context) && !$candirectenrol) {
    echo html_writer::tag('p', html_writer::tag('strong', get_string('error:nopermissiontosignup', 'facetoface')));
    echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), array('title' => get_string('goback', 'facetoface')));
} else if ($facetoface->forceselectjobassignment && !boolval(\shezar_job\job_assignment::get_all($USER->id, facetoface_approval_required($facetoface)))) {
    echo html_writer::tag('p', html_writer::tag('strong', get_string('error:nojobassignmentselectedactivity', 'facetoface')));
    echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), array('title' => get_string('goback', 'facetoface')));
} else if (!empty($session->registrationtimestart) && ($session->registrationtimestart > time())) {
    $datetimetz = new stdClass();
    $datetimetz->date = userdate($session->registrationtimestart, get_string('strftimedate', 'langconfig'));
    $datetimetz->time = userdate($session->registrationtimestart,  get_string('strftimetime', 'langconfig'));
    $datetimetz->timezone = core_date::get_user_timezone();
    echo html_writer::span(get_string('signupregistrationnotyetopen', 'facetoface', $datetimetz));
} else if (!empty($session->registrationtimefinish) && ($session->registrationtimefinish < time())) {
    $datetimetz = new stdClass();
    $datetimetz->date = userdate($session->registrationtimefinish, get_string('strftimedate', 'langconfig'));
    $datetimetz->time = userdate($session->registrationtimefinish,  get_string('strftimetime', 'langconfig'));
    $datetimetz->timezone = core_date::get_user_timezone();
    echo html_writer::span(get_string('signupregistrationclosed', 'facetoface', $datetimetz));
} else if ($session->mintimestart and
           $dates = facetoface_get_session_dates($session->id) and
           $availability = facetoface_get_sessions_within($dates, $USER->id)) {
    // There are date conflicts with other session signups.
    $conflict = facetoface_get_session_involvement($USER, $availability);
    echo html_writer::tag('p', html_writer::tag('strong', $conflict));
    echo html_writer::empty_tag('br') . html_writer::link($returnurl, get_string('goback', 'facetoface'), array('title' => get_string('goback', 'facetoface')));
// If manager approval is required and no manager is defined, warn the user.
} else if (empty($session->managerids) && !get_config(null, 'facetoface_managerselect') && ($approvaltype == APPROVAL_MANAGER || $approvaltype == APPROVAL_ADMIN)) {
    echo $OUTPUT->notification(get_string('error:missingrequiredmanager', 'mod_facetoface'), 'notifyproblem');
} else {
    // Signup form.
    $mform->display();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
