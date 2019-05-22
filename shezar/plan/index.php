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
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/plan/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

$planuser = optional_param('userid', $USER->id, PARAM_INT); // show plans for this user

// Permission checks.
$role = ($planuser == $USER->id) ? 'learner' : 'manager';
$can_access = dp_can_view_users_plans($planuser);
$can_manage = dp_can_manage_users_plans($planuser);
$can_view = dp_role_is_allowed_action($role, 'view');
$can_create = dp_role_is_allowed_action($role, 'create');

if (!$can_access || !$can_view) {
    print_error('error:nopermissions', 'shezar_plan');
}

//
// Display plan list
//
$context = context_system::instance();
$PAGE->set_url('/shezar/plan/index.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

if ($role == 'manager') {
    $PAGE->set_shezar_menu_selected('myteam');
} else {
    $PAGE->set_shezar_menu_selected('learningplans');
}

$heading = get_string('learningplans', 'shezar_plan');
$pagetitle = get_string('learningplans', 'shezar_plan');

dp_get_plan_base_navlinks($planuser);

// Plan menu
dp_display_plans_menu($planuser,0,$role);

$PAGE->set_title($heading);
$PAGE->set_heading(format_string($SITE->fullname));
echo $OUTPUT->header();

if ($planuser != $USER->id) {
    echo dp_display_user_message_box($planuser);
}

echo $OUTPUT->heading($heading);

echo $OUTPUT->container_start('', 'dp-plans-description');

if ($planuser == $USER->id) {
    $planinstructions = get_string('planinstructions', 'shezar_plan') . ' ';
} else {
    $user = $DB->get_record('user', array('id' => $planuser));
    $userfullname = fullname($user);
    $planinstructions = get_string('planinstructionsuser', 'shezar_plan', $userfullname) . ' ';
}
if ($can_manage && $can_create) {
    $planinstructions .= get_string('planinstructions_add', 'shezar_plan');
} else {
    $planinstructions .= get_string('planinstructions_noadd', 'shezar_plan');
}

\shezar_plan\event\plan_list_viewed::create_from_userid($planuser)->trigger();

echo html_writer::tag('p', $planinstructions, array('class' => 'instructional_text'));

if ($can_manage && $can_create) {
    $renderer = $PAGE->get_renderer('shezar_plan');
    echo $renderer->print_add_plan_button($planuser);
}
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('', 'dp-plans-list-active-plans');
echo dp_display_plans($planuser, array(DP_PLAN_STATUS_APPROVED), array('enddate', 'status'), get_string('activeplans', 'shezar_plan'));
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('', 'dp-plans-list-unapproved-plans');
echo dp_display_plans($planuser, array(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_STATUS_PENDING),
    array('status'), get_string('unapprovedplans', 'shezar_plan'));
echo $OUTPUT->container_end();

echo $OUTPUT->container_start('', 'dp-plans-list-completed-plans');
echo dp_display_plans($planuser, DP_PLAN_STATUS_COMPLETE, array('completed'), get_string('completedplans', 'shezar_plan'));
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
