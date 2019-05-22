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
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/lib.php');
require_once($CFG->libdir.'/adminlib.php');


///
/// Setup / loading data
///

$sitecontext = context_system::instance();

// Get params
$id     = required_param('id', PARAM_INT);
// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);

$hierarchy = new competency();

// Setup page and check permissions
admin_externalpage_setup($hierarchy->prefix.'manage');

require_capability('shezar/hierarchy:delete'.$hierarchy->prefix.'template', $sitecontext);

$template = $hierarchy->get_template($id);

///
/// Display page
///

echo $OUTPUT->header();

if (!$delete) {
    $strdelete = get_string('deletechecktemplate', $hierarchy->prefix);

    $confirmurl = new moodle_url("/shezar/hierarchy/prefix/{$hierarchy->prefix}/template/delete.php", array('id' => $template->id, 'delete' => md5($template->timemodified), 'sesskey' => $USER->sesskey));
    $cancelurl = new moodle_url('/shezar/hierarchy/framework/view.php', array('prefix' => 'competency', 'frameworkid' => $template->frameworkid));
    echo $OUTPUT->confirm($strdelete . str_repeat(html_writer::empty_tag('br'), 2) . format_string($template->fullname), $confirmurl, $cancelurl);

    echo $OUTPUT->footer();
    exit;
}


///
/// Delete template
///

if ($delete != md5($template->timemodified)) {
    print_error('checkvariable', 'shezar_hierarchy');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

$hierarchy->delete_template($id);

echo $OUTPUT->heading(get_string('deletedtemplate', $hierarchy->prefix, format_string($template->fullname)));
echo $OUTPUT->continue_button("{$CFG->wwwroot}/shezar/hierarchy/framework/view.php?prefix=competency&frameworkid={$template->frameworkid}");
echo $OUTPUT->footer();
