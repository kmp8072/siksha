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
 * @subpackage reportbuilder
 */

/**
 * Page containing performance report settings
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/shezar/core/lib/scheduler.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/report_forms.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');

$id = required_param('id', PARAM_INT); // report id

admin_externalpage_setup('rbmanagereports');

$output = $PAGE->get_renderer('shezar_reportbuilder');

$returnurl = new moodle_url('/shezar/reportbuilder/performance.php', array('id' => $id));

$report = new reportbuilder($id);

$schedule = array();
if ($report->cache) {
    $cache = reportbuilder_get_cached($id);
    $scheduler = new scheduler($cache, array('nextevent' => 'nextreport'));
    $schedule = $scheduler->to_array();
}
// form definition
$mform = new report_builder_edit_performance_form(null, array('id' => $id, 'report' => $report, 'schedule' => $schedule));

// form results check
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/shezar/reportbuilder/index.php');
}
if ($fromform = $mform->get_data()) {

    if (empty($fromform->submitbutton)) {
        shezar_set_notification(get_string('error:unknownbuttonclicked', 'shezar_reportbuilder'), $returnurl);
    }

    $todb = new stdClass();
    $todb->id = $id;
    $todb->initialdisplay = isset($fromform->initialdisplay) ? $fromform->initialdisplay : 0;
    $todb->cache = isset($fromform->cache) ? $fromform->cache : 0;
    $todb->timemodified = time();
    $DB->update_record('report_builder', $todb);

    if ($fromform->cache) {
        reportbuilder_schedule_cache($id, $fromform);
        if (isset($fromform->generatenow) && $fromform->generatenow) {
            reportbuilder_generate_cache($id);
        }
    } else {
        reportbuilder_purge_cache($id, true);
    }

    $report = new reportbuilder($id);
    \shezar_reportbuilder\event\report_updated::create_from_report($report, 'performance')->trigger();
    shezar_set_notification(get_string('reportupdated', 'shezar_reportbuilder'), $returnurl, array('class' => 'notifysuccess'));
}

echo $output->header();

echo $output->container_start('reportbuilder-navlinks');
echo $output->view_all_reports_link() . ' | ';
echo $output->view_report_link($report->report_url());
echo $output->container_end();

echo $output->heading(get_string('editreport', 'shezar_reportbuilder', format_string($report->fullname)));

$currenttab = 'performance';
require('tabs.php');

// display the form
$mform->display();

echo $output->footer();
