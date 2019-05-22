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
 * @author Nathan Lewis <nathan.lewis@shezarlearning.com>
 * @package shezar_job
 */

// Display user position information
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot . '/shezar/job/lib.php');
require_once($CFG->dirroot . '/shezar/job/jobassignment_form.php');

// Get input parameters
$courseid = optional_param('course', SITEID, PARAM_INT);
$jobassignmentid = optional_param('jobassignmentid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Check logged in user can view this profile.
require_login($course);

if (empty($jobassignmentid)) {
    // If no job assignment id is provided then we must be adding a new job assignment for the specified user.
    $jobassignment = null;
    $userid = optional_param('userid', $USER->id, PARAM_INT);
    $currenturl = new moodle_url('/shezar/job/jobassignment.php', array('userid' => $userid));
} else {
    // Load the job assignment.
    $jobassignment = \shezar_job\job_assignment::get_with_id($jobassignmentid);
    $userid = $jobassignment->userid;
    $currenturl = new moodle_url('/shezar/job/jobassignment.php', array('jobassignmentid' => $jobassignmentid));
}

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:useridincorrect', 'shezar_core');
}

// Check permissions.
$coursecontext = context_course::instance($course->id);
$personalcontext = context_user::instance($user->id);
$canview = false;
if (!empty($USER->id) && ($user->id == $USER->id)) {
    // Can view own profile
    $canview = true;
} else if (has_capability('moodle/user:viewdetails', $coursecontext)) {
    $canview = true;
} else if (has_capability('moodle/user:viewdetails', $personalcontext)) {
    $canview = true;
}
if (!$canview) {
    print_error('cannotviewprofile');
}

// Is user deleted?
if ($user->deleted) {
    print_error('userdeleted', 'moodle');
}

if (empty($jobassignmentid) && empty($CFG->shezar_job_allowmultiplejobs) && \shezar_job\job_assignment::get_all($userid)) {
    // We're adding a new job assignment (as no jobassignment id provided), but multiple jobs is off
    // and the user already has one.
    print_error('error:onlyonejobassignmentallowed', 'shezar_job');
}

// Can user edit this user's job assignments?
$canedit = shezar_job_can_edit_job_assignments($user->id);

// Can user edit temp manager?
$canedittempmanager = false;
if (!empty($CFG->enabletempmanagers)) {
    if (has_capability('shezar/core:delegateusersmanager', $personalcontext)) {
        $canedittempmanager = true;
    } else if ($USER->id == $user->id && has_capability('shezar/core:delegateownmanager', $personalcontext)) {
        $canedittempmanager = true;
    }
}

$fullname = fullname($user, true);

if (empty($jobassignment)) {
   $pagetitle = get_string('jobassignmentadd', 'shezar_job');
} else {
   $pagetitle = $jobassignment->fullname;
}

$PAGE->set_url($currenturl);
$PAGE->set_context($coursecontext);

$PAGE->navbar->add(get_string('users'), new moodle_url('/admin/user.php'));
$PAGE->navbar->add($fullname, new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id)));
$PAGE->navbar->add($pagetitle, null);
$PAGE->set_title("{$course->fullname}: {$fullname}: {$pagetitle}");
$PAGE->set_heading("{$pagetitle}");

// Setup custom javascript.
local_js(array(
    shezar_JS_DIALOG,
    shezar_JS_TREEVIEW
));
$PAGE->requires->strings_for_js(array('chooseappraiser', 'choosemanager', 'chooseorganisation',
                                      'chooseposition', 'choosetempmanager', 'selected'), 'shezar_job');
$PAGE->requires->strings_for_js(array('error:appraisernotselected', 'error:managernotselected', 'error:organisationnotselected',
                                      'error:positionnotselected', 'error:tempmanagernotselected'), 'shezar_job');
$jsmodule = array(
        'name' => 'shezar_jobassignment',
        'fullpath' => '/shezar/job/js/jobassignment.js',
        'requires' => array('json'));
$jscanedit = $canedit ? 'true' : 'false';
$jscanedittempmanager = $canedittempmanager ? 'true' : 'false';
$selectedposition = json_encode(dialog_display_currently_selected(get_string('selected', 'shezar_job'), 'position'));
$selectedorganisation = json_encode(dialog_display_currently_selected(get_string('selected', 'shezar_job'), 'organisation'));
$selectedmanager = json_encode(dialog_display_currently_selected(get_string('selected', 'shezar_job'), 'manager'));
$selectedtempmanager = json_encode(dialog_display_currently_selected(get_string('selected', 'shezar_job'), 'tempmanager'));
$selectedappraiser = json_encode(dialog_display_currently_selected(get_string('selected', 'shezar_job'), 'appraiser'));
$args = array('args'=>'{"userid":'.$user->id.','.
        '"can_edit":'.$jscanedit.','.
        '"can_edit_tempmanager":'.$jscanedittempmanager.','.
        '"dialog_display_position":'.$selectedposition.','.
        '"dialog_display_organisation":'.$selectedorganisation.','.
        '"dialog_display_manager":'.$selectedmanager.','.
        '"dialog_display_tempmanager":'.$selectedtempmanager.','.
        '"dialog_display_appraiser":'.$selectedappraiser.'}');
$PAGE->requires->js_init_call('M.shezar_jobassignment.init', $args, false, $jsmodule);

$PAGE->set_pagelayout('course');

// Form.
$submitbutton = optional_param('submitbutton', null, PARAM_ALPHANUMEXT);
$submitted = !empty($submitbutton);
$submittedpositionid = optional_param('positionid', null, PARAM_INT);
$submittedorganisationid = optional_param('organisationid', null, PARAM_INT);
$submittedmanagerid = optional_param('managerid', null, PARAM_INT);
$submittedmanagerjaid = optional_param('managerjaid', null, PARAM_INT);
$submittedappraiserid = optional_param('appraiserid', null, PARAM_INT);
$submittedtempmanagerid = optional_param('tempmanagerid', null, PARAM_INT);
$submittedtempmanagerjaid = optional_param('tempmanagerjaid', null, PARAM_INT);

$editoroptions = array('subdirs' => true, 'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'context' => $personalcontext);

$form = new job_assignment_form($currenturl, compact('jobassignment', 'canedit',
        'editoroptions', 'canedittempmanager', 'submitted', 'submittedpositionid', 'submittedorganisationid',
        'submittedmanagerid', 'submittedmanagerjaid', 'submittedappraiserid', 'submittedtempmanagerid',
        'submittedtempmanagerjaid', 'userid'
    ));
if ($jobassignment) {
    $form->set_data($jobassignment->get_data());
}

// Don't show the page if they do not have a position & can't edit positions.
if (!$canedit && empty($jobassignment) && !$canedittempmanager) {
    throw new exception('Cannot create job assignment for this user');
} else if ($form->is_cancelled()) {
    // Redirect to user profile page.
    redirect(new moodle_url('/user/profile.php', array('id' => $user->id)));
}

if ($submitted = $form->get_data()) {
    $data = new stdClass();

    $data->idnumber = $submitted->idnumber;

    if (isset($submitted->fullname) && $submitted->fullname !== "") {
        if ($jobassignment && $submitted->fullname == get_string('jobassignmentdefaultfullname', 'shezar_job', $jobassignment->idnumber)) {
            $data->fullname = null;
        } else {
            $data->fullname = $submitted->fullname;
        }
    } else {
        $data->fullname = null;
    }

    if (isset($submitted->shortname) && $submitted->shortname !== "") {
        $data->shortname = $submitted->shortname;
    } else {
        $data->shortname = null;
    }

    if (!empty($submitted->description_editor) && !empty($submitted->description_editor)) {
        $data->description_editor = $submitted->description_editor;
    } else {
        $data->description_editor = null;
    }

    if (!empty($submitted->positionid) && $submitted->positionid > 0) {
        $data->positionid = $submitted->positionid;
    } else {
        $data->positionid = null;
    }

    if (!empty($submitted->organisationid) && $submitted->organisationid > 0) {
        $data->organisationid = $submitted->organisationid;
    } else {
        $data->organisationid = null;
    }

    if (!empty($submitted->startdate) && $submitted->startdate > 0) {
        $data->startdate = $submitted->startdate;
    } else {
        $data->startdate = null;
    }

    if (!empty($submitted->enddate) && $submitted->enddate > 0) {
        $data->enddate = $submitted->enddate;
    } else {
        $data->enddate = null;
    }

    // Get manager.
    $data->managerjaid = null;
    if (!empty($submitted->managerid) && $submitted->managerid > 0) {
        // If there is a manager assigned, check it is valid.
        if ($submitted->managerid == $userid) {
            print_error('error:userownmanager', 'shezar_job');
        }
        $validmanager = $DB->get_record('user', array('id' => $submitted->managerid), 'username, deleted');
        if ($validmanager && $validmanager->deleted != 0) {
            $a = new stdClass();
            $a->username = $validmanager->username;
            shezar_set_notification(get_string('error:managerdeleted', 'shezar_job', $a), null, array('class' => 'notifynotice'));
        } else {
            // Check the job assignment.
            if (!empty($submitted->managerjaid)) {
                $managerja = \shezar_job\job_assignment::get_with_id($submitted->managerjaid);
                if ($managerja->userid != $submitted->managerid) {
                    throw new exception('Manager job assignment does not match manager - code error');
                }
            } else {
                if ($canedit) {
                    // The manager has no job assignment, so create a default one.
                    $managerja = \shezar_job\job_assignment::create_default($submitted->managerid);
                } else {
                    // In all but very exceptional cases, a user without edit permissions should not get to this stage.
                    throw new coding_exception('No manager job assignment supplied.');
                }
            }
            $data->managerjaid = $managerja->id;
        }
    }

    // Get temp manager.
    $data->tempmanagerjaid = null;
    if (!empty($submitted->tempmanagerid) && $submitted->tempmanagerid > 0) {
        // If there is a temp manager assigned, check it is valid.
        if ($submitted->tempmanagerid == $userid) {
            print_error('error:userownmanager', 'shezar_job');
        }
        $validtempmanager = $DB->get_record('user', array('id' => $submitted->tempmanagerid), 'username, deleted');
        if ($validtempmanager && $validtempmanager->deleted != 0) {
            $a = new stdClass();
            $a->username = $validtempmanager->username;
            shezar_set_notification(get_string('error:managerdeleted', 'shezar_job', $a), null, array('class' => 'notifynotice'));
        } else {
            // Check the job assignment.
            if (!empty($submitted->tempmanagerjaid)) {
                $tempmanagerja = \shezar_job\job_assignment::get_with_id($submitted->tempmanagerjaid);
                if ($tempmanagerja->userid != $submitted->tempmanagerid) {
                    throw new exception('Temp manager job assignment does not match temp manager - code error');
                }
            } else {
                if ($canedittempmanager) {
                    // The temp manager has no job assignment, so create a default one.
                    $tempmanagerja = \shezar_job\job_assignment::create_default($submitted->tempmanagerid);
                } else {
                    // In all but very exceptional cases, a user without edit permissions should not get to this stage.
                    throw new coding_exception('No manager job assignment supplied.');
                }
            }
            $data->tempmanagerjaid = $tempmanagerja->id;
        }
        $data->tempmanagerexpirydate = $submitted->tempmanagerexpirydate;
    }

    // Get appraiser id.
    $data->appraiserid = null;
    if (isset($submitted->appraiserid) && $submitted->appraiserid > 0) {
        // If there is a appraiser assigned, check appraiser is valid.
        if ($submitted->appraiserid == $userid) {
            print_error('error:userownappraiser', 'shezar_hierarchy');
        }
        $validappraiser = $DB->get_record('user', array('id' => $submitted->appraiserid), 'username, deleted');
        if ($validappraiser && $validappraiser->deleted != 0) {
            $a = new stdClass();
            $a->username = $validappraiser->username;
            shezar_set_notification(get_string('error:appraiserdeleted','shezar_hierarchy', $a), null, array('class' => 'notifynotice'));
        } else {
            $data->appraiserid = $submitted->appraiserid;
        }
    }

    if ($jobassignment) {
        $jobassignment->update($data);
    } else {
        $data->userid = $userid;
        $jobassignment = \shezar_job\job_assignment::create($data);
    }

    // Display success message
    shezar_set_notification(get_string('jobassignmentsaved','shezar_job'),
        new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $course->id)),
        array('class' => 'notifysuccess'));

}

// Log
if ($jobassignment) {
    \shezar_job\event\job_assignment_viewed::create_from_instance($jobassignment, $coursecontext)->trigger();
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
