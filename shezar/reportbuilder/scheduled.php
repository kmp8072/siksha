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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

/**
 * Page for setting up scheduled reports
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir  . '/adminlib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/scheduled_forms.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/email_setting_schedule.php');

require_login();
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_url('/shezar/reportbuilder/scheduled.php');
$PAGE->set_shezar_menu_selected('myreports');

// Get the report id. This can be in one of two variables because the two forms are constructed differently.
$reportid = optional_param('reportid', 0, PARAM_INT); //report that a schedule is being added for
$formdata = optional_param_array('addanewscheduledreport', null, PARAM_INT);
$reportid = $reportid ? $reportid : intval($formdata['reportid']);
// Get the id of a scheduled report that's being edited.
$id = optional_param('id', 0, PARAM_INT);

$myreportsurl = $CFG->wwwroot . '/my/reports.php';
$returnurl = $CFG->wwwroot . '/shezar/reportbuilder/scheduled.php';
$output = $PAGE->get_renderer('shezar_reportbuilder');

if ($id == 0) {
    // Try to create report object to catch invalid data.
    $report = new reportbuilder($reportid);
    $schedule = new stdClass();
    $schedule->id = 0;
    $schedule->reportid = $reportid;
    $schedule->frequency = null;
    $schedule->schedule = null;
    $schedule->format = null;
    $schedule->exporttofilesystem = null;
    $schedule->userid = $USER->id;
} else {
    if (!$schedule = $DB->get_record('report_builder_schedule', array('id' => $id))) {
        print_error('error:invalidreportscheduleid', 'shezar_reportbuilder');
    }

    $report = new reportbuilder($schedule->reportid);
}

if (!reportbuilder::is_capable($schedule->reportid)) {
    print_error('nopermission', 'shezar_reportbuilder');
}
if ($schedule->userid != $USER->id) {
    require_capability('shezar/reportbuilder:managereports', context_system::instance());
}

$savedsearches = $report->get_saved_searches($schedule->reportid, $USER->id);
if (!isset($report->src->redirecturl)) {
    $savedsearches[0] = get_string('alldata', 'shezar_reportbuilder');
}

// Get list of emails settings for this schedule report.
$schedule->audiences = email_setting_schedule::get_audiences_to_email($id);
$schedule->systemusers = email_setting_schedule::get_system_users_to_email($id);
$schedule->externalusers = email_setting_schedule::get_external_users_to_email($id);

$existingusers = array();
foreach ($schedule->systemusers as $user) {
    $existingusers[$user->id] = $user;
}

$existingaudiences = array();
foreach ($schedule->audiences as $audience) {
    $existingaudiences[$audience->id] = $audience;
}

// Get existing users and audiences IDs.
$existingsyusers = !empty($existingusers) ? implode(',', array_keys($existingusers)) : '';
$existingaud = !empty($existingaudiences) ? implode(',', array_keys($existingaudiences)) : '';

// Load JS for lightbox.
local_js(array(
    shezar_JS_DIALOG,
    shezar_JS_TREEVIEW
));

$args = array('args'=>'{"reportid":' . $reportid . ','
    . '"id":' . $id . ','
    . '"existingsyusers":"' . $existingsyusers .'",'
    . '"existingaud":"' . $existingaud .'"}'
);

$jsmodule = array('name' => 'shezar_email_scheduled_report',
    'fullpath' => '/shezar/reportbuilder/js/email_scheduled_report.js',
    'requires' => array('json')
);

$PAGE->requires->strings_for_js(array('addsystemusers', 'addcohorts', 'emailexternaluserisonthelist'), 'shezar_reportbuilder');
$PAGE->requires->strings_for_js(array('err_email'), 'form');
$PAGE->requires->strings_for_js(array('error:badresponsefromajax'), 'shezar_cohort');
$PAGE->requires->js_init_call('M.shezar_email_scheduled_report.init', $args, false, $jsmodule);

// Form definition.
$mform = new scheduled_reports_new_form(
    null,
    array(
        'id' => $id,
        'report' => $report,
        'frequency' => $schedule->frequency,
        'schedule' => $schedule->schedule,
        'format' => $schedule->format,
        'savedsearches' => $savedsearches,
        'exporttofilesystem' => $schedule->exporttofilesystem,
        'ownerid' => $schedule->userid,
    )
);

$mform->set_data($schedule);

if ($mform->is_cancelled()) {
    redirect($myreportsurl);
}
if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        shezar_set_notification(get_string('error:unknownbuttonclicked', 'shezar_reportbuilder'), $returnurl);
    }

    if ($fromform->id) {
        if ($newid = add_scheduled_report($fromform)) {
            shezar_set_notification(get_string('updatescheduledreport', 'shezar_reportbuilder'), $myreportsurl, array('class' => 'notifysuccess'));
        }
        else {
            shezar_set_notification(get_string('error:updatescheduledreport', 'shezar_reportbuilder'), $returnurl);
        }
    }
    else {
        if ($newid = add_scheduled_report($fromform)) {
            shezar_set_notification(get_string('addedscheduledreport', 'shezar_reportbuilder'), $myreportsurl, array('class' => 'notifysuccess'));
        }
        else {
            shezar_set_notification(get_string('error:addscheduledreport', 'shezar_reportbuilder'), $returnurl);
        }
    }
}

if ($id == 0) {
    $pagename = 'addscheduledreport';
} else {
    $pagename = 'editscheduledreport';
}

$PAGE->set_title(get_string($pagename, 'shezar_reportbuilder'));
$PAGE->set_cacheable(true);
$PAGE->navbar->add(get_string('reports', 'shezar_core'), new moodle_url('/my/reports.php'));
$PAGE->navbar->add(get_string($pagename, 'shezar_reportbuilder'));
echo $output->header();

echo $output->heading(get_string($pagename, 'shezar_reportbuilder'));

$mform->display();

echo $output->footer();

function add_scheduled_report($fromform) {
    global $DB, $USER;

    if (isset($fromform->reportid) && isset($fromform->format) && isset($fromform->frequency)) {
        $report = new stdClass();
        $report->schedule = $fromform->schedule;
        $report->frequency = $fromform->frequency;
        $scheduler = new scheduler($report);
        $nextevent = $scheduler->next(time(), false, core_date::get_user_timezone());

        $transaction = $DB->start_delegated_transaction();
        $todb = new stdClass();
        if ($id = $fromform->id) {
            $todb->id = $id;
        }
        $todb->reportid = $fromform->reportid;
        $todb->savedsearchid = $fromform->savedsearchid;
        $todb->userid = $USER->id;
        $todb->format = $fromform->format;
        $todb->exporttofilesystem = $fromform->emailsaveorboth;
        $todb->frequency = $fromform->frequency;
        $todb->schedule = $fromform->schedule;
        $todb->nextreport = $nextevent->get_scheduled_time();
        if (!$id) {
            $newid = $DB->insert_record('report_builder_schedule', $todb);
        } else {
            $DB->update_record('report_builder_schedule', $todb);
            $newid = $todb->id;
        }

        // Get audiences, system users and external users and update email tables.
        $audiences = (!empty($fromform->audiences)) ? explode(',', $fromform->audiences) : array();
        $systemusers = (!empty($fromform->systemusers)) ? explode(',', $fromform->systemusers) : array();
        $externalusers = (!empty($fromform->externalemails)) ? explode(',', $fromform->externalemails) : array();

        $scheduleemail = new email_setting_schedule($newid);
        $scheduleemail->set_email_settings($audiences, $systemusers, $externalusers);

        $transaction->allow_commit();

        return $newid;
    }
    return false;
}
