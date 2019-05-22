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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar
 * @subpackage coursecatalog
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');

$debug = optional_param('debug', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());

$enhancedcatalogenabled = get_config('core', 'enhancedcatalog');

if ($enhancedcatalogenabled) {
    $PAGE->set_shezar_menu_selected('certifications');
}

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/shezar/coursecatalog/certifications.php');
if ($CFG->forcelogin) {
    require_login();
}

check_certification_enabled();

$renderer = $PAGE->get_renderer('shezar_reportbuilder');
$strheading = get_string('searchcertifications', 'shezar_certification');
$shortname = 'catalogcertifications';

if (!$report = reportbuilder_get_embedded_report($shortname, null, false, 0)) {
    print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
}

$logurl = $PAGE->url->out_as_local_url();

\shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

$fullname = get_string('certifications', 'shezar_certification');
$pagetitle = format_string(get_string('findlearning', 'shezar_core') . ': ' . $fullname);

$PAGE->navbar->add($fullname, new moodle_url('/shezar/coursecatalog/certifications.php'));
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

$report->display_table();

echo $OUTPUT->footer();
