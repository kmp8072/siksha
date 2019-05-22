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

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once('../lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/lib.php');


///
/// Setup / loading data
///

$sitecontext = context_system::instance();

// Get params
$prefix   = required_param('prefix', PARAM_SAFEDIR);
$id     = required_param('id', PARAM_INT);
// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);

hierarchy::check_enable_hierarchy($prefix);

$hierarchy = hierarchy::load_hierarchy($prefix);

// Setup page and check permissions
admin_externalpage_setup($prefix.'manage','',array('prefix' => $prefix));

require_capability('shezar/hierarchy:delete'.$prefix.'frameworks', $sitecontext);

$framework = $hierarchy->get_framework($id);

///
/// Display page
///
$PAGE->navbar->add(get_string("{$prefix}frameworks", 'shezar_hierarchy'),
                    new moodle_url('/shezar/hierarchy/framework/index.php', array('prefix' => $prefix)));
$PAGE->navbar->add(get_string('deleteframework', 'shezar_hierarchy', format_string($framework->fullname)));

if (!$delete) {
    echo $OUTPUT->header();
    $strdelete = get_string('deletecheckframework', 'shezar_hierarchy', format_string($framework->fullname));

    echo $OUTPUT->heading(get_string('deleteframework', 'shezar_hierarchy', format_string($framework->fullname)), 1);

    echo $OUTPUT->confirm("$strdelete" . html_writer::empty_tag('br') . html_writer::empty_tag('br'), "{$CFG->wwwroot}/shezar/hierarchy/framework/delete.php?prefix=$prefix&id={$framework->id}&amp;delete=".md5($framework->timemodified)."&amp;sesskey={$USER->sesskey}", "{$CFG->wwwroot}/shezar/hierarchy/framework/index.php?prefix=$prefix");

    echo $OUTPUT->footer();
    exit;
}


///
/// Delete framework
///

if ($delete != md5($framework->timemodified)) {
    print_error('invalidcheck', 'shezar_hierarchy');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

if ($hierarchy->delete_framework()) {
    $eventclass = "\\hierarchy_{$prefix}\\event\\framework_deleted";
    $eventclass::create_from_instance($framework)->trigger();

    shezar_set_notification(get_string($prefix.'deletedframework', 'shezar_hierarchy', $framework->fullname), "{$CFG->wwwroot}/shezar/hierarchy/framework/index.php?prefix=$prefix", array('class' => 'notifysuccess'));
} else {
    shezar_set_notification(get_string($prefix.'error:deletedframework', 'shezar_hierarchy', $framework->fullname), "{$CFG->wwwroot}/shezar/hierarchy/framework/index.php?prefix=$prefix");
}

echo $OUTPUT->footer();
