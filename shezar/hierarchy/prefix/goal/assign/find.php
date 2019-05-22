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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage shezar_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content_hierarchy.class.php');
require_once($CFG->dirroot.'/shezar/cohort/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/goal/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/goal/assign/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/organisation/lib.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot.'/shezar/core/lib/assign/lib.php');

// Params.

// Assign to id.
$assignto = required_param('assignto', PARAM_INT);

// Assignment module type 'pos/org/cohort'.
$assigntype = required_param('assigntype', PARAM_INT);

// Parent id.
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id.
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html.
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// Include child hierarchy items?
$includechildren = optional_param('includechildren', false, PARAM_BOOL);

// No javascript parameters.
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();
$context = context_system::instance();

$manager = false;
$self = false;

if ($assigntype == GOAL_ASSIGNMENT_INDIVIDUAL) {
    $user_context = context_user::instance($assignto);
    $manager = \shezar_job\job_assignment::is_managing($USER->id, $assignto) && has_capability('shezar/hierarchy:managestaffcompanygoal', $user_context);
    $self = $USER->id == $assignto && has_capability('shezar/hierarchy:manageowncompanygoal', $user_context);
    $admin = has_capability('shezar/hierarchy:managegoalassignments', $user_context);
} else {
    // You must have some form of managegoals permission to see this page.
    $admin = has_capability('shezar/hierarchy:managegoalassignments', $context);
}

if (!($admin || $manager || $self)) {
    print_error('error:findgoals', 'shezar_hierarchy');
}

// Check if Goals are enabled.
goal::check_feature_enabled();


$strfindgoals = get_string('findgoals', 'shezar_hierarchy');

// String of params needed in non-js url strings.
$urlparams = array('assignto' => $assignto,
                   'assigntype' => $assigntype,
                   'frameworkid' => $frameworkid,
                   'nojs' => $nojs,
                   'returnurl' => $returnurl,
                   'includechildren' => $includechildren,
                   'sesskey' => sesskey());

$type = goal::goal_assignment_type_info($assigntype);

// Set up the page.
$PAGE->set_url(new moodle_url('/shezar/hierarchy/prefix/goal/assign/find.php'), $urlparams);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_shezar_menu_selected('mygoals');
$PAGE->set_title($strfindgoals);
$PAGE->set_heading($strfindgoals);

// Set up some variables.
if ($assigntype == GOAL_ASSIGNMENT_INDIVIDUAL) {
    // Things have to be done slightly differently for individuals.
    $sql = "SELECT ga.*, g.fullname
            FROM {goal_user_assignment} ga
                LEFT JOIN {goal} g
                ON g.id = ga.goalid
            WHERE ga.userid = ?
            AND ga.assigntype = ?";

    if (!$currentlyassigned = $DB->get_records_sql($sql, array($assignto, $assigntype))) {
        $currentlyassigned = array();
    }
} else {
    $module = new $type->fullname();

    // Load module item.
    if (!$moditem = $module->get_item($assignto)) {
        // Throw a fit if the item does not exist.
        print_error($type->fullname . 'notfound', 'shezar_hierarchy');
    }

    // Currently assigned goals.
    if (!$currentlyassigned = goal::get_modules_assigned_goals($assigntype, $assignto)) {
        $currentlyassigned = array();
    }
}

// Currently assigned goals formatted for dialog handler.
$already_assigned = array();
foreach ($currentlyassigned as $assigned) {
    $assignment = new stdClass();
    $assignment->id = $assigned->goalid;
    $assignment->fullname = $assigned->fullname;
    $already_assigned[$assignment->id] = $assignment;

}

if (!$nojs) {

    // Load dialog content generator.
    if ($assigntype == GOAL_ASSIGNMENT_POSITION) {
        $dialog = new pos_goal_assign_ui_picker_hierarchy('goal', $frameworkid);
    } else if ($assigntype == GOAL_ASSIGNMENT_ORGANISATION) {
        $dialog = new org_goal_assign_ui_picker_hierarchy('goal', $frameworkid);
    } else {
        $dialog = new shezar_dialog_content_hierarchy_multi('goal', $frameworkid, false, true);
    }

    // Toggle treeview only display.
    $dialog->show_treeview_only = $treeonly;

    // Load items to display.
    $dialog->load_items($parentid);

    // Set disabled items.
    $dialog->disabled_items = $already_assigned;

    // Set title.
    $dialog->selected_title = 'itemstoadd';

    if (isset($includechildren)) {
        $dialog->includechildren = $includechildren;
    }

    // Additional url parameters.
    $dialog->urlparams = array('assignto' => $assignto, 'assigntype' => $assigntype, 'includechildren' => $includechildren);

    // Display.
    echo $dialog->generate_markup();

} else {
    // Non JS version of page.

    // Setup hierarchy objects.
    $hierarchy = new goal();

    // Load framework.
    if (!$framework = $hierarchy->get_framework($frameworkid)) {
        print_error('goalframeworknotfound', 'shezar_hierarchy');
    }

    // Load goals to display.
    $goals = $hierarchy->get_items_by_parent($parentid);

    echo $OUTPUT->header();
    $out = html_writer::tag('h2', get_string('assigngoal', 'shezar_hierarchy'));
    $link = html_writer::link($returnurl, get_string('cancelwithoutassigning', 'shezar_hierarchy'));
    $out .= html_writer::tag('p', $link);

    if (empty($frameworkid)) {

        echo build_nojs_frameworkpicker(
            $hierarchy,
            '/shezar/hierarchy/prefix/goal/assign/find.php',
            array(
                'returnurl' => $returnurl,
                'sesskey' => sesskey(),
                'nojs' => 1,
                'assignto' => $assignto,
                'assigntype' => $assigntype,
                'frameworkid' => $frameworkid,
            )
        );

    } else {
        $out .= html_writer::start_tag('div', array('id' => 'nojsinstructions'));
        $out .= build_nojs_breadcrumbs($hierarchy,
            $parentid,
            '/shezar/hierarchy/prefix/goal/assign/find.php',
            array(
                'returnurl' => $returnurl,
                'sesskey' => sesskey(),
                'nojs' => $nojs,
                'assignto' => $assignto,
                'assigntype' => $assigntype,
                'frameworkid' => $frameworkid,
            )
        );
        $out .= html_writer::tag('p', get_string('clicktoassigngoal', 'shezar_hierarchy') . ' ' .
                                      get_string('clicktoviewchildrengoal', 'shezar_hierarchy'));
        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', array('class' => 'nojsselect'));
        $out .= build_nojs_treeview(
            $goals,
            get_string('nochildgoalsfound', 'shezar_hierarchy'),
            '/shezar/hierarchy/prefix/goal/assign/assign.php',
            array(
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
                'nojs' => 1,
                'frameworkid' => $frameworkid,
                'assignto' => $assignto,
                'assigntype' => $assigntype,
                'selected' => ''
            ),
            '/shezar/hierarchy/prefix/goal/assign/find.php',
            $urlparams,
            $hierarchy->get_all_parents()
        );
        $out .= html_writer::end_tag('div');
    }
    echo $out;
    echo $OUTPUT->footer();
}
