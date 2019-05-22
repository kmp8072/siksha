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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

/**
 * Page containing list of available reports and new report form
 */

define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', true); // We are setting up report here, do not accept source params.

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/report_forms.php');
require_once($CFG->dirroot . '/shezar/core/lib/scheduler.php');

$id = optional_param('id', null, PARAM_INT); // id for delete report
$d = optional_param('d', false, PARAM_BOOL); // delete record?
$em = optional_param('em', false, PARAM_BOOL); // embedded report?
$confirm = optional_param('confirm', false, PARAM_BOOL); // confirm delete
$initcache = optional_param('initcache', false, PARAM_BOOL); // force cache to update with next cron run

admin_externalpage_setup('rbmanagereports');

$output = $PAGE->get_renderer('shezar_reportbuilder');

global $USER;

$returnurl = $CFG->wwwroot . '/shezar/reportbuilder/index.php';
$type = $em ? 'reload' : 'delete';

// delete an existing report
if ($d && $confirm) {
    if (!confirm_sesskey()) {
        shezar_set_notification(get_string('error:bad_sesskey', 'shezar_reportbuilder'), $returnurl);
    }
    $report = new reportbuilder($id);
    if (reportbuilder_delete_report($id)) {
        \shezar_reportbuilder\event\report_deleted::create_from_report($report, $em)->trigger();
        shezar_set_notification(get_string($type . 'report', 'shezar_reportbuilder'), $returnurl, array('class' => 'notifysuccess'));

    } else {
        shezar_set_notification(get_string('no' . $type . 'report', 'shezar_reportbuilder'), $returnurl);
    }
} else if ($d) {
    echo $output->header();
    echo $output->heading(get_string('reportbuilder', 'shezar_reportbuilder'));
    if ($em) {
        $continueurl = new moodle_url('/shezar/reportbuilder/index.php', array('id' => $id, 'd' => '1', 'em' => $em,
            'confirm' => '1', 'sesskey' => $USER->sesskey));
        echo $output->confirm(get_string('reportconfirm'.$type, 'shezar_reportbuilder'), $continueurl, $returnurl);
    } else {
        $continueurl = new moodle_url('/shezar/reportbuilder/index.php', array('id' => $id, 'd' => '1', 'em' => $em,
            'confirm' => '1', 'sesskey' => $USER->sesskey));
        echo $output->confirm(get_string('reportconfirm'.$type, 'shezar_reportbuilder'), $continueurl, $returnurl);
    }
    echo $output->footer();
    exit;
} else if ($initcache) {
    $cache = reportbuilder_get_cached($id);
    if ($cache) {
        $schedule = new scheduler($cache, array('nextevent' => 'nextreport'));
        if (!$schedule->is_time()) {
            $schedule->do_asap();
            $DB->update_record('report_builder_cache', $cache);
        }
        shezar_set_notification(get_string('reportcacheinitialize', 'shezar_reportbuilder'), $returnurl, array('class' => 'notifysuccess'));
    } else {
        shezar_set_notification(get_string('error:reportcacheinitialize', 'shezar_reportbuilder'), $returnurl);
    }
}

// form definition
$mform = new report_builder_new_form();

// form results check
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {

    if (empty($fromform->submitbutton)) {
        shezar_set_notification(
            get_string('error:unknownbuttonclicked', 'shezar_reportbuilder'),
            $returnurl);
    }
    // create new record here
    $todb = new stdClass();
    $todb->fullname = $fromform->fullname;
    $todb->shortname = reportbuilder::create_shortname($fromform->fullname);
    $todb->source = ($fromform->source != '0') ? $fromform->source : null;
    $todb->hidden = $fromform->hidden;
    $todb->recordsperpage = 40;
    $todb->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
    $todb->accessmode = REPORT_BUILDER_ACCESS_MODE_ANY; // default to limited access
    $todb->embedded = 0;
    if (isset($fromform->globalrestriction)) {
        // In case somebody adds it to the UI in the future.
        $todb->globalrestriction = $fromform->globalrestriction;
    } else {
        // Always use the default even if GUR not active at the moment.
        $todb->globalrestriction = get_config('reportbuilder', 'globalrestrictiondefault');
    }
    $todb->timemodified = time();

    try {
        $transaction = $DB->start_delegated_transaction();

        $newid = $DB->insert_record('report_builder', $todb);

        // by default we'll require a role but not set any, which will restrict report access to
        // the site administrators only
        reportbuilder_set_default_access($newid);

        // create columns for new report based on default columns
        $src = reportbuilder::get_source_object($fromform->source);
        if (isset($src->defaultcolumns) && is_array($src->defaultcolumns)) {
            $defaultcolumns = $src->defaultcolumns;
            $so = 1;
            foreach ($defaultcolumns as $option) {
                $heading = isset($option['heading']) ? $option['heading'] :
                    null;
                $hidden = isset($option['hidden']) ? $option['hidden'] : 0;
                $column = $src->new_column_from_option($option['type'],
                    $option['value'], null, null, $heading, !empty($heading), $hidden);
                $todb = new stdClass();
                $todb->reportid = $newid;
                $todb->type = $column->type;
                $todb->value = $column->value;
                $todb->heading = $column->heading;
                $todb->hidden = $column->hidden;
                $todb->transform = $column->transform;
                $todb->aggregate = $column->aggregate;
                $todb->sortorder = $so;
                $todb->customheading = 0; // initially no columns are customised
                $DB->insert_record('report_builder_columns', $todb);
                $so++;
            }
        }
        // create filters for new report based on default filters
        if (isset($src->defaultfilters) && is_array($src->defaultfilters)) {
            $defaultfilters = $src->defaultfilters;
            $so = 1;
            foreach ($defaultfilters as $option) {
                $todb = new stdClass();
                $todb->reportid = $newid;
                $todb->type = $option['type'];
                $todb->value = $option['value'];
                $todb->advanced = isset($option['advanced']) ? $option['advanced'] : 0;
                $todb->sortorder = $so;
                $todb->region = isset($option['region']) ? $option['region'] : rb_filter_type::RB_FILTER_REGION_STANDARD;
                $DB->insert_record('report_builder_filters', $todb);
                $so++;
            }
        }
        // Create toolbar search columns for new report based on default toolbar search columns.
        if (isset($src->defaulttoolbarsearchcolumns) && is_array($src->defaulttoolbarsearchcolumns)) {
            foreach ($src->defaulttoolbarsearchcolumns as $option) {
                $todb = new stdClass();
                $todb->reportid = $newid;
                $todb->type = $option['type'];
                $todb->value = $option['value'];
                $DB->insert_record('report_builder_search_cols', $todb);
            }
        }
        $report = new reportbuilder($newid);
        \shezar_reportbuilder\event\report_created::create_from_report($report, false)->trigger();
        $transaction->allow_commit();
        redirect($CFG->wwwroot . '/shezar/reportbuilder/general.php?id='.$newid);
    } catch (ReportBuilderException $e) {
        $transaction->rollback($e);
        trigger_error($e->getMessage(), E_USER_WARNING);
    } catch (Exception $e) {
        $transaction->rollback($e);
        redirect($returnurl, get_string('error:couldnotcreatenewreport', 'shezar_reportbuilder'));
    }
}

$custom = reportbuilder::get_user_generated_reports();
usort($custom, function($a, $b) {
    return strcmp($a->fullname, $b->fullname);
});

$embedded = reportbuilder::get_embedded_reports();
usort($embedded, function($a, $b) {
    return strcmp($a->fullname, $b->fullname);
});

/** @var shezar_reportbuilder_renderer $output */
echo $output->header();

// User generated reports.
echo $output->heading(get_string('usergeneratedreports', 'shezar_reportbuilder'));
echo $output->user_generated_reports_table($custom);

// Embedded reports
echo $output->heading(get_string('embeddedreports', 'shezar_reportbuilder'));
echo $output->embedded_reports_table($embedded);

// display mform
$mform->display();

echo $output->footer();
