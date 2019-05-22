<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once $CFG->libdir.'/adminlib.php';
require_once 'edit_form.php';
require_once($CFG->dirroot . '/shezar/plan/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

///
/// Setup / loading data
///

// Get paramters
$id = optional_param('id', 0, PARAM_INT); // Priority id; 0 if creating a new priority
// Page setup and check permissions
admin_externalpage_setup('priorityscales');
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('shezar/plan:managepriorityscales', $context);
if ($id == 0) {
    // creating new Learning Plan priority
    $priority = new stdClass();
    $priority->id = 0;
    $priority->description = '';
} else {
    // editing existing Learning Plan priority
    if (!$priority = $DB->get_record('dp_priority_scale', array('id' => $id))) {
        print_error('error:priorityscaleidincorrect', 'shezar_plan');
    }
}

///
/// Handle form data
///
$priority->descriptionformat = FORMAT_HTML;
$priority = file_prepare_standard_editor($priority, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                         'shezar_plan', 'dp_priority_scale', $priority->id);
$mform = new edit_priority_form(
        null, // method (default)
        array( // customdata
            'priorityid' => $id
        )
);
$mform->set_data($priority);

// If cancelled
if ($mform->is_cancelled()) {

    redirect("$CFG->wwwroot/shezar/plan/priorityscales/index.php");

// Update data
} else if ($prioritynew = $mform->get_data()) {

    $prioritynew->timemodified = time();
    $prioritynew->usermodified = $USER->id;
    $prioritynew->sortorder = 1 + $DB->get_field_sql("SELECT MAX(sortorder) FROM {dp_priority_scale}");

    if (empty($prioritynew->id)) {
        // New priority
        unset($prioritynew->id);
        //set editor field to empty, will be updated properly later
        $prioritynew->description = '';
        $transaction = $DB->start_delegated_transaction();
        $prioritynew->id = $DB->insert_record('dp_priority_scale', $prioritynew);
        $priorityvalues = explode("\n", trim($prioritynew->priorityvalues));
        unset($prioritynew->priorityvalues);
        $sortorder = 1;
        $priorityidlist = array();
        foreach ($priorityvalues as $priorityval) {
            if (strlen(trim($priorityval)) != 0) {
                $priorityvalrec = new stdClass();
                $priorityvalrec->priorityscaleid = $prioritynew->id;
                $priorityvalrec->name = trim($priorityval);
                $priorityvalrec->sortorder = $sortorder;
                $priorityvalrec->timemodified = time();
                $priorityvalrec->usermodified = $USER->id;
                $priorityidlist[] = $DB->insert_record('dp_priority_scale_value', $priorityvalrec);
                $sortorder++;
            }
        }
        // Set the default priority value to the least competent one, and the
        // "proficient" priority value to the most competent one
        if (count($priorityidlist)) {
            $prioritynew->defaultid = $priorityidlist[count($priorityidlist)-1];
            $prioritynew->proficient = $priorityidlist[0];
        }

        $prioritynew = file_postupdate_standard_editor($prioritynew, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'shezar_plan', 'dp_priority_scale', $prioritynew->id);
        $DB->update_record('dp_priority_scale', $prioritynew);
        $transaction->allow_commit();

        $prioritynew = $DB->get_record('dp_priority_scale', array('id' => $prioritynew->id));
        \shezar_plan\event\priority_scale_created::create_from_scale($prioritynew)->trigger();

        $notification = get_string('priorityscaleadded', 'shezar_plan', format_string($prioritynew->name));

    } else {
        // Existing priority
        $prioritynew = file_postupdate_standard_editor($prioritynew, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'shezar_plan', 'dp_priority_scale', $prioritynew->id);
        $DB->update_record('dp_priority_scale', $prioritynew);

        $prioritynew = $DB->get_record('dp_priority_scale', array('id' => $prioritynew->id));
        \shezar_plan\event\priority_scale_updated::create_from_scale($prioritynew)->trigger();

        $notification = get_string('priorityscaleupdated', 'shezar_plan', format_string($prioritynew->name));
    }

    shezar_set_notification($notification,
        "$CFG->wwwroot/shezar/plan/priorityscales/view.php?id={$prioritynew->id}",
        array('class' => 'notifysuccess'));
}

/// Print Page
$PAGE->navbar->add(get_string("priorityscales", 'shezar_plan'), new moodle_url('/shezar/plan/priorityscales/index.php'));

if ($id == 0) { // Add
    $PAGE->navbar->add(get_string('priorityscalecreate', 'shezar_plan'));
    $heading = get_string('priorityscalecreate', 'shezar_plan');
} else {    //Edit
    $PAGE->navbar->add(get_string('editpriority', 'shezar_plan', format_string($priority->name)));
    $heading = get_string('editpriority', 'shezar_plan', format_string($priority->name));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$mform->display();

echo $OUTPUT->footer();
