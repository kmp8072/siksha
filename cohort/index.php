<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', 1); // Specify parameters via reportbuidler constructor!

require('../config.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');

$contextid = optional_param('contextid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$showall = optional_param('showall', 0, PARAM_BOOL);
$format = optional_param('format', '', PARAM_TEXT); //export format
$debug = optional_param('debug', false, PARAM_BOOL); //report debug

require_login();

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

$category = null;
if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
}

$manager = has_capability('moodle/cohort:manage', $context);

$strcohorts = get_string('cohorts', 'cohort');

$PAGE->set_context($context);

if ($category) {
    $showall = 0;
    $PAGE->set_pagelayout('report');
    $PAGE->set_context($context);
    $PAGE->set_url('/cohort/index.php', array('contextid' => $context->id));
    $PAGE->set_title($strcohorts);
    $PAGE->set_heading($COURSE->fullname);
} else {
    $params = array('contextid' => $context->id, 'showall' => $showall);
    admin_externalpage_setup('cohorts', '', $params, '', array('pagelayout'=>'report'));
}

if ($showall) {
    $data = array('contextid' => null);
} else {
    $data = array('contextid' => $context->id);
}

// Verify global restrictions.
$shortname = 'cohort_admin';
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$report = reportbuilder_get_embedded_report($shortname, $data, false, $sid, $globalrestrictionset);
if (!empty($format)) {
    $report->export_data($format);
    die;
}

echo $OUTPUT->header();
if ($debug) {
    $report->debug($debug);
}

$report->display_restrictions();

$fullcount = $report->get_full_count();
$filteredcount = $report->get_filtered_count();
$count = ($fullcount != $filteredcount) ? " ($filteredcount/$fullcount)" : " ($filteredcount)";

$output = $PAGE->get_renderer('shezar_reportbuilder');

if ($showall) {
    $heading = get_string('cohortsin', 'cohort', get_string('allcohorts' , 'core_cohort')) . $count;
} else {
    $heading = get_string('cohortsin', 'cohort', $context->get_context_name()) . $count;
}
echo $OUTPUT->heading($heading);

$baseurl = new moodle_url('/cohort/index.php', array('contextid' => $context->id, 'showall' => $showall));
if ($editcontrols = cohort_edit_controls($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}

// check if report is cached and warn user
if ($report->is_cached()) {
    $cohorts = cohort_get_cohorts($context->id);
    if ($cohorts['allcohorts'] != $fullcount) {
        echo $output->cache_pending_notification($report->_id);
    }
}
$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

$report->display_table();
$output->export_select($report, $sid);

echo $OUTPUT->footer();
