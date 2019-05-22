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
 * @package shezar_program
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/program/lib.php');
require_once($CFG->dirroot.'/shezar/certification/lib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');

$debug = optional_param('debug', 0, PARAM_INT);
$programid = required_param('programid', PARAM_INT);

// Check capabilities.
ajax_require_login();

$program = new program($programid);
require_capability('shezar/program:configureassignments', $program->get_context());
$program->check_enabled();
$iscertif = $program->is_certif();

$PAGE->set_url('/shezar/program/assignment/duedates_report.php');
$PAGE->set_context(context_system::instance());

$renderer = $PAGE->get_renderer('shezar_reportbuilder');

if (!empty($program->certifid)) {
    $reportrecord = $DB->get_record('report_builder', array('shortname' => 'cert_assignment_duedates'));
    $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
    if (!$report = reportbuilder_get_embedded_report('cert_assignment_duedates', null, false, 0, $globalrestrictionset)) {
        print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
    }
} else {
    $reportrecord = $DB->get_record('report_builder', array('shortname' => 'program_assignment_duedates'));
    $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
    if (!$report = reportbuilder_get_embedded_report('program_assignment_duedates', null, false, 0, $globalrestrictionset)) {
        print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
    }
}

if ($debug) {
    $report->debug($debug);
}

$countall = $report->get_full_count();

$heading = get_string('totalusersinassignment', 'shezar_program', $countall);
echo $renderer->heading($heading);

$report->display_search();
$report->display_sidebar_search();

$report->display_table();
