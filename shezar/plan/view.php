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
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage plan
 */

/**
 * Plan view page
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/shezar/plan/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

$id = required_param('id', PARAM_INT); // plan id
$action = optional_param('action', 'view', PARAM_TEXT);

if ($action == 'edit') {
    require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
}

$componentname = 'plan';

$currenturl = qualified_me();
$viewurl = strip_querystring(qualified_me())."?id={$id}&action=view";
$editurl = strip_querystring(qualified_me())."?id={$id}&action=edit";

require_login();
$plan = new development_plan($id);

// Permissions check
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/shezar/plan/view.php', array('id' => $id)));
$PAGE->set_pagelayout('report');

$ownplan = $USER->id == $plan->userid;
$menuitem = ($ownplan) ? 'learningplans' : 'myteam';
$PAGE->set_shezar_menu_selected($menuitem);

$can_access = dp_can_view_users_plans($plan->userid);
$can_view = dp_role_is_allowed_action($plan->role, 'view');

if (!$can_access || !$can_view) {
    print_error('error:nopermissions', 'shezar_plan');
}

$can_manage = dp_can_manage_users_plans($plan->userid);
$can_update = dp_role_is_allowed_action($plan->role, 'update');

require_once('edit_form.php');
$plan->descriptionformat = FORMAT_HTML;
$plan = file_prepare_standard_editor($plan, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                    'shezar_plan', 'dp_plan', $plan->id);
$form = new plan_edit_form($currenturl, array('plan' => $plan, 'action' => $action, 'can_manage' => $can_manage));

if ($form->is_cancelled()) {
    shezar_set_notification(get_string('planupdatecancelled', 'shezar_plan'), $viewurl, array('class' => 'notifysuccess'));
}

if ($plan->get_setting('view') != DP_PERMISSION_ALLOW) {
    print_error('error:nopermissions', 'shezar_plan');
}

// Handle form submits
if ($data = $form->get_data()) {
    if (!$can_manage) {
        print_error('error:nopermissions', 'shezar_plan');
    }
    if (isset($data->edit)) {
        if ($plan->get_setting('update') < DP_PERMISSION_ALLOW) {
            print_error('error:nopermissions', 'shezar_plan');
        }
        redirect($editurl);
    } else if (isset($data->delete)) {
        if ($plan->get_setting('delete') < DP_PERMISSION_ALLOW) {
            print_error('error:nopermissions', 'shezar_plan');
        }
        redirect(strip_querystring(qualified_me())."?id={$id}&action=delete");
    } else if (isset($data->deleteyes)) {
        if ($plan->get_setting('delete') < DP_PERMISSION_ALLOW) {
            print_error('error:nopermissions', 'shezar_plan');
        }
        if ($plan->delete()) {
            shezar_set_notification(get_string('plandeletesuccess', 'shezar_plan', $plan->name), "{$CFG->wwwroot}/shezar/plan/index.php?userid={$plan->userid}", array('class' => 'notifysuccess'));
        }
    } else if (isset($data->deleteno)) {
        redirect($viewurl);
    } else if (isset($data->complete)) {
        if ($plan->get_setting('completereactivate') < DP_PERMISSION_ALLOW) {
            print_error('error:nopermissions', 'shezar_plan');
        }
        redirect(strip_querystring(qualified_me())."?id={$id}&action=complete");
    } else if (isset($data->completeyes)) {
        if ($plan->get_setting('completereactivate') < DP_PERMISSION_ALLOW) {
            print_error('error:nopermissions', 'shezar_plan');
        }
        if ($plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_MANUAL_COMPLETE)) {
            \shezar_plan\event\plan_completed::create_from_plan($plan)->trigger();
            $plan->send_completion_alert();
            shezar_set_notification(get_string('plancompletesuccess', 'shezar_plan', $plan->name), $viewurl, array('class' => 'notifysuccess'));
        } else {
            shezar_set_notification(get_string('plancompletefail', 'shezar_plan', $plan->name), $viewurl);
        }
    } else if (isset($data->completeno)) {
        redirect($viewurl);
    } else if (isset($data->submitbutton)) {
        if ($plan->get_setting('update') < DP_PERMISSION_ALLOW) {
            print_error('error:nopermissions', 'shezar_plan');
        }
        // Save plan data
        $data = file_postupdate_standard_editor($data, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'shezar_plan', 'dp_plan', $data->id);
        $DB->update_record('dp_plan', $data);
        $plan = new development_plan($data->id);
        \shezar_plan\event\plan_updated::create_from_plan($plan)->trigger();
        shezar_set_notification(get_string('planupdatesuccess', 'shezar_plan'), $viewurl, array('class' => 'notifysuccess'));
    }

    // Reload plan to reflect any changes
    $plan = new development_plan($id);
}


/**
 * Display header
 */
dp_get_plan_base_navlinks($plan->userid);
$PAGE->navbar->add($plan->name);
$plan->print_header('plan');

\shezar_plan\event\plan_viewed::create_from_plan($plan)->trigger();

// Plan details
if ($plan->timecompleted) {
    $plan->enddate = $plan->timecompleted;
}
$form->set_data($plan);
$form->display();

if ($action == 'view') {
    // Comments
    require_once($CFG->dirroot.'/comment/lib.php');
    comment::init();
    $options = new stdClass();
    $options->area    = 'plan_overview';
    $options->context = $context;
    $options->itemid  = $plan->id;
    $options->showcount = true;
    $options->component = 'shezar_plan';
    $options->autostart = true;
    $options->notoggle = true;
    $comment = new comment($options);
    echo $comment->output(true);
}

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
