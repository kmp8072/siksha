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
 * @package modules
 * @subpackage facetoface
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/signup_tsandcs_form.php');

$s = required_param('s', PARAM_INT); // Facetoface session ID.

if (!$session = facetoface_get_session($s)) {
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}
$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $facetoface->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance("facetoface", $facetoface->id, $course->id);

if ($facetoface->approvaltype != APPROVAL_SELF) {
    // This should not happen unless there is a concurrent change of settings.
    print_error('error');
}

// It needs to be possible for users who cannot access the course to see this so that they can signup.
// This is only true if they are not enrolled AND there is a direct enrolment instance in the course.
// If they can access the course then we check there access normally using require_login with the course and cm.
// Otherwise we require them to login but not the course and try a direct enrolment access.
if (can_access_course($course)) {
    // User is already enrolled, let them view the text again.
    require_login($course, true, $cm);
    require_capability('mod/facetoface:view', context_module::instance($cm->id));

} else {
    // EVERYONE must login.
    require_login();
    // Can user self enrol via any instance?
    // First check that they can view the course, and that direct enrolment is enabled.
    if (!shezar_course_is_viewable($course->id) || !enrol_is_enabled('shezar_facetoface')) {
        // They can't access the course.
        print_error('error');
    }
    // Now check if there is a direct enrolment instance that will let them in.
    $allow = false;
    $instances = $DB->get_records('enrol', array('courseid' => $course->id, 'enrol' => 'shezar_facetoface'));
    if (count($instances)) {
        /** @var enrol_shezar_facetoface_plugin $enrol */
        $enrol = enrol_get_plugin('shezar_facetoface');
    }
    foreach ($instances as $instance) {
        if ($enrol->can_self_enrol($instance, true) === true) {
            $allow = true;
            break;
        }
    }
    if (!$allow) {
        print_error('cannotenrol', 'enrol_shezar_facetoface');
    }
}

$mform = new signup_tsandcs_form(null, array('tsandcs' => $facetoface->approvalterms, 's' => $s));

// This should be json_encoded, but for now we need to use html content
// type to not break $.get().
header('Content-type: text/html; charset=utf-8');
$mform->display();
