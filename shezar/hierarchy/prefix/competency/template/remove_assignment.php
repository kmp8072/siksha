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
 * @subpackage shezar_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/lib.php');


///
/// Setup / loading data
///

$sitecontext = context_system::instance();

// Get params
$templateid     = required_param('templateid', PARAM_INT); // Competency template ID
$assignmentid   = required_param('assignment', PARAM_INT); // Assigned competency ID

// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);

// Load data
$hierarchy          = new competency();
$template           = $hierarchy->get_template($templateid);
$competency         = $hierarchy->get_item($assignmentid);

if (!$template) {
    print_error('competencytemplatenotfound', 'shezar_hierarchy');

}

if (!$competency) {
    print_error('assignedcompetencynotfound', 'shezar_hierarchy');
}

// Check capabilities
require_capability('shezar/hierarchy:update'.$hierarchy->prefix.'template', $sitecontext);

// Setup page and check permissions
admin_externalpage_setup($hierarchy->prefix.'manage');


///
/// Display page
///

echo $OUTPUT->header();

// Cancel/return url
$return = "{$CFG->wwwroot}/shezar/hierarchy/prefix/competency/template/view.php?id={$template->id}";


if (!$delete) {
    $message = get_string('templatecompetencyremovecheck', $hierarchy->prefix).'<br /><br />';
    $message .= format_string($competency->fullname);

    $action = "{$CFG->wwwroot}/shezar/hierarchy/prefix/competency/template/remove_assignment.php?templateid={$template->id}&amp;assignment={$competency->id}&amp;delete=".md5($competency->timemodified)."&amp;sesskey={$USER->sesskey}";

    echo $OUTPUT->confirm($message, $action, $return);

    echo $OUTPUT->footer();
    exit;
}


///
/// Delete
///

if ($delete != md5($competency->timemodified)) {
    print_error('checkvariable', 'shezar_hierarchy');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

$hierarchy->delete_assigned_template_competency($template->id, $competency->id);

$message = get_string('removed'.$hierarchy->prefix.'templatecompetency', $hierarchy->prefix, format_string($competency->fullname));

echo $OUTPUT->heading($message);
echo $OUTPUT->continue_button($return);
echo $OUTPUT->footer();
