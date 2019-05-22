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
 * @author David Curry <david.curry@shezarlearning.com>
 * @package shezar_job
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content_users.class.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');

// Page title
$pagetitle = 'selectmanagers';

///
/// Params
///

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

///
/// Permissions checks
///

require_login();
$PAGE->set_context(context_system::instance());

// Check that the user can view the report specified and that the report contains the filter which uses this page.
// If not, then they are not permitted to view all users here.
$reportid = required_param('reportid', PARAM_INT);
$canviewreport = reportbuilder::is_capable($reportid, $USER->id);
$reporthasfilter = reportbuilder::contains_filter($reportid, 'job_assignment', 'allmanagers');
if (!($canviewreport and $reporthasfilter)) {
    print_error('accessdenied', 'admin');
}

///
/// Display page
///

// Load dialog content generator
$dialog = new shezar_dialog_content_users();

// Toggle treeview only display
$dialog->show_treeview_only = $treeonly;

// Load items to display
$dialog->load_items(0);

// Set title
$dialog->selected_title = 'itemstoadd';
$dialog->select_title = '';

// Display
echo $dialog->generate_markup();
