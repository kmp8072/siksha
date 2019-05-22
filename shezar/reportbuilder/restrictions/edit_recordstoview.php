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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @author Rob Tyler <rob.tyler@shezarlms.com>
 * @package shezar_reportbuilder
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/report_forms.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/classes/rb_global_restriction.php');

$id = optional_param('id', 0, PARAM_INT); // Restriction id.
$deleteid = optional_param('deleteid', null, PARAM_ALPHANUMEXT);
$allrecords = optional_param('allrecords', null, PARAM_INT);

admin_externalpage_setup('rbmanageglobalrestrictions');
/** @var shezar_reportbuilder_renderer|core_renderer $output */
$output = $PAGE->get_renderer('shezar_reportbuilder');

$restriction = new rb_global_restriction($id);
$returnurl = new moodle_url('/shezar/reportbuilder/restrictions/index.php');

$assign = new shezar_assign_reportbuilder_record('reportbuilder', $restriction, 'record');
$grouptypes = $assign->get_assignable_grouptype_names();
$grouptypes = array_merge(array("" => get_string('assigngroup', 'shezar_core')), $grouptypes);

$continueurl = new moodle_url('/shezar/reportbuilder/restrictions/edit_recordstoview.php', array('id' => $id));

if ($allrecords !== null) {
    require_sesskey();
    $data = new stdClass();
    $data->allrecords = $allrecords ? 1 : 0;
    $restriction->update($data);
    redirect($continueurl);
}

if ($deleteid) {
    require_sesskey();
    // TODO TL-6684: add delete confirmation here.
    list($grp, $aid) = explode("_", $deleteid);
    $assign->delete_assigned_group($grp, $aid);
    redirect($continueurl);
}

if ($restriction->allrecords) {
    echo $output->edit_restriction_header($restriction, 'recordstoview');
    echo html_writer::tag('p', get_string('restrictionallrecords', 'shezar_reportbuilder'));

    $allrecordsurl = new moodle_url('/shezar/reportbuilder/restrictions/edit_recordstoview.php',
        array('id' => $id, 'allrecords' => 0, 'sesskey' => sesskey()));
    echo $output->single_button($allrecordsurl, get_string('restrictiondisableallrecords', 'shezar_reportbuilder'));

    echo $output->footer();
    die;
}

$module = 'reportbuilder';
$suffix = 'record';
shezar_setup_assigndialogs($module, $id, true, '', $suffix);
echo $output->edit_restriction_header($restriction, 'recordstoview');

echo html_writer::tag('p', get_string('recordstoviewdescription', 'shezar_reportbuilder'));

$allrecordsurl = new moodle_url('/shezar/reportbuilder/restrictions/edit_recordstoview.php',
    array('id' => $id, 'allrecords' => 1, 'sesskey' => sesskey()));
echo $output->single_button($allrecordsurl, get_string('restrictionenableallrecords', 'shezar_reportbuilder'));

echo $output->heading(get_string('assignedgroups', 'shezar_reportbuilder'), 3);
echo html_writer::select($grouptypes, 'groupselector', null, null, array('class' => 'group_selector', 'itemid' => $id));

$currentassignments = $assign->get_current_assigned_groups();
echo $output->display_assigned_groups($currentassignments, $id, $suffix);

echo $output->heading(get_string('assignedusers', 'shezar_reportbuilder'), 3);
echo $output->display_user_datatable();

echo $output->footer();
