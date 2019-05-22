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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar
 * @subpackage feedback360
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir  . '/adminlib.php');
require_once($CFG->dirroot . '/shezar/feedback360/lib.php');
require_once($CFG->dirroot . '/shezar/feedback360/lib/assign/lib.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');

// Check if 360 Feedbacks are enabled.
feedback360::check_feature_enabled();

// Get the feedback360 id.
$itemid = required_param('id', PARAM_INT);
$module = 'feedback360';
$feedback360 = new feedback360($itemid);
$assign = new shezar_assign_feedback360($module, $feedback360);

// Capability checks.
$systemcontext = context_system::instance();
$canassign = has_capability('shezar/feedback360:assignfeedback360togroup', $systemcontext);
$canviewusers = has_capability('shezar/feedback360:viewassignedusers', $systemcontext);

$deleteid = optional_param('deleteid', null, PARAM_ALPHANUMEXT);
if ($deleteid && $canassign) {
    list($grp, $aid) = explode("_", $deleteid);
    $assign->delete_assigned_group($grp, $aid);
}

admin_externalpage_setup('managefeedback360');
// Setup the JS.
shezar_setup_assigndialogs($module, $itemid, $canviewusers);
$output = $PAGE->get_renderer('shezar_feedback360');
echo $output->header();
if ($feedback360->id) {
    echo $output->heading($feedback360->name);
    echo $output->feedback360_additional_actions($feedback360->status, $feedback360->id);
}

echo $output->feedback360_management_tabs($feedback360->id, 'assignments');

echo $output->heading(get_string('assigncurrentgroups', 'shezar_feedback360'));

if ($canassign) {
    if ($feedback360->status == feedback360::STATUS_DRAFT) {
        $groups = $assign->get_assignable_grouptype_names();
        // If hierarchy positions are disabled then don't included them in the options.
        if (shezar_feature_disabled('positions')) {
            unset($groups['pos']);
        }

        $options = array_merge(array("" => get_string('assigngroup', 'shezar_core')), $groups);
        echo html_writer::select($options, 'groupselector', null, null, array('class' => 'group_selector', 'itemid' => $itemid));
    } else if ($feedback360->status == feedback360::STATUS_CLOSED) {
        echo get_string('feedback360closednochangesallowed', 'shezar_feedback360');
    } else {
        echo get_string('feedback360activenochangesallowed', 'shezar_feedback360');
    }
}

$currentassignments = $assign->get_current_assigned_groups();

echo $output->display_assigned_groups($currentassignments, $itemid);

echo $output->heading(get_string('assigncurrentusers', 'shezar_feedback360'));

if ($canviewusers) {
    echo $output->display_user_datatable();
}

echo $output->footer();
