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
 * @author Nathan Lewis <nathan.lewis@shezarlms.com>
 * @package shezar_certification
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/shezar/certification/lib.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot . '/shezar/certification/edit_completion_form.php');

// Check if certifications are enabled.
check_certification_enabled();

if (empty($CFG->enableprogramcompletioneditor)) {
    print_error('error:completioneditornotenabled', 'shezar_program');
}

$id = required_param('id', PARAM_INT); // Program id.
$userid = required_param('userid', PARAM_INT);

require_login();

$program = new program($id);
$programcontext = $program->get_context();

require_capability('shezar/program:editcompletion', $programcontext);

$certification = $DB->get_record('certif', array('id' => $program->certifid));
if (!$certification) {
    print_error(get_string('nocertifdetailsfound', 'shezar_certification'));
}

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

$url = new moodle_url('/shezar/certification/edit_completion.php', array('id' => $id, 'userid' => $userid));
$PAGE->set_context($programcontext);

if ($dismissedexceptions = $program->check_user_for_dismissed_exceptions($userid)) {
    $resetexception = optional_param('resetexception', 0, PARAM_INT);

    if ($resetexception) {
        // Remove the exception status on the user assignment.
        $exmanager = new prog_exceptions_manager($id);
        $exmanager->override_dismissed_exception($userid);

        $urlparams = array('id' => $id, 'userid' => $userid);
        $redirecturl = new moodle_url('/shezar/certification/edit_completion.php', $urlparams);

        shezar_set_notification(get_string('exceptionoverridden', 'shezar_program'), $redirecturl, array('class' => 'notifysuccess'));
    }
}

// Process delete history.
$deletehistory = optional_param('deletehistory', 0, PARAM_INT);
if ($deletehistory && !$dismissedexceptions) {
    $chid = required_param('chid', PARAM_INT);

    // Validate that the record to be deleted matches the certification and user.
    $params = array('id' => $chid, 'certifid' => $program->certifid, 'userid' => $userid);
    if (!$DB->record_exists('certif_completion_history', $params)) {
        shezar_set_notification(get_string('error:impossibledatasubmitted', 'shezar_program'),
            $url,
            array('class' => 'notifyproblem'));
    }

    certif_delete_completion_history($chid, 'Completion history manually deleted');

    shezar_set_notification(get_string('completionhistorydeleted', 'shezar_program'),
        $url,
        array('class' => 'notifysuccess'));
}

// Load all the data about the user and certification.
list($certcompletion, $progcompletion) = certif_load_completion($id, $userid, false);
$exceptions = $DB->get_records('prog_exception', array('programid' => $id, 'userid' => $userid));

if ($certcompletion && $progcompletion && empty($exceptions) && !$dismissedexceptions) {
    $currentformdata = new stdClass();
    $currentformdata->state = certif_get_completion_state($certcompletion);
    $currentformdata->inprogress = ($certcompletion->status == CERTIFSTATUS_INPROGRESS) ? 1 : 0;
    $currentformdata->status = $certcompletion->status;
    $currentformdata->renewalstatus = $certcompletion->renewalstatus;
    $currentformdata->certifpath = $certcompletion->certifpath;
    // Fix stupid timedue should be -1 for not set problem.
    $currentformdata->timeduenotset = ($progcompletion->timedue == COMPLETION_TIME_NOT_SET) ? 'yes' : 'no';
    $currentformdata->timedue = ($progcompletion->timedue == COMPLETION_TIME_NOT_SET) ? 0 : $progcompletion->timedue;
    $currentformdata->timecompleted = $certcompletion->timecompleted;
    $currentformdata->timewindowopens = $certcompletion->timewindowopens;
    $currentformdata->timeexpires = $certcompletion->timeexpires;
    $currentformdata->progstatus = $progcompletion->status;
    $currentformdata->progtimecompleted = $progcompletion->timecompleted;

    // Prepare the form.
    $errors = certif_get_completion_errors($certcompletion, $progcompletion);
    $problemkey = certif_get_completion_error_problemkey($errors);
    $editformcustomdata = array(
        'id' => $id,
        'userid' => $userid,
        'showinitialstateinvalid' => (($currentformdata->state == CERTIFCOMPLETIONSTATE_INVALID) || !empty($errors)),
        'certification' => $certification,
        'originalstate' => $currentformdata->state,
        'status' => $progcompletion->status,
        'solution' => certif_get_completion_error_solution($problemkey, $id, $userid, true),
    );
    $editform = new certif_edit_completion_form($url, $editformcustomdata, 'post', '', array('id' => 'form_certif_completion'));

    // Process any actions submitted.
    if ($editform->is_cancelled()) {
        shezar_set_notification(get_string('completionupdatecancelled', 'shezar_program'), $url,
            array('class' => 'notifysuccess'));
    }

    $confirm = "";

    if ($submitted = $editform->get_data()) {
        // Validate the form and display any problems.
        if ($submitted->state == CERTIFCOMPLETIONSTATE_INVALID) {
            shezar_set_notification(get_string('error:impossibledatasubmitted', 'shezar_program'),
                $url,
                array('class' => 'notifyproblem'));
        }

        list($newcertcompletion, $newprogcompletion) = certif_process_submitted_edit_completion($submitted);
        $newstate = certif_get_completion_state($newcertcompletion);
        $errors = certif_get_completion_errors($newcertcompletion, $newprogcompletion);

        if ($newstate == CERTIFCOMPLETIONSTATE_INVALID || !empty($errors)) {
            shezar_set_notification(get_string('error:impossibledatasubmitted', 'shezar_program'),
                $url,
                array('class' => 'notifyproblem'));
        }

        if (!empty($submitted->savechanges)) {
            // The user is trying to submit changes. Show the warning and confirmation.
            $data = new stdClass();
            $data->originalstate = $currentformdata->state;
            $data->newstate = $submitted->state;
            $data->newcertcompletion = $newcertcompletion;

            list($data->userresults, $data->cronresults) =
                certif_get_completion_change_consequences($data->originalstate, $data->newstate, $data->newcertcompletion);

            $renderer = $PAGE->get_renderer('shezar_certification');
            $confirm = $renderer->get_save_completion_confirmation($data);

            // Recreate the form, this time with the confirmation stuff.
            $editformcustomdata['showconfirm'] = true;
            $editform = new certif_edit_completion_form($url, $editformcustomdata,
                'post', '', array('id' => 'form_certif_completion'));

        } else if (!empty($submitted->confirmsave)) {
            // The user has clicked the confirm button, so save to db.
            if (certif_write_completion($newcertcompletion, $newprogcompletion, 'Completion manually edited')) {
                if ($currentformdata->state == CERTIFCOMPLETIONSTATE_CERTIFIED && $newstate != CERTIFCOMPLETIONSTATE_CERTIFIED) {
                    prog_reset_course_set_completions($id, $userid);
                }
                shezar_set_notification(get_string('completionchangessaved', 'shezar_program'),
                    $url,
                    array('class' => 'notifysuccess'));
            } else {
                shezar_set_notification(get_string('error:impossibledatasubmitted', 'shezar_program'),
                    $url,
                    array('class' => 'notifyproblem'));
            }
        }
    }

    // Init form core js before certification.
    $args = $editform->_form->getLockOptionObject();
    if (count($args[1]) > 0) {
        $PAGE->requires->js_init_call('M.form.initFormDependencies', $args, false, moodleform::get_js_module());
    }
}

// Masquerade as the completion page for the sake of navigation.
$PAGE->navigation->override_active_url(new moodle_url('/shezar/program/completion.php', array('id' => $id)));
// Add an item to the navbar to make it unique.
$PAGE->navbar->add(get_string('editcompletion', 'shezar_program'));

// Set up the page.
$PAGE->set_url($url);
$PAGE->set_title($program->fullname);
$PAGE->set_heading($program->fullname);

// Display.
$heading = get_string('completionsforuserinprog', 'shezar_program',
    array('user' => fullname($user), 'prog' => format_string($program->fullname)));

// Javascript includes.
$jsmodule = array(
    'name' => 'shezar_editcertcompletion',
    'fullpath' => '/shezar/certification/edit_completion.js');
$PAGE->requires->js_init_call('M.shezar_editcertcompletion.init', array(), false, $jsmodule);
$PAGE->requires->strings_for_js(
    array('notapplicable', 'perioddays', 'periodweeks', 'periodmonths', 'periodyears'), 'shezar_certification');
$PAGE->requires->strings_for_js(
    array('bestguess', 'confirmdeletecompletion'), 'shezar_program');

$PAGE->requires->strings_for_js(array('fixconfirmone', 'fixconfirmtitle'), 'shezar_program');
$PAGE->requires->js_call_amd('shezar_program/check_completion', 'init');

echo $OUTPUT->header();
echo $OUTPUT->container_start('editcompletion');
echo $OUTPUT->heading($heading);

$completionurl = new moodle_url('/shezar/program/completion.php', array('id' => $id));
echo html_writer::tag('ul', html_writer::tag('li', html_writer::link($completionurl,
    get_string('completionreturntocertification', 'shezar_certification'))));

// Display if and how this user is assigned, or otherwise why they might have the completion record.
echo $OUTPUT->notification($program->display_completion_record_reason($user, $progcompletion), 'notifymessage');

// Display the edit completion record form.
if (isset($editform)) {
    echo $confirm;
    $editform->set_data($currentformdata);
    $editform->validate_defined_fields(true);
    $editform->display();
} else if ($dismissedexceptions) {
    $urlparams = array('id' => $id, 'userid' => $userid, 'resetexception' => 1);
    $exceptionurl = new moodle_url('/shezar/certification/edit_completion.php', $urlparams);

    echo $OUTPUT->notification(get_string('userhasdismissedexception', 'shezar_program'), 'notifymessage');
    echo $OUTPUT->single_button($exceptionurl, get_string('overrideandassign', 'shezar_program'));
} else if (!empty($exceptions)) {
    echo $OUTPUT->notification(get_string('fixexceptionbeforeeditingcompletion', 'shezar_program'), 'notifyproblem');
}

// Display the completion history and transactions.
if (!isset($editform) || empty($confirm)) {
    $history = $DB->get_records('certif_completion_history',
        array('userid' => $userid, 'certifid' => $program->certifid), 'timecompleted DESC');

    $sql = "SELECT pcl.id, pcl.timemodified, pcl.changeuserid, pcl.description, " . get_all_user_name_fields(true, 'usr') . "
          FROM {prog_completion_log} pcl
          LEFT JOIN {user} usr ON usr.id = pcl.changeuserid
         WHERE (pcl.userid = :userid OR pcl.userid IS NULL) AND pcl.programid = :programid
         ORDER BY pcl.id DESC";
    $transactions = $DB->get_records_sql($sql, array('userid' => $userid, 'programid' => $id));

    foreach ($history as $certcomplhistory) {
        $certcomplhistory->state = certif_get_completion_state($certcomplhistory);
        $certcomplhistory->errors = certif_get_completion_errors($certcomplhistory, null);
    }

    $historyformcustomdata = array(
        'id' => $id,
        'userid' => $userid,
        'history' => $history,
        'transactions' => $transactions,
    );
    $historyurl = new moodle_url('/shezar/certification/edit_completion_history.php', array('id' => $id, 'userid' => $userid));
    $historyform = new certif_edit_completion_history_and_transactions_form($historyurl, $historyformcustomdata,
        'post', '', array('id' => 'form_certif_completion_history_and_transactions'));
    $historyform->display();
}

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
