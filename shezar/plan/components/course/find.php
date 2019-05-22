<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content_courses.class.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php');

$PAGE->set_context(context_system::instance());
// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

///
/// Setup / loading data
///

// Plan id
$id = required_param('id', PARAM_INT);

// Category id
$categoryid = optional_param('parentid', 'cat0', PARAM_ALPHANUM);

// Strip cat from begining of categoryid
$categoryid = (int) substr($categoryid, 3);


///
/// Load plan
///
require_capability('shezar/plan:accessplan', context_system::instance());

$plan = new development_plan($id);
$component = $plan->get_component('course');

// Access control check
$can_manage = dp_can_manage_users_plans($plan->userid);
$can_update = dp_role_is_allowed_action($plan->role, 'update');

if (!$can_manage || !$can_update) {
    print_error('error:cannotupdateitems', 'shezar_plan');
}

if (!$permission = $component->can_update_items()) {
    print_error('error:cannotupdatecourses', 'shezar_plan');
}

$selected = array();
$unremovable = array();

foreach ($component->get_assigned_items() as $item) {
    $item->id = $item->courseid;
    $selected[$item->courseid] = $item;

    if (!$component->can_delete_item($item)) {
        $unremovable[$item->courseid] = $item;
    }
}


///
/// Setup dialog
///

// Load dialog content generator
$dialog = new shezar_dialog_content_courses($categoryid);

// Set type to multiple
$dialog->type = shezar_dialog_content::TYPE_CHOICE_MULTI;
$dialog->selected_title = 'itemstoadd';

// Add data
$dialog->load_courses(false, $plan->userid);

// Set selected items
$dialog->selected_items = $selected;

// Set unremovable items
$dialog->unremovable_items = $unremovable;

// Addition url parameters
$dialog->urlparams = array('id' => $id);

// Display page
echo $dialog->generate_markup();
