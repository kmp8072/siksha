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
 * @author Peter Bulmer <peterb@catalyst.net.nz>
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Russell England <russell.england@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/shezar/plan/lib.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once('edit_form.php');
require_once('lib.php');

require_login();

if (shezar_feature_disabled('recordoflearning')) {
    print_error('error:recordoflearningdisabled', 'shezar_plan');
}

$evidenceid = required_param('id', PARAM_INT); // evidence assignment id

if (!$evidence = $DB->get_record('dp_plan_evidence', array('id' => $evidenceid))) {
    print_error('error:evidenceidincorrect', 'shezar_plan');
}
$userid = $evidence->userid;

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:usernotfound', 'shezar_plan');
}

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('report');
$PAGE->set_url('/shezar/plan/record/evidence/view.php', array('id' => $evidenceid));

if ($USER->id != $userid && !(\shezar_job\job_assignment::is_managing($USER->id, $userid)) && !has_capability('shezar/plan:accessanyplan', context_system::instance())) {
    print_error('error:cannotviewpage', 'shezar_plan');
}

if ($USER->id != $userid) {
    $strheading = get_string('recordoflearningforname', 'shezar_core', fullname($user, true));
    $usertype = 'manager';
} else {
    $strheading = get_string('recordoflearning', 'shezar_core');
    $usertype = 'learner';
}

// Get subheading name for display.
if ($usertype == 'manager') {
    if (shezar_feature_visible('myteam')) {
        $menuitem = 'myteam';
        $url = new moodle_url('/my/teammembers.php');
        $PAGE->navbar->add(get_string('team', 'shezar_core'), $url);
    } else {
        $menuitem = null;
        $url = null;
    }
} else {
    $menuitem = null;
    $url = null;
}
$indexurl = new moodle_url('/shezar/plan/record/evidence/index.php', array('userid' => $userid));
$PAGE->navbar->add($strheading, $indexurl);
$PAGE->navbar->add(get_string('allevidence', 'shezar_plan'), new moodle_url('/shezar/plan/record/evidence/index.php', array('userid' => $userid)));
$PAGE->navbar->add(get_string('evidenceview', 'shezar_plan'));

$PAGE->set_title($strheading);
$PAGE->set_heading(format_string($SITE->fullname));
dp_display_plans_menu($userid, 0, $usertype, 'evidence/index', 'none', false);
echo $OUTPUT->header();

echo $OUTPUT->container_start('', 'dp-plan-content');

echo $OUTPUT->heading($strheading);

dp_print_rol_tabs(null, 'evidence', $userid);

echo display_evidence_detail($evidenceid);

echo list_evidence_in_use($evidenceid);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
