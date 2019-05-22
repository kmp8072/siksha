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
 * @package shezar
 * @subpackage cohort
 */
/**
 * This page displays the report of "orphaned users", who are not contained in any cohort
 */
require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');

$context = context_system::instance();

$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // export format
$debug  = optional_param('debug', false, PARAM_BOOL);

$PAGE->set_context($context);

// Verify global restrictions.
$shortname = 'cohort_orphaned_users';
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$report = reportbuilder_get_embedded_report($shortname, null, false, $sid, $globalrestrictionset);

// Handle a request for export
if($format!='') {
    $report->export_data($format);
    die;
}

$report->include_js();

$url = new moodle_url('/shezar/cohort/orphanreport.php', array('format' => $format, 'debug' => $debug));
admin_externalpage_setup('cohorts', '', null, $url, array('pagelayout' => 'report'));

\shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$strcohorts = get_string('cohorts', 'shezar_cohort');
echo $OUTPUT->header();


if($debug) {
    $report->debug($debug);
}

$report->display_restrictions();

echo $OUTPUT->heading(get_string('orphanedusers', 'shezar_cohort'));
echo $OUTPUT->container(get_string('orphanhelptext', 'shezar_cohort'));

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

$report->display_table();

$output = $PAGE->get_renderer('shezar_reportbuilder');
$output->export_select($report, $sid);

echo $OUTPUT->footer();
