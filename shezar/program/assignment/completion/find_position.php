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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package shezar
 * @subpackage program
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content_hierarchy.class.php');
require_once($CFG->dirroot.'/shezar/program/program.class.php');

require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

// Check permissions.
$programid = required_param('programid', PARAM_INT);
$program = new program($programid);
require_capability('shezar/program:configureassignments', $program->get_context());
require_capability('shezar/hierarchy:viewposition', $systemcontext);
$program->check_enabled();

///
/// Setup / loading data
///


///
/// Load parameters
///

// Parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);


///
/// Display page
///

// Load dialog content generator
$dialog = new shezar_dialog_content_hierarchy('position', $frameworkid);

// Toggle treeview only display
$dialog->show_treeview_only = $treeonly;

// Load items to display
$dialog->load_items($parentid);

// Display page
echo $dialog->generate_markup();