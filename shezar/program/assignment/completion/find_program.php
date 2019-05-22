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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package shezar
 * @subpackage program
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content_programs.class.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php');
require_once($CFG->dirroot.'/shezar/program/program.class.php');

require_login();
$PAGE->set_context(context_system::instance());

// Check permissions.
$programid = required_param('programid', PARAM_INT);
$program = new program($programid);
require_capability('shezar/program:configureassignments', $program->get_context());
$program->check_enabled();

///
/// Setup / loading data
///

// Category id
$categoryid = optional_param('parentid', 'cat0', PARAM_ALPHANUM);

// Strip cat from begining of categoryid
$categoryid = (int) substr($categoryid, 3);

///
/// Setup dialog
///

// Load dialog content generator
$dialog = new shezar_dialog_content_programs($categoryid);

$dialog->selected_title = 'currentlyselected';

// Add data
$dialog->load_programs();

// Display page
echo $dialog->generate_markup();
