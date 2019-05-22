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

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/position/lib.php');

position::check_feature_enabled();

///
/// Params
///

// Competency id
$assignto = required_param('assignto', PARAM_INT);

// Framework id
$frameworkid = required_param('frameworkid', PARAM_INT);

// Competencies to add
$add = required_param('add', PARAM_SEQUENCE);

// Indicates whether current related items, not in $add list, should be deleted
$deleteexisting = optional_param('deleteexisting', 0, PARAM_BOOL);

// Non JS parameters
$nojs = optional_param('nojs', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$s = optional_param('s', '', PARAM_TEXT);

// Setup page
admin_externalpage_setup('positionmanage');

// Check if Competencies are enabled.
if (shezar_feature_disabled('competencies')) {
    echo html_writer::tag('div', get_string('competenciesdisabled', 'shezar_hierarchy'), array('class' => 'notifyproblem'));
    die();
}

// Check permissions
$sitecontext = context_system::instance();
require_capability('shezar/hierarchy:updateposition', $sitecontext);

// Setup hierarchy objects
$competencies = new competency();
$positions = new position();

// Load position
if (!$position = $positions->get_item($assignto, $frameworkid)) {
    print_error('positionnotfound', 'shezar_hierarchy');
}

// Currently assigned competencies
if (!$currentlyassigned = $positions->get_assigned_competencies($assignto, $frameworkid)) {
    $currentlyassigned = array();
}


// Parse input
$add = $add ? explode(',', $add) : array();
$time = time();

///
/// Delete removed assignments (if specified)
///

if ($deleteexisting) {
    $removeditems = array_diff(array_keys($currentlyassigned), $add);

    foreach ($removeditems as $rid) {
        // Retrieve the item for the event, then delete it.
        $snapshots = $DB->get_records('pos_competencies', array('positionid' => $position->id, 'competencyid' => $rid));
        $DB->delete_records('pos_competencies', array('positionid' => $position->id, 'competencyid' => $rid));

        // There should only be one but we have to do this in a loop to be safe.
        foreach ($snapshots as $snapshot) {
            \hierarchy_position\event\competency_unassigned::create_from_instance($snapshot)->trigger();
        }
    }
}


///
/// Assign competencies
///

$str_remove = get_string('remove');

$rc = 0;

foreach ($add as $addition) {
    $rc = $rc == 0 ? 1 : 0;
    if (in_array($addition, array_keys($currentlyassigned))) {
        // Skip assignment
        continue;
    }
    // Check id
    if (!is_numeric($addition)) {
        print_error('baddatanonnumeric', 'shezar_hierarchy');
    }

    // Load competency
    $related = $competencies->get_item($addition);

    // Add relationship
    $relationship = new stdClass();
    $relationship->positionid = $position->id;
    $relationship->competencyid = $related->id;
    $relationship->timecreated = $time;
    $relationship->usermodified = $USER->id;

    $relationship->id = $DB->insert_record('pos_competencies', $relationship);

    $relationship = $DB->get_record('pos_competencies', array('id' => $relationship->id));
    \hierarchy_position\event\competency_assigned::create_from_instance($relationship)->trigger();
}

if ($nojs) {
    // If JS disabled, redirect back to original page (only if session key matches)
    $url = ($s == sesskey()) ? $returnurl : $CFG->wwwroot;
    redirect($url);
} else {
    // Return html
    $positions->display_extra_view_info($position, $frameworkid);
}
