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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package shezar
 * @subpackage program
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');

$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format','', PARAM_TEXT); // Export format.
$debug = optional_param('debug', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/shezar/program/find.php');
if ($CFG->forcelogin) {
    require_login();
}

// Check if programs are enabled.
check_program_enabled();

$renderer = $PAGE->get_renderer('shezar_reportbuilder');
$strheading = get_string('searchprograms', 'shezar_program');
$shortname = 'findprograms';

if (!$report = reportbuilder_get_embedded_report($shortname, null, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();
if ($format != '') {
    $report->export_data($format);
    die;
}

\shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

$fullname = format_string($report->fullname);
$pagetitle = format_string(get_string('report', 'shezar_core').': '.$fullname);

$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($fullname, new moodle_url('/shezar/program/find.php'));
$PAGE->navbar->add(get_string('search'));
$PAGE->set_title($pagetitle);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

if ($debug) {
    $report->debug($debug);
}

$report->display_restrictions();

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$heading = $strheading . ': ' .
    $renderer->print_result_count_string($countfiltered, $countall);
echo $OUTPUT->heading($heading);

print $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

print $renderer->showhide_button($report->_id, $report->shortname);

$report->display_table();

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->footer();
