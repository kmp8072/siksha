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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage cohort
 */
/**
 * This file displays the embedded report to show the "enrolled learning" items for a single cohort
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');

require_login();

$id     = optional_param('id', false, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // Export format.
$debug  = optional_param('debug', false, PARAM_BOOL);

if (!$id) {
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/shezar/cohort/enrolledlearning.php'));

    echo $OUTPUT->header();
    $url = new moodle_url('/cohort/index.php');
    echo $OUTPUT->container(get_string('cohortenrolledlearningselect', 'shezar_cohort', $url->out()));
    echo $OUTPUT->footer();
    exit;
}

$cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);

$context = context::instance_by_id($cohort->contextid);
$PAGE->set_context($context);

require_capability('moodle/cohort:view', $context);
// TODO: TL-7240 - allow moodle/cohort:manage to also work at category context, relies on dialogs being fixed.
// at this stage cohort:manage is only checked in system context - this can be changed once dialogs
// allow all necessary permissions checks at category level
$canedit = has_capability('moodle/cohort:manage', context_system::instance());

// NOTE: the manage capability is actually wrong here for courses because the enrolment changes are controlled with:
//       enrol_is_enabled('cohort') and has_capability('moodle/course:enrolconfig', $coursecontext) and has_capability('enrol/cohort:config', $coursecontext)

$report = reportbuilder_get_embedded_report('cohort_associations_enrolled', array('cohortid' => $id), false, $sid);

$url = new moodle_url('/shezar/cohort/enrolledlearning.php', array('id' => $id));
if ($context->contextlevel == CONTEXT_SYSTEM) {
    admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout'=>'report'));
} else {
    $PAGE->set_url($url);
    $PAGE->set_heading($COURSE->fullname);
    $PAGE->set_title($cohort->name . ' : ' . get_string('enrolledlearning', 'shezar_cohort'));
    $PAGE->set_pagelayout('report');
}

// Handle a request for export
if ($format != '') {
    $report->export_data($format);
    die;
}

\shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

// Setup lightbox.
local_js(
    array(
        shezar_JS_DIALOG,
        shezar_JS_TREEVIEW,
        shezar_JS_DATEPICKER
    )
);

$jsmodule = array(
    'name' => 'shezar_cohortenrolledlearning',
    'fullpath' => '/shezar/cohort/enrolledlearning.js',
    'requires' => array('json', 'shezar_core'));
$PAGE->requires->js_init_call('M.shezar_cohortenrolledlearning.init', null, false, $jsmodule);

// Include cohort learning js module.
$PAGE->requires->strings_for_js(array('none'), 'moodle');
$PAGE->requires->strings_for_js(array('assignenrolledlearningcourse', 'assignenrolledlearningprogram',
                                        'assignenrolledlearningcertification', 'deletelearningconfirm', 'savinglearning'),
                                        'shezar_cohort');
$jsmodule = array(
        'name' => 'shezar_cohortlearning',
        'fullpath' => '/shezar/cohort/dialog/learningitem.js',
        'requires' => array('json'));
$args = array('args'=>'{"cohortid":'.$cohort->id.','.
        '"COHORT_ASSN_ITEMTYPE_CERTIF":' . COHORT_ASSN_ITEMTYPE_CERTIF . ',' .
        '"COHORT_ASSN_ITEMTYPE_PROGRAM":' . COHORT_ASSN_ITEMTYPE_PROGRAM . ','.
        '"COHORT_ASSN_ITEMTYPE_COURSE":' . COHORT_ASSN_ITEMTYPE_COURSE . ','.
        '"COHORT_ASSN_VALUE_VISIBLE":' . COHORT_ASSN_VALUE_VISIBLE .','.
        '"COHORT_ASSN_VALUE_ENROLLED":' . COHORT_ASSN_VALUE_ENROLLED .','.
        '"assign_value":' . COHORT_ASSN_VALUE_ENROLLED .','.
        '"assign_string":"' . $COHORT_ASSN_VALUES[COHORT_ASSN_VALUE_ENROLLED] .'",'.
        '"saveurl":"/shezar/cohort/enrolledlearning.php" }');
$PAGE->requires->js_init_call('M.shezar_cohortlearning.init', $args, false, $jsmodule);
// Include cohort programcompletion js module
$PAGE->requires->strings_for_js(array('datepickerlongyeardisplayformat', 'datepickerlongyearplaceholder', 'datepickerlongyearregexjs'), 'shezar_core');
$PAGE->requires->strings_for_js(array('completioncriteria', 'pleaseentervaliddate',
    'pleaseentervalidunit', 'pleasepickaninstance', 'chooseitem', 'removecompletiondate'), 'shezar_program');
$selected_program = json_encode(dialog_display_currently_selected(get_string('selected', 'shezar_hierarchy'), 'program-completion-event-dialog'));
$jsmodule = array(
        'name' => 'shezar_cohortprogramcompletion',
        'fullpath' => '/shezar/cohort/dialog/programcompletion.js',
        'requires' => array('json'));
$args = array('args'=>'{"cohortid":'.$cohort->id.','.
        '"selected_program":'.$selected_program.','.
        '"COMPLETION_EVENT_NONE":'.COMPLETION_EVENT_NONE.','.
        '"COMPLETION_TIME_NOT_SET":'.COMPLETION_TIME_NOT_SET.','.
        '"COMPLETION_EVENT_FIRST_LOGIN":'.COMPLETION_EVENT_FIRST_LOGIN.','.
        '"COMPLETION_EVENT_ENROLLMENT_DATE":'.COMPLETION_EVENT_ENROLLMENT_DATE.'}');
$PAGE->requires->js_init_call('M.shezar_cohortprogramcompletion.init', $args, false, $jsmodule);

if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array('contextid' => $cohort->contextid)));
} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array()));
}
$strheading = get_string('enrolledlearning', 'shezar_cohort');
shezar_cohort_navlinks($cohort->id, $cohort->name, $strheading);
echo $OUTPUT->header();

if ($debug) {
    $report->debug($debug);
}

$report->display_restrictions();

echo $OUTPUT->heading(format_string($cohort->name));
echo cohort_print_tabs('enrolledlearning', $cohort->id, $cohort->cohorttype, $cohort);

echo html_writer::start_tag('div', array('class' => 'buttons enrolled-learning-buttons'));

// Add courses.
if ($canedit && has_capability('moodle/course:update', $context)) {
    echo html_writer::start_tag('div', array('class' => 'singlebutton'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-course-learningitem-dialog',
        'value' => get_string('addcourses', 'shezar_cohort')));
    echo html_writer::end_tag('div');
}

// Add programs and certifications.
if ($canedit && shezar_feature_visible('programs') && has_capability('shezar/program:configureassignments', $context)) {
    echo html_writer::start_tag('div', array('class' => 'singlebutton'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-program-learningitem-dialog',
        'value' => get_string('addprograms', 'shezar_cohort')));
    echo html_writer::end_tag('div');
}
if ($canedit && shezar_feature_visible('certifications') && has_capability('shezar/program:configureassignments', $context)) {
    echo html_writer::start_tag('div', array('class' => 'singlebutton'));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'id' => 'add-certification-learningitem-dialog',
        'value' => get_string('addcertifications', 'shezar_cohort')));
    echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

$report->display_table();

$output = $PAGE->get_renderer('shezar_reportbuilder');
$output->export_select($report, $sid);

echo $OUTPUT->footer();
