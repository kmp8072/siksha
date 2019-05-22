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
 * @author Oleg Demeshev <oleg.demeshev@shezarlms.com>
 * @package shezar
 * @subpackage facetoface
 */

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/attendee_note_form.php');

$userid    = required_param('userid', PARAM_INT); // Facetoface signup user ID.
$sessionid = required_param('s', PARAM_INT); // Facetoface session ID.

require_sesskey();

if (!$session = facetoface_get_session($sessionid)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemodule', 'facetoface');
}

// Check essential permissions.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
if (!has_capability('mod/facetoface:manageattendeesnote', $context)) {
    print_error('nopermissions', 'error', '', 'Showing cancellation note');
}

/* @var mod_facetoface_renderer|core_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_facetoface');
$renderer->setcontext($context);

// Get custom field values of the cancellation.
$cancellationnote = facetoface_get_attendee($sessionid, $userid);
$cancellationnote->id = $cancellationnote->submissionid;
$customfields = customfield_get_data($cancellationnote, 'facetoface_cancellation', 'facetofacecancellation');

// Prepare output.
$usernamefields = get_all_user_name_fields(true);
$user = $DB->get_record('user', array('id' => $userid), "{$usernamefields}");
$output = get_string('usercancellationnoteheading', 'facetoface', fullname($user));
$output .= html_writer::empty_tag('hr');
if (!empty($customfields)) {
    foreach ($customfields as $cftitle => $cfvalue) {
        $output .= html_writer::tag('strong', str_replace(' ', '&nbsp;', format_string($cftitle)) . ': ')
                 . html_writer::span($cfvalue);
        $output .= html_writer::empty_tag('br');
    }
} else {
    $output .= get_string('none');
}
$output .= '<hr />';
$output .= $renderer->single_button(
    new moodle_url('/mod/facetoface/editcancellationsnote.php', array('userid' => $userid, 's' => $sessionid, 'sesskey' => sesskey())),
    get_string('edit', 'mod_facetoface'),
    'get'
);

header('Content-type: text/html; charset=utf-8');
echo $output;
