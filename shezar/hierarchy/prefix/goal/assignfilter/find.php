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
 * @package shezar
 * @subpackage hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content_hierarchy.class.php');

require_once($CFG->dirroot.'/shezar/hierarchy/prefix/goal/lib.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');

//
// Params.
//

// Framework id.
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Parent id.
$parentid = optional_param('parentid', 0, PARAM_INT);

// Only return generated tree html.
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

//
// Permissions checks.
//

require_login();

$PAGE->set_context(context_system::instance());

// All hierarchy items can be viewed by any real user.
if (isguestuser()) {
    echo html_writer::tag('div', get_string('noguest', 'error'), array('class' => 'notifyproblem'));
    die;
}

// Check if Competencies are enabled.
if (shezar_feature_disabled('goals')) {
    echo html_writer::tag('div', get_string('goalsdisabled', 'shezar_hierarchy'), array('class' => 'notifyproblem'));
    die();
}

//
// Display page.
//

// Load dialog content generator.
$dialog = new shezar_dialog_content_hierarchy_multi('goal', $frameworkid);

// Toggle treeview only display.
$dialog->show_treeview_only = $treeonly;

// Load items to display.
$dialog->load_items($parentid);

// Set title.
$dialog->selected_title = 'itemstoadd';
$dialog->select_title = '';

// Display.
echo $dialog->generate_markup();
