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
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package shezar
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

///
/// Setup / loading data
///

// Plan id
$id = required_param('id', PARAM_INT);

// Updated course lists
$idlist = optional_param('update', null, PARAM_SEQUENCE);
if ($idlist == null) {
    $idlist = array();
} else {
    $idlist = explode(',', $idlist);
}

$plan = new development_plan($id);
$componentname = 'course';
$component = $plan->get_component($componentname);


///
/// Permissions check
///
$can_manage = dp_can_manage_users_plans($plan->userid);
$can_update = dp_role_is_allowed_action($plan->role, 'update');

if (!$can_manage || !$can_update) {
    print_error('error:cannotupdateitems', 'shezar_plan');
}

if (!$component->can_update_items()) {
    print_error('error:cannotupdateitems', 'shezar_plan');
}

///
/// Update component
///
$component->update_assigned_items($idlist);

echo $component->display_list();
echo $plan->display_plan_message_box();
