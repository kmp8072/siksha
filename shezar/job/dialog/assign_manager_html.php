<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@shezarlearning.com>
 * @package shezar_job
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) .'/config.php');
require_once($CFG->dirroot . '/shezar/job/dialog/assign_manager.php');
require_once($CFG->dirroot . '/shezar/job/lib.php');

$userid = required_param('userid', PARAM_INT);
$managerid = optional_param('parentid', false, PARAM_ALPHANUM);

// If you can select a manager on signup and you don't have an account.
$manageronsignup = (!empty($CFG->registerauth) && get_config('shezar_job', 'allowsignupmanager') && $userid === 0);
if (!$manageronsignup) {
    // Its off or you have signified you are looking at a specific user.
    require_login(null, false, null, false, true);
}

// First check that the user really does exist and that they're not a guest.
$userexists = !isguestuser($userid) && $DB->record_exists('user', array('id' => $userid, 'deleted' => 0));
// Check if the current user can edit the given user's job assignments.
$canedit = $userexists && shezar_job_can_edit_job_assignments($userid);

// The current user can see a list of users if:
//    They can edit the current users position.
// OR
//    'Allow primary position fields - Manager' has been turned on for the email auth plugin and
//    they are not currently logged in.
//    In which case anyone can get a list of users - there is a warning in the interface about this.
if (!$canedit && !$manageronsignup) {
    print_error('nopermissions', '', '', 'Assign managers');
}

$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);

$dialog = new shezar_job_dialog_assign_manager($userid, $managerid);
$dialog->load_data();

echo $dialog->generate_markup();
