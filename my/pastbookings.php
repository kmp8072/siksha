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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage my
 */

require_once(dirname(dirname(__FILE__)).'/config.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT); // which user to show
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format','', PARAM_TEXT); // export format
$edit = optional_param('edit', -1, PARAM_BOOL);
$debug  = optional_param('debug', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/my/pastbookings.php', array('userid' => $userid, 'format' => $format)));
$PAGE->set_shezar_menu_selected('mybookings');
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('my-bookings');

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:usernotfound', 'shezar_core');
}

// Users can only view their own and their staff's pages.
if ($USER->id != $userid && !\shezar_job\job_assignment::is_managing($USER->id, $userid) && !is_siteadmin()) {
    print_error('error:cannotviewthispage', 'shezar_core');
}

$output = $PAGE->get_renderer('shezar_reportbuilder');

if ($USER->id != $userid) {
    $strheading = get_string('pastbookingsfor', 'shezar_core').fullname($user, true);
    if (shezar_feature_visible('myteam')) {
        $menuitem = 'myteam';
        $url = new moodle_url('/my/teammembers.php');
        $PAGE->navbar->add(get_string('team', 'shezar_core'), $url);
    } else {
        $menuitem = null;
        $url = null;
    }
} else {
    $strheading = get_string('mypastbookings', 'shezar_core');
    $menuitem = null;
    $url = null;
}

$shortname = 'pastbookings';
$data = array(
    'userid' => $userid,
);

if (!$report = reportbuilder_get_embedded_report($shortname, $data, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
}

if ($debug) {
    $report->debug($debug);
}

$logurl = $PAGE->url->out_as_local_url();
if ($format != '') {
    $report->export_data($format);
    die;
}

\shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

$fullname = $report->fullname;
$pagetitle = format_string(get_string('report', 'shezar_core').': '.$fullname);

$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->navbar->add($strheading);

if (!isset($USER->editing)) {
    $USER->editing = 0;
}
$editbutton = '';
if ($PAGE->user_allowed_editing()) {
    $editbutton .= $OUTPUT->edit_button($PAGE->url);
    if ($edit == 1 && confirm_sesskey()) {
        $USER->editing = 1;
        $url = new moodle_url($PAGE->url, array('notifyeditingon' => 1));
        redirect($url);
    } else if ($edit == 0 && confirm_sesskey()) {
        $USER->editing = 0;
        redirect($PAGE->url);
    }
} else {
    $USER->editing = 0;
}

$PAGE->set_button($report->edit_button().$editbutton);

echo $OUTPUT->header();

$currenttab = "pastbookings";
include('booking_tabs.php');

$report->display_restrictions();

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

// Display heading including filtering stats.
$heading = $strheading . ': ' .
    $output->print_result_count_string($countfiltered, $countall);
echo $OUTPUT->heading($heading);

print $output->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo html_writer::empty_tag('br');

print $output->showhide_button($report->_id, $report->shortname);

$report->display_table();

// Export button.
$output->export_select($report, $sid);

echo $OUTPUT->footer();

?>
