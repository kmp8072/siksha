<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @author Keelin Devenney <keelin@learningpool.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/cancelsession_form.php');

$s = required_param('s', PARAM_INT); // facetoface session ID
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

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

require_login($course, false, $cm);
require_capability('mod/facetoface:editevents', $context);

$PAGE->set_url('/mod/facetoface/cancelsession.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));
$PAGE->set_title($facetoface->name);
$PAGE->set_heading($course->fullname);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

if (facetoface_has_session_started($session, time())) {
    // How did they get here? There should not be any link in UI to this page.
    redirect($returnurl);
}
if ($session->cancelledstatus != 0) {
    // How did they get here? There should not be any link in UI to this page.
    redirect($returnurl);
}

// NOTE: $session->allowcancellations has no relation to this script.

$mform = new mod_facetoface_cancelsession_form(null, compact('backtoallsessions', 'session'));
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    // This may take a long time...
    ignore_user_abort(true);
    if (!facetoface_cancel_session($session, $fromform)) {
        print_error('error:couldnotcancelsession', 'facetoface', $returnurl);
    }

    $message = get_string('bookingsessioncancelled', 'facetoface');
    shezar_set_notification($message, $returnurl, array('class' => 'notifysuccess'));
}

echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('cancelingsession', 'facetoface', $facetoface->name));

echo facetoface_print_session($session, true);

$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
