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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Russell England <russell.england@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php'); // Is this needed?

require_login();

if (shezar_feature_disabled('recordoflearning')) {
    print_error('error:recordoflearningdisabled', 'shezar_plan');
}

$userid = optional_param('userid', $USER->id, PARAM_INT); // Which user to show, default to current user.
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // Export format.
$debug  = optional_param('debug', 0, PARAM_INT);

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:usernotfound', 'shezar_plan');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_url('/shezar/plan/record/evidence/index.php', array('userid' => $userid, 'format' => $format));

if ($USER->id == $userid) {
    $strheading = get_string('recordoflearning', 'shezar_core');
    $usertype = 'learner';
    $menuitem = 'recordoflearning';
    $menunavitem = '';
    $url = null;
} else {
    $strheading = get_string('recordoflearningforname', 'shezar_core', fullname($user, true));
    $usertype = 'manager';
    if (shezar_feature_visible('myteam')) {
        $menuitem = 'myteam';
        $menunavitem = 'team';
        $url = new moodle_url('/my/teammembers.php');
    } else {
        $menuitem = null;
        $menunavitem = '';
        $url = null;
    }
}

$reportfilters = array('userid' => $userid);
$report = reportbuilder_get_embedded_report('plan_evidence', $reportfilters, false, $sid);

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

// Display the page.
$strsubheading = get_string('allevidence', 'shezar_plan');
if ($url) {
    $PAGE->navbar->add(get_string($menunavitem, 'shezar_core'), $url);
}
$PAGE->navbar->add($strheading, new moodle_url('/shezar/plan/record/index.php', array('userid' => $userid)));
$PAGE->navbar->add($strsubheading);
$PAGE->set_title($strheading);
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->set_button($report->edit_button());
$PAGE->set_shezar_menu_selected($menuitem);
dp_display_plans_menu($userid, 0, $usertype, 'evidence/index', 'none', false);

echo $OUTPUT->header();

echo $OUTPUT->container_start('', 'dp-plan-content');

echo $OUTPUT->heading($strheading.' : '.$strsubheading);

dp_print_rol_tabs(null, 'evidence', $userid);

$report->display_restrictions();

$countfiltered = $report->get_filtered_count();
$countall = $report->get_full_count();

$renderer = $PAGE->get_renderer('shezar_reportbuilder');
$heading = $renderer->print_result_count_string($countfiltered, $countall);
echo $OUTPUT->heading($heading);

echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

print $OUTPUT->single_button(
        new moodle_url("/shezar/plan/record/evidence/edit.php",
                array('id' => 0, 'userid' => $userid)), get_string('addevidence', 'shezar_plan'), 'get');

echo $renderer->showhide_button($report->_id, $report->shortname);

$report->display_table();

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
