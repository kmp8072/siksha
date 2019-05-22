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
 * @package shezar
 * @subpackage shezar_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/plan/lib.php');
require_once($CFG->dirroot . '/shezar/hierarchy/prefix/competency/lib.php');

require_login();

// Check if Competencies are enabled.
competency::check_feature_enabled();

$userid = optional_param('userid', null, PARAM_INT);
$compid = optional_param('competencyid', null, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT);
$debug = optional_param('debug', 0, PARAM_INT);
$rolstatus = optional_param('status', 'all', PARAM_ALPHANUM);

if (!in_array($rolstatus, array('active', 'completed', 'all'))) {
    $rolstatus = 'all';
}

// Default to current user.
if (empty($userid)) {
    $userid = $USER->id;
}

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:usernotfound', 'shezar_plan');
}

$context = context_system::instance();

$urlparms = array('userid' => $userid, 'status' => $rolstatus);
if ($compid) {
    $urlparms['competencyid'] = $compid;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/shezar/hierarchy/prefix/competency/statushistoryreport.php', $urlparms));
$PAGE->set_pagelayout('report');

$renderer = $PAGE->get_renderer('shezar_reportbuilder');

if ($USER->id != $userid) {
    $strheading = get_string('recordoflearningforname', 'shezar_core', fullname($user, true));
} else {
    $strheading = get_string('recordoflearning', 'shezar_core');
}
// Get subheading name for display.
if (is_null($compid)) {
    $strsubheading = get_string($rolstatus . 'competenciessubhead', 'shezar_plan');
} else {
    $compname = format_string($DB->get_field('comp', 'fullname', array('id' => $compid)));
    $strsubheading = get_string('historyforcompetencyx', 'shezar_plan', $compname);
}

$shortname = 'plan_comp_status_history';
$data = array(
    'userid' => $userid,
    'competencyid' => $compid,
);
if ($rolstatus !== 'all') {
    $data['rolstatus'] = $rolstatus;
}
if (!$report = reportbuilder_get_embedded_report($shortname, $data, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();
if ($format != '') {
    $report->export_data($format);
    die;
}

\shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

// Display the page.
$PAGE->navbar->add($strheading, new moodle_url('/shezar/plan/record/index.php'));
$PAGE->navbar->add($strsubheading);

$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);
$PAGE->set_button($report->edit_button());

$ownplan = $USER->id == $userid;

$usertype = ($ownplan) ? 'learner' : 'manager';
$menuitem = ($ownplan) ? 'recordoflearning' : 'myteam';
$PAGE->set_shezar_menu_selected($menuitem);
dp_display_plans_menu($userid, 0, $usertype, 'competencies', $rolstatus);

echo $OUTPUT->header();

if ($debug) {
    $report->debug($debug);
}

echo $OUTPUT->container_start('', 'dp-plan-content');

echo $OUTPUT->heading($strheading . ' : ' . $strsubheading, 1);

$currenttab = 'competencies';

dp_print_rol_tabs($rolstatus, $currenttab, $userid);

$report->display_restrictions();

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$heading = $renderer->print_result_count_string($countfiltered, $countall);
echo $OUTPUT->heading($heading);

echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo $renderer->showhide_button($report->_id, $report->shortname);

$report->display_table();

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
