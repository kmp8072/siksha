<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2014 onwards shezar Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package repository_opensesame
 */

require(__DIR__ . '/../../config.php');
require_once("$CFG->dirroot/lib/adminlib.php");
require_once("$CFG->dirroot/repository/lib.php");
require_once("$CFG->dirroot/shezar/reportbuilder/lib.php");

$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHANUMEXT);
$debug = optional_param('debug', false, PARAM_BOOL);

admin_externalpage_setup('opensesamereport', '', null, '', array('pagelayout'=>'report'));

$opensesame = core_plugin_manager::instance()->get_plugin_info('repository_opensesame');
if (!$opensesame->is_enabled()) {
    redirect(new moodle_url('/repository/opensesame/register.php'));
}

$report = reportbuilder_get_embedded_report('opensesame', array(), false, $sid);
$PAGE->set_button($report->edit_button());
$output = $PAGE->get_renderer('shezar_reportbuilder');

if (!empty($format)) {
    $report->export_data($format);
    die;
}

echo $output->header();

$report->display_restrictions();

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$strheading = get_string('embeddedreportname', 'rb_source_opensesame');
$heading = $strheading . ': ' . $output->print_result_count_string($countfiltered, $countall);
echo $output->heading($heading);

if ($debug) {
    $report->debug($debug);
}

$report->display_search();
$report->display_sidebar_search();

echo $report->display_saved_search_options();

$report->display_table();
$output->export_select($report, $sid);

echo $output->footer();
