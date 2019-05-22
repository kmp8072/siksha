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
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package mod_facetoface
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('session_forms.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$f = optional_param('f', 0, PARAM_INT); // facetoface Module ID
$s = optional_param('s', 0, PARAM_INT); // facetoface session ID
$c = optional_param('c', 0, PARAM_INT); // copy session
$d = optional_param('d', 0, PARAM_INT); // delete session
$confirm = optional_param('confirm', false, PARAM_BOOL); // delete confirmation
$cntdates = optional_param('cntdates', 0, PARAM_INT); // Number of events to set.
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

$session = null;

// This file requires the following:
//
// * A session id ($s) in which case editing is the default.
//   * AND If $c is also not empty then we are copying the event as a new event.
//   * OR If $d is also not empty then we are deleting the event.
// * A course module id ($id) in which case we are adding a new event to the given instance.
// * OR a facetoface instance id ($f) in which case we are adding a new event to the given instance.
//
// All of these variables are normalised after the related objects have been retrieved.
// Because of this we will work out the proper heading for the page now.
$actionheading = 'addingsession';
if ($s) {
    $actionheading = 'editingsession';
    if ($d) {
        $actionheading = 'deletingsession';
    } else if ($c) {
        $actionheading = 'copyingsession';
    }
}

// Offer one date for new sessions by default.
if (!$s && $cntdates < 1) {
    $cntdates = 1;
}

if ($s) {
    list($session, $facetoface, $course, $cm, $context) = facetoface_get_env_session($s);
    $s = $session->id;
} else if ($id) {
    if (!$cm = get_coursemodule_from_id('facetoface', $id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$facetoface = $DB->get_record('facetoface',array('id' => $cm->instance))) {
        print_error('error:incorrectcoursemodule', 'facetoface');
    }
} else {
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $f))) {
        print_error('error:incorrectfacetofaceid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
}
$context = context_module::instance($cm->id);
$f = $facetoface->id;
$id = $cm->id;

require_login($course, false, $cm);
require_capability('mod/facetoface:editevents', $context);

$errorstr = '';

local_js(array(
    shezar_JS_DIALOG,
    shezar_JS_TREEVIEW
));
$PAGE->set_url('/mod/facetoface/sessions.php', array('f' => $f, 'backtoallsessions' => $backtoallsessions));
$PAGE->requires->strings_for_js(array('save', 'delete'), 'shezar_core');
$PAGE->requires->strings_for_js(array('cancel', 'ok', 'edit', 'loadinghelp'), 'moodle');
$PAGE->requires->strings_for_js(array('chooseassets', 'chooseroom', 'dateselect', 'useroomcapacity', 'nodatesyet',
    'createnewasset', 'editasset', 'createnewroom', 'editroom'), 'facetoface');
$PAGE->set_title($facetoface->name);
$PAGE->set_heading($course->fullname);

$jsconfig = array('sessionid' => $s, 'can_edit' => 'true', 'facetofaceid' => $facetoface->id);
if (!empty($session)) {
    $cntdates = max($cntdates, $session->cntdates);
}

for ($offset = 0; $offset < $cntdates; $offset++) {
    $display_selected = dialog_display_currently_selected(get_string('selected', 'facetoface'), "selectroom{$offset}-dialog");
    $jsconfig['display_selected_item' . $offset] = $display_selected;
}

$jsmodule = array(
    'name' => 'shezar_f2f_room',
    'fullpath' => '/mod/facetoface/sessions.js',
    'requires' => array('json', 'shezar_core'));
$PAGE->requires->js_init_call('M.shezar_f2f_room.init', array($jsconfig), false, $jsmodule);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

// Handle deletions, note that cancelled events must be deletable too.
if ($session and $d) {
    if (!$confirm) {
        echo $OUTPUT->header();

        echo $OUTPUT->heading(get_string($actionheading, 'facetoface', format_string($facetoface->name)));

        $viewattendees = has_capability('mod/facetoface:viewattendees', $context);

        echo facetoface_print_session($session, $viewattendees);

        $optionsyes = array('sesskey' => sesskey(), 's' => $session->id, 'd' => 1, 'confirm' => 1, 'backtoallsessions' => $backtoallsessions);
        echo $OUTPUT->confirm(get_string('deletesessionconfirm', 'facetoface', format_string($facetoface->name)),
            new moodle_url('sessions.php', $optionsyes),
            new moodle_url($returnurl));

        echo $OUTPUT->footer();
        die;
    }

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    if (facetoface_delete_session($session)) {
        \mod_facetoface\event\session_deleted::create_from_session($session, $context)->trigger();
        redirect($returnurl);
    }
    print_error('error:couldnotdeletesession', 'facetoface', $returnurl);
}

if (!empty($session->cancelledstatus) && !$c) {
    print_error('error:cannoteditcancelledevent', 'facetoface', $returnurl);
}

$editoroptions = array(
    'noclean'  => false,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'context'  => $context,
);

$sessionid = isset($session->id) ? $session->id : 0;

$canconfigurecancellation = has_capability('mod/facetoface:configurecancellation', $context);

$defaulttimezone = '99';

if (!isset($session)) {
    $sessiondata = new stdClass();
    $sessiondata->id = 0;
    $sessiondata->allowcancellations = $facetoface->allowcancellationsdefault;
    $sessiondata->cancellationcutoff = $facetoface->cancellationscutoffdefault;
    $sessiondata->cntdates = $cntdates;
    $nbdays = 1;
} else {
    if (!empty($session->sessiondates[0]->sessiontimezone) and $session->sessiondates[0]->sessiontimezone != '99') {
        $defaulttimezone = core_date::normalise_timezone($session->sessiondates[0]->sessiontimezone);
    }
    // Load custom fields data for the session.
    customfield_load_data($session, 'facetofacesession', 'facetoface_session');

    // Set values for the form and unset some values that will be evaluated later.
    $sessiondata = clone($session);
    if (isset($sessiondata->sessiondates)) {
        unset($sessiondata->sessiondates);
    }

    $sessiondata->detailsformat = FORMAT_HTML;
    $editoroptions = $TEXTAREA_OPTIONS;
    $editoroptions['context'] = $context;
    $sessiondata = file_prepare_standard_editor($sessiondata, 'details', $editoroptions, $editoroptions['context'],
        'mod_facetoface', 'session', $session->id);

    // Let form know how many dates to process.
    if ($cntdates > $sessiondata->cntdates) {
        $sessiondata->cntdates = $cntdates;
    }

    $nbdays = count($session->sessiondates);
    if ($session->sessiondates) {
        $i = 0;
        foreach ($session->sessiondates as $date) {
            $idfield = "sessiondateid[$i]";
            $timestartfield = "timestart[$i]";
            $timefinishfield = "timefinish[$i]";
            $timezonefield = "sessiontimezone[$i]";
            $roomidfield = "roomid[$i]";
            $assetsfield = "assetids[$i]";

            if ($date->sessiontimezone === '') {
                $date->sessiontimezone = '99';
            } else if ($date->sessiontimezone != 99) {
                $date->sessiontimezone = core_date::normalise_timezone($date->sessiontimezone);
            }

            $sessiondata->$idfield = $date->id;
            $sessiondata->$timestartfield = $date->timestart;
            $sessiondata->$timefinishfield = $date->timefinish;
            $sessiondata->$timezonefield = $date->sessiontimezone;
            $sessiondata->$roomidfield = $date->roomid;
            $sessiondata->$assetsfield = $date->assetids;

            // NOTE: There is no need to remove rooms and assets
            //       because form validation will not allow saving
            //       and likely they will just change the date.

            $i++;
        }
    }
}

$mform = new mod_facetoface_session_form(null, compact('id', 'f', 's', 'c', 'session', 'nbdays', 'course', 'editoroptions', 'defaulttimezone', 'facetoface', 'cm', 'sessiondata', 'backtoallsessions'));
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { // Form submitted
    // Make sure user cannot cancel this page request. (Back luck IIS users!)
    ignore_user_abort();

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'facetoface', $returnurl);
    }

    // Pre-process fields
    if (empty($fromform->allowoverbook)) {
        $fromform->allowoverbook = 0;
    }
    if (empty($fromform->waitlisteveryone)) {
        $fromform->waitlisteveryone = 0;
    }
    if (empty($fromform->normalcost)) {
        $fromform->normalcost = 0;
    }
    if (empty($fromform->discountcost)) {
        $fromform->discountcost = 0;
    }
    if (empty($fromform->selfapproval)) {
        $fromform->selfapproval = 0;
    }

    //check dates and calculate total duration
    $sessiondates = array();
    for ($i = 0; $i < $fromform->cntdates; $i++) {
        if (!empty($fromform->datedelete[$i])) {
            continue; // skip this date
        }
        if (!empty($fromform->timestart[$i]) && !empty($fromform->timefinish[$i])) {
            $date = new stdClass();
            $date->sessiontimezone = $fromform->sessiontimezone[$i];
            $date->timestart = $fromform->timestart[$i];
            $date->timefinish = $fromform->timefinish[$i];
            $date->roomid = $fromform->roomid[$i];
            $date->assetids = !empty($fromform->assetids[$i]) ? explode(',', $fromform->assetids[$i]) : array();
            $sessiondates[] = $date;
        }
    }

    $todb = new stdClass();
    $todb->facetoface = $facetoface->id;
    $todb->capacity = $fromform->capacity;
    $todb->allowoverbook = $fromform->allowoverbook;
    $todb->waitlisteveryone = $fromform->waitlisteveryone;
    $todb->normalcost = $fromform->normalcost;
    $todb->discountcost = $fromform->discountcost;
    $todb->usermodified = $USER->id;

    // Sign-Up fields added.
    $todb->registrationtimestart = $fromform->registrationtimestart;
    $todb->registrationtimefinish = $fromform->registrationtimefinish;

    // If min capacity is not provided or unset default to 0.
    if ($fromform->mincapacity < 0) {
        $fromform->mincapacity = 0;
    }

    // If sendcapacityemail is empty default to 0
    if (empty($fromform->sendcapacityemail)) {
        $fromform->sendcapacityemail = 0;
    }

    // Do not change cancellation here!
    unset($fromform->cancelledstatus);

    $todb->mincapacity = $fromform->mincapacity;
    $todb->sendcapacityemail = $fromform->sendcapacityemail;
    $todb->cutoff = $fromform->cutoff;

    if ($canconfigurecancellation) {
        $todb->allowcancellations = $fromform->allowcancellations;
        $todb->cancellationcutoff = $fromform->cancellationcutoff;
    } else {
        if ($session) {
            $todb->allowcancellations = $session->allowcancellations;
            $todb->cancellationcutoff = $session->cancellationcutofs;
        } else {
            $todb->allowcancellations = $facetoface->allowcancellationsdefault;
            $todb->cancellationcutoff = $facetoface->cancellationscutoffdefault;
        }
    }

    $transaction = $DB->start_delegated_transaction();

    $update = false;
    if (!$c and $session != null) {
        $update = true;
        $todb->id = $session->id;
        $sessionid = $session->id;
        $olddates = $DB->get_records('facetoface_sessions_dates', array('sessionid' => $session->id), 'timestart');
        if (!facetoface_update_session($todb, $sessiondates)) {
            print_error('error:couldnotupdatesession', 'facetoface', $returnurl);
        }
    } else {
        // Create or Duplicate the session.
        if (!$sessionid = facetoface_add_session($todb, $sessiondates)) {
            print_error('error:couldnotaddsession', 'facetoface', $returnurl);
        }
    }

    $fromform->id = $sessionid;
    customfield_save_data($fromform, 'facetofacesession', 'facetoface_session');

    $transaction->allow_commit();

    // Retrieve record that was just inserted/updated.
    if (!$session = facetoface_get_session($sessionid)) {
        print_error('error:couldnotfindsession', 'facetoface', $returnurl);
    }

    if ($update) {
        // Now that we have updated the session record fetch the rest of the data we need.
        facetoface_update_attendees($session);

        // Send any necessary datetime change notifications but only if date/time is known.
        if (facetoface_session_dates_check($olddates, $sessiondates)) {
            $attendees = facetoface_get_attendees($session->id);
            foreach ($attendees as $user) {
                facetoface_send_datetime_change_notice($facetoface, $session, $user->id, $olddates);
            }
        }
    }

    // Save trainer roles.
    if (isset($fromform->trainerrole)) {
        facetoface_update_trainers($facetoface, $session, $fromform->trainerrole);
    }

    // Save any calendar entries.
    $session->sessiondates = $sessiondates;
    $data = file_postupdate_standard_editor($fromform, 'details', $editoroptions, $context, 'mod_facetoface', 'session', $session->id);
    $session->details = $data->details;
    $DB->set_field('facetoface_sessions', 'details', $data->details, array('id' => $session->id));

    facetoface_update_calendar_entries($session, $facetoface);

    if ($update) {
        \mod_facetoface\event\session_updated::create_from_session($session, $context)->trigger();
    } else {
        \mod_facetoface\event\session_created::create_from_session($session, $context)->trigger();
    }

    redirect($returnurl);
}

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string($actionheading, 'facetoface', format_string($facetoface->name)));

if (!empty($errorstr)) {
    echo $OUTPUT->container(html_writer::tag('span', $errorstr, array('class' => 'errorstring')), array('class' => 'notifyproblem'));
}

$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
