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
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/attendees/forms.php');

// The number of users that should be shown per page.
define('USERS_PER_PAGE', 50);

$s      = required_param('s', PARAM_INT); // facetoface session ID
$listid = required_param('listid', PARAM_ALPHANUM); // Session key to list of users to add.
$page   = optional_param('page', 0, PARAM_INT); // Current page number.
$ignoreconflicts = optional_param('ignoreconflicts', false, PARAM_BOOL); // Ignore scheduling conflicts.

list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);

// Check essential permissions.
require_login($course, false, $cm);
require_capability('mod/facetoface:addattendees', $context);

$currenturl = new moodle_url('/mod/facetoface/attendees/addconfirm.php', array('s' => $s, 'listid' => $listid, 'page' => $page));
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->requires->js_call_amd('mod_facetoface/attendees_addconfirm', 'init', array(array('s' => $s, 'listid' => $listid)));

$list = new \mod_facetoface\bulk_list($listid);

// Selected users.
$userlist = $list->get_user_ids();
if (empty($userlist)) {
    shezar_set_notification(get_string('updateattendeesunsuccessful', 'facetoface'),
            new moodle_url('/mod/facetoface/attendees.php', array('s' => $s, 'backtoallsessions' => 1)));
}

$approvalreqd = facetoface_approval_required($facetoface);
$mform = new addconfirm_form(null, array('s' => $s, 'listid' => $listid, 'approvalreqd' => $approvalreqd,
        'enablecustomfields' => !$list->has_user_data(), 'ignoreconflicts' => $ignoreconflicts));

$returnurl = new moodle_url('/mod/facetoface/attendees.php', array('s' => $s, 'backtoallsessions' => 1));
if ($mform->is_cancelled()) {
    $list->clean();
    redirect($returnurl);
}

// Get users waiting approval to add to the "already attending" list as we do not want to add them again.
$waitingapproval = facetoface_get_requests($session->id);

if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    if (empty($_SESSION['f2f-bulk-results'])) {
        $_SESSION['f2f-bulk-results'] = array();
    }

    $added  = array();
    $errors = array();
    // Original booked attendees plus those awaiting approval
    if ($session->cntdates) {
        $original = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW,
            MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED));
    } else {
        $original = facetoface_get_attendees($session->id, array(MDL_F2F_STATUS_WAITLISTED, MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_NO_SHOW,
            MDL_F2F_STATUS_PARTIALLY_ATTENDED, MDL_F2F_STATUS_FULLY_ATTENDED));
    }

    $approvalrequired = !empty($fromform->ignoreapproval) ? APPROVAL_NONE  : $facetoface->approvaltype;

    // Add those awaiting approval
    foreach ($waitingapproval as $waiting) {
        if (!isset($original[$waiting->id])) {
            $original[$waiting->id] = $waiting;
        }
    }

    // Adding new attendees.
    // Check if we need to add anyone.
    $users = $mform->get_user_list($userlist);
    $attendeestoadd = array_diff_key($users, $original);

    // Confirm that new attendess have job assignments when required.
    if (!empty($facetoface->forceselectjobassignment)) {
        foreach ($attendeestoadd as $attendeetoadd) {
            $userdata = $list->get_user_data($attendeetoadd->id);
            if (empty($userdata['jobassignmentid'])) {
                shezar_set_notification(get_string('error:nojobassignmentselectedlist', 'facetoface'), $currenturl);
            }
        }
    }

    if (!empty($attendeestoadd)) {
        // Prepare params
        $params = array();
        $params['suppressemail'] = !$fromform->notifyuser;
        // If we selected ignore approval then change the status.
        $params['approvalreqd'] = $approvalrequired;
        // If approval is required then we need to send a request to their manager.
        if ($approvalrequired) {
            $params['ccmanager'] = 1;
        } else {
            $params['ccmanager'] = $fromform->notifymanager;
        }
        $params['ignoreconflicts'] = $ignoreconflicts;

        $clonefromform = serialize($fromform);
        foreach ($attendeestoadd as $attendee) {
            // Add job assignments if they are enabled.
            $params['jobassignmentid'] = null;
            if ($facetoface->selectjobassignmentonsignup) {
                $userdata = $list->get_user_data($attendee->id);
                if (!empty($userdata['jobassignmentid'])) {
                    $params['jobassignmentid'] = $userdata['jobassignmentid'];
                }
            }

            $result = facetoface_user_import($course, $facetoface, $session, $attendee->id, $params);
            if ($result['result'] !== true) {
                $errors[] = $result;
                continue;
            } else {
                $result['result'] = get_string('addedsuccessfully', 'facetoface');
                $added[] = $result;
            }

            // Store customfields.
            $signupstatus = facetoface_get_attendee($session->id, $attendee->id);
            $customdata = $list->has_user_data() ? (object)$list->get_user_data($attendee->id) : $fromform;
            $customdata->id = $signupstatus->submissionid;
            customfield_save_data($customdata, 'facetofacesignup', 'facetoface_signup');
            // Values of multi-select are changing after edit_save_data func.
            $fromform = unserialize($clonefromform);
        }
    }

    // Log that users were edited.
    if (count($added) > 0 || count($errors) > 0) {
        \mod_facetoface\event\attendees_updated::create_from_session($session, $context)->trigger();
    }
    $_SESSION['f2f-bulk-results'][$session->id] = array($added, $errors);

    facetoface_set_bulk_result_notification(array($added, $errors));
    $numattendees = facetoface_get_num_attendees($session->id);
    $overbooked = ($numattendees > $session->capacity);
    if ($overbooked) {
        $overbookedmessage = get_string('capacityoverbookedlong', 'facetoface', array('current' => $numattendees, 'maximum' => $session->capacity));
        shezar_set_notification($overbookedmessage, null, array('class' => 'notifynotice'));
    }

    $list->clean();
    redirect(new moodle_url('/mod/facetoface/attendees.php', array('s' => $s, 'backtoallsessions' => 1)));
}

$PAGE->set_title(format_string($facetoface->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addattendeestep2', 'facetoface'));
echo facetoface_print_session($session, false, true, true);

// Table.
$f2frenderer = $PAGE->get_renderer('mod_facetoface');
$f2frenderer->setcontext($context);

$users = $mform->get_user_list($userlist, $page, USERS_PER_PAGE);
$paging = new paging_bar(count($userlist), $page, USERS_PER_PAGE, $currenturl);

$jaselector = 0;
if (!empty($facetoface->forceselectjobassignment)) {
    $jaselector = 2;
} else if (!empty($facetoface->selectjobassignmentonsignup)) {
    $jaselector = 1;
}

echo $f2frenderer->render($paging);
echo $f2frenderer->print_userlist_table($users, $list, $session->id, $jaselector);
echo $f2frenderer->render($paging);

echo html_writer::empty_tag('br');

$returnurl = $list->get_returnurl();
echo html_writer::link($returnurl, get_string('changeselectedusers', 'facetoface'), array('class'=>'link-as-button'));
$mform->display();

echo $OUTPUT->footer();
