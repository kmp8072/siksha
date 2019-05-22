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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package shezar
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');
require_once($CFG->libdir.'/completionlib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

//
// Load parameters
//
$id = required_param('id', PARAM_INT); // plan id
$componentname = required_param('c', PARAM_ALPHA); // component type
$submitted = optional_param('updatesettings', null, PARAM_TEXT); // form submitted
$ajax = optional_param('ajax', false, PARAM_BOOL); // ajax call

require_login();

//
// Load plan, component and check permissions
//
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('report');
$plan = new development_plan($id);
$ownplan = ($USER->id == $plan->userid);
$menuitem = ($ownplan) ? 'learningplans' : 'myteam';
$PAGE->set_shezar_menu_selected($menuitem);

// Check if the user can view the component content.
$can_access = dp_can_view_users_plans($plan->userid);
$can_view = dp_role_is_allowed_action($plan->role, 'view');

if (!$can_access || !$can_view) {
    print_error('error:nopermissions', 'shezar_plan');
}

// Check if the user can manage the component content.
$can_manage = dp_can_manage_users_plans($plan->userid);
$can_update = dp_role_is_allowed_action($plan->role, 'update');

// Check for valid component, before proceeding
// Check against active components to prevent hackery
if (!in_array($componentname, array_keys($plan->get_components()))) {
    print_error('error:invalidcomponent', 'shezar_plan');
}

$component = $plan->get_component($componentname);


//
// Perform actions
//
if ($submitted && confirm_sesskey()) {
    $component->process_settings_update($ajax);
    // If ajax, no need to recreate entire page
    if ($ajax) {
        // Print list and message box so page can be updated
        echo $component->display_list();
        echo $plan->display_plan_message_box();
        die();
    }
}

$component->process_action_hook();


//
// Display header
//
$component->setup_picker();

dp_get_plan_base_navlinks($USER->id);
$PAGE->navbar->add($plan->name, new moodle_url('/shezar/plan/view.php', array('id' => $plan->id)));
$PAGE->navbar->add(get_string($component->component.'plural', 'shezar_plan'));
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/shezar/plan/component.php', array('id' => $id, 'c' => $componentname)));

$plan->print_header($componentname);
$table = $component->display_list();

if ($can_manage && $can_update) {
    echo $component->display_picker();

    $form = html_writer::start_tag('form', array('id' => "dp-component-update",  'action' => $component->get_url(),
                                    "method" => "POST"));
    $form .= html_writer::empty_tag('input', array('type' => "hidden", 'id' => "sesskey",  'name' => "sesskey",
                                    'value' => sesskey()));
    $form .= html_writer::tag('div', $table, array('id' => 'dp-component-update-table'));

    if ($component->can_update_settings(false)) {
        if (!$component->get_assigned_items()) {
            $display = 'none';
        } else {
            $display = 'block';
        }
        $button = html_writer::empty_tag('input', array('type' => "submit", 'name' => "updatesettings",
                                            'value' => get_string('updatesettings', 'shezar_plan')));
        $form .= html_writer::tag('noscript', $OUTPUT->container($button, array('id' => "dp-component-update-submit",
                                            'style' => "display: {$display};")));
    }
    $form .= html_writer::end_tag('form');
    echo $form;
    echo build_datepicker_js("[id^=duedate_{$componentname}]");
} else {
    echo html_writer::tag('div', $table, array('id' => 'dp-component-update-table'));
}

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
