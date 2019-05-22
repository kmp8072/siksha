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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package shezar
 * @subpackage program
 */

/**
 * Program view page
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');
require_once($CFG->dirroot.'/shezar/certification/lib.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');

require_login();

$id = required_param('id', PARAM_INT);

$program = new program($id);
$iscertif = $program->is_certif();
$programcontext = $program->get_context();

require_capability('shezar/program:configureassignments', $programcontext);
$program->check_enabled();

$PAGE->set_url(new moodle_url('/shezar/program/edit_assignments.php', array('id' => $id)));
$PAGE->set_context($programcontext);
$PAGE->set_title(format_string($program->fullname));
$PAGE->set_heading(format_string($program->fullname));

// Javascript include.
local_js(array(
shezar_JS_DIALOG,
shezar_JS_TREEVIEW,
shezar_JS_DATEPICKER,
shezar_JS_PLACEHOLDER
));

// Get item pickers
$PAGE->requires->strings_for_js(array('setcompletion', 'removecompletiondate', 'youhaveunsavedchanges',
                'cancel','ok','completioncriteria','pleaseentervaliddate',
                'pleaseentervalidunit','pleasepickaninstance','editassignments',
                'saveallchanges','confirmassignmentchanges','chooseitem'), 'shezar_program');
$PAGE->requires->string_for_js('loading', 'admin');
$PAGE->requires->string_for_js('none', 'moodle');
$display_selected = json_encode(dialog_display_currently_selected(get_string('selected', 'shezar_hierarchy'), 'completion-event-dialog'));
$args = array('args' => '{"id":"'.$program->id.'",'.
                         '"confirmation_template":'.prog_assignments::get_confirmation_template().','.
                         '"COMPLETION_EVENT_NONE":"'.COMPLETION_EVENT_NONE.'",'.
                         '"COMPLETION_TIME_NOT_SET":"'.COMPLETION_TIME_NOT_SET.'",'.
                         '"COMPLETION_EVENT_FIRST_LOGIN":"'.COMPLETION_EVENT_FIRST_LOGIN.'",'.
                         '"COMPLETION_EVENT_ENROLLMENT_DATE":"'.COMPLETION_EVENT_ENROLLMENT_DATE.'",'.
                         '"display_selected_completion_event":'.$display_selected.'}');

$jsmodule = array(
        'name' => 'shezar_programassignment',
        'fullpath' => '/shezar/program/assignment/program_assignment.js',
        'requires' => array('json', 'shezar_core'));

$PAGE->requires->js_init_call('M.shezar_programassignment.init',$args, false, $jsmodule);

// Define the categorys to appear on the page
$categories = prog_assignment_category::get_categories();

if ($data = data_submitted()) {

    // Check the session key
    confirm_sesskey();

    // Update each category
    foreach ($categories as $category) {
        $category->update_assignments($data);
    }

    // reset the assignments property to ensure it only contains the current assignments.
    $assignments = $program->get_assignments();
    $assignments->init_assignments($program->id);

    // Update the user assignments
    $program->update_learner_assignments();

    $prog_update = new stdClass();
    $prog_update->id = $id;
    $prog_update->timemodified = time();
    $prog_update->usermodified = $USER->id;
    $DB->update_record('prog', $prog_update);

    $eventdata = array();
    foreach ($assignments as $assignment) {
        $eventdata[] = (array) $assignment;
    }

    $event = \shezar_program\event\program_assignmentsupdated::create(
        array(
            'objectid' => $id,
            'context' => context_program::instance($id),
            'userid' => $USER->id,
            'other' => array(
                'assignments' => $eventdata,
            ),
        )
    );
    $event->trigger();

    if (isset($data->savechanges)) {
        shezar_set_notification(get_string('programassignmentssaved', 'shezar_program'), 'edit_assignments.php?id='.$id,
                                                                                        array('class' => 'notifysuccess'));
    }

}

$currenturl = qualified_me();
$currenturl_noquerystring = strip_querystring($currenturl);
$viewurl = $currenturl_noquerystring."?id={$id}&action=view";

// Trigger event.
$dataevent = array('id' => $program->id, 'other' => array('section' => 'assignments'));
$event = \shezar_program\event\program_viewed::create_from_data($dataevent)->trigger();

// Display.
$heading = format_string($program->fullname);

if ($iscertif) {
    $heading .= ' ('.get_string('certification', 'shezar_certification').')';
}

echo $OUTPUT->header();

echo $OUTPUT->container_start('program assignments', 'program-assignments');

echo $OUTPUT->heading($heading);
$renderer = $PAGE->get_renderer('shezar_program');

// Display the current status
echo $program->display_current_status();
$exceptions = $program->get_exception_count();
$currenttab = 'assignments';
require('tabs.php');

echo $renderer->display_edit_assignment_form($id, $categories, CERTIFPATH_STD); // Can use STD or CERT, they are the same.

echo $renderer->get_cancel_button(array('id' => $program->id));

echo $OUTPUT->container_end();

echo $OUTPUT->footer();