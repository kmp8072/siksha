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
 * @package shezar_dashboard
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/dashboard/lib.php');
require_once($CFG->dirroot . '/shezar/dashboard/dashboard_forms.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');

$action = optional_param('action', null, PARAM_ALPHANUMEXT);
$id = 0;
if ($action != 'new') {
    $id = required_param('id', PARAM_INT);
}

admin_externalpage_setup('shezardashboard', '', array('id' => $id), new moodle_url('/shezar/dashboard/edit.php'));

// Check shezar Dashboard is enable.
shezar_dashboard::check_feature_enabled();

$dashboard = new shezar_dashboard($id);

$returnurl = new moodle_url('/shezar/dashboard/manage.php');

$mform = new shezar_dashboard_edit_form(null, array('dashboard' => $dashboard->get_for_form()));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($fromform = $mform->get_data()) {
    if (empty($fromform->submitbutton)) {
        shezar_set_notification(get_string('error:unknownbuttonclicked', 'shezar_dashboard'), $returnurl);
    }

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    $dashboard->set_from_form($fromform)->save();

    shezar_set_notification(get_string('dashboardsaved', 'shezar_dashboard'), $returnurl, array('class' => 'notifysuccess'));
}

if ($id == 0) {
    $heading = get_string('createdashboard', 'shezar_dashboard');
    $name = get_string('createdashboard', 'shezar_dashboard');
} else {
    $heading = get_string('editdashboard', 'shezar_dashboard');
    $name = $dashboard->name;
}

// Set up JS.
local_js(array(
        shezar_JS_UI,
        shezar_JS_ICON_PREVIEW,
        shezar_JS_DIALOG,
        shezar_JS_TREEVIEW
        ));

// Assigned audiences.
$cohorts = implode(',', $dashboard->get_cohorts());

$PAGE->requires->strings_for_js(array('assignedcohorts'), 'shezar_dashboard');
$jsmodule = array(
        'name' => 'shezar_cohortdialog',
        'fullpath' => '/shezar/dashboard/dialog/cohort.js',
        'requires' => array('json'));
$args = array('args'=>'{"selected":"' . $cohorts . '",'.
        '"COHORT_ASSN_VALUE_ENROLLED":' . COHORT_ASSN_VALUE_ENROLLED . '}');
$PAGE->requires->js_init_call('M.shezar_dashboardcohort.init', $args, true, $jsmodule);
unset($cohorts);

$title = $PAGE->title . ': ' . $heading;
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->navbar->add($name);

$output = $PAGE->get_renderer('shezar_dashboard');

echo $output->header();
echo $output->heading(get_string('managedashboards', 'shezar_dashboard'));
$mform->display();
echo $output->footer();
