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
 * @package tool
 * @subpackage shezar_sync
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/shezar_sync/lib.php');

$debug  = optional_param('debug', false, PARAM_BOOL);
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // export format
$delete = optional_param('del', 'none', PARAM_ALPHANUM);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/' . $CFG->admin . '/tool/shezar_sync/admin/synclog.php');

if ($CFG->forcelogin) {
    require_login();
}

$renderer = $PAGE->get_renderer('shezar_reportbuilder');
$strheading = get_string('synclog', 'tool_shezar_sync');
$shortname = 'shezarsynclog';

if (!$report = reportbuilder_get_embedded_report($shortname, null, false, $sid)) {
    print_error('error:couldnotgenerateembeddedreport', 'shezar_reportbuilder');
}

if ($delete != 'none' && confirm_sesskey()) {
    if (has_capability('tool/shezar_sync:deletesynclog', $context)) {
        $sql = 'SELECT MAX(time) as maxtime from {shezar_sync_log}';
        $maxtime = $DB->get_field_sql($sql);
        $runid = latest_runid();

        if ($delete == 'all' || $delete == 'partial') {
            //double check before deleting (almost) all the records from shezar_sync_log
            $confirmed = new moodle_url('/' . $CFG->admin . '/tool/shezar_sync/admin/synclog.php',
                array('del' => md5($maxtime . ':' . $delete . ':' . $runid)));
            $cancelled = new moodle_url('/' . $CFG->admin . '/tool/shezar_sync/admin/synclog.php');

            echo $OUTPUT->header();
            echo $OUTPUT->confirm(get_string("delete{$delete}synclogcheck", 'tool_shezar_sync'), $confirmed, $cancelled);
            echo $OUTPUT->footer();
            exit;
        } else {
            if ($delete == md5($maxtime . ':' . 'all' . ':' . $runid)) {
                // Delete all sync.
                $DB->delete_records('shezar_sync_log');
            } else if ($delete == md5($maxtime . ':' . 'partial' . ':' . $runid)) {
                // Delete all but most recent sync.
                $DB->delete_records_select('shezar_sync_log', 'runid < ?', array('runid' => $runid));
            }
        }
    } else {
        print_error('error:deletesynclogpermission', 'tool_shezar_sync');
    }
}

$logurl = $PAGE->url->out_as_local_url();
if ($format != '') {
    $report->export_data($format);
    die;
}

\shezar_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

$report->include_js();

$fullname = format_string($report->fullname);
$pagetitle = format_string(get_string('report', 'shezar_core') . ': ' . $fullname);

$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('view'));
$PAGE->set_title($pagetitle);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$report->display_restrictions();

$heading = $strheading . ': ' . $renderer->print_result_count_string($countfiltered, $countall);
echo $OUTPUT->heading($heading);

if ($debug) {
    $report->debug($debug);
}

print $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

$report->display_table();

// Export button.
$renderer->export_select($report, $sid);

// Show delete buttons.
if (has_capability('tool/shezar_sync:deletesynclog', $context)) {
    echo $OUTPUT->single_button(new moodle_url('/' . $CFG->admin . '/tool/shezar_sync/admin/synclog.php',
        array('del' => 'all')), get_string('deleteallsynclog', 'tool_shezar_sync'));
    echo $OUTPUT->single_button(new moodle_url('/' . $CFG->admin . '/tool/shezar_sync/admin/synclog.php',
        array('del' => 'partial')), get_string('deletepartialsynclog', 'tool_shezar_sync'));
}

echo $OUTPUT->footer();
