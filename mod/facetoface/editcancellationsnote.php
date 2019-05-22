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
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package mod_facetoface
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/cancellation_note_form.php');

$userid    = required_param('userid', PARAM_INT); // Facetoface cancellation user ID.
$sessionid = required_param('s', PARAM_INT); // Facetoface session ID.

$url = new moodle_url('/mod/facetoface/editattendeesnote.php', array('userid' => $userid, 'sessionid' => $sessionid));
$returnurlparams = array('s' => $sessionid, 'backtoallsessions' => 1, 'action' => 'cancellations');
$returnurl = new moodle_url('/mod/facetoface/attendees.php', $returnurlparams);

require_sesskey();

if (!$session = facetoface_get_session($sessionid)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $facetoface->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);

// Check essential permissions.
$PAGE->set_url($url);
require_login($course, true, $cm);
require_capability('mod/facetoface:manageattendeesnote', $context);

$attendeenote = facetoface_get_attendee($sessionid, $userid);
$attendeenote->userid = $attendeenote->id;
$attendeenote->id = $attendeenote->submissionid;
$attendeenote->sessionid = $sessionid;
customfield_load_data($attendeenote, 'facetofacecancellation', 'facetoface_cancellation');

$mform = new cancellation_note_form(null, array('s' => $sessionid, 'userid' => $userid, 'attendeenote' => $attendeenote));
$mform->set_data($attendeenote);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) {
    // Save the custom fields.
    customfield_save_data($fromform, 'facetofacecancellation', 'facetoface_cancellation');
    // Redirect.
    redirect($returnurl);
}

$pagetitle = format_string($facetoface->name);

$PAGE->set_title(format_string($facetoface->name, true, array('context' => $context)));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $mform->display();
echo $OUTPUT->footer();
