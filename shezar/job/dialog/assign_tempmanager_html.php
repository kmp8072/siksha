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
$usualmanagerid = optional_param('usualmgrid', 0, PARAM_INT);

require_login(null, false, null, false, true);

// First check that the user really does exist and that they're not a guest.
$userexists = !isguestuser($userid) && $DB->record_exists('user', array('id' => $userid, 'deleted' => 0));

$canedittempmanager = false;
if ($userexists && !empty($CFG->enabletempmanagers)) {
    $personalcontext = context_user::instance($userid);
    if (has_capability('shezar/core:delegateusersmanager', $personalcontext)) {
        $canedittempmanager = true;
    } else if ($USER->id == $userid && has_capability('shezar/core:delegateownmanager', $personalcontext)) {
        $canedittempmanager = true;
    } else if (shezar_job_can_edit_job_assignments($userid)) {
        $canedittempmanager = true;
    }
}

if (!$canedittempmanager) {
    print_error('nopermissions', '', '', 'Assign temporary managers');
}

$contextsystem = context_system::instance();
$PAGE->set_context($contextsystem);

$dialog = new shezar_job_dialog_assign_manager($userid, $managerid, $usualmanagerid);
$dialog->load_data();

echo $dialog->generate_markup();
