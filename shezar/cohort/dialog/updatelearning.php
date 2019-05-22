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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage cohort
 */
/**
 * This file is the ajax handler which adds the selected course/program to a cohort's learning items
 */
require_once(dirname(dirname(dirname(dirname(__FILE__)))) .'/config.php');
require_once($CFG->dirroot .'/cohort/lib.php');
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');

// this could take a while
core_php_time_limit::raise(0);

$context = context_system::instance();
require_capability('moodle/cohort:manage', $context);

require_sesskey();

$type = required_param('type', PARAM_TEXT);
$cohortid = required_param('cohortid', PARAM_INT);

$updateids = optional_param('u', 0, PARAM_SEQUENCE);
$value = optional_param('v', COHORT_ASSN_VALUE_ENROLLED, PARAM_INT);

// List of courses where we need to resync enrolments.
$courseids = array();

if (!empty($updateids)) {
    $updateids = explode(',', $updateids);
    foreach ($updateids as $instanceid) {
        if ($type == COHORT_ASSN_ITEMTYPE_COURSE) {
            if ($course = $DB->get_record('course', array('id' => $instanceid), 'id')) {
                $courseids[$course->id] = $course->id;
            }
        }
        shezar_cohort_add_association($cohortid, $instanceid, $type, $value);
    }
}

$delid = optional_param('d', 0, PARAM_INT);
if (!empty($delid)) {
    if (!empty($type) && !empty($delid)) {
        if ($type == COHORT_ASSN_ITEMTYPE_COURSE) {
            if ($enrolinstance = $DB->get_record('enrol', array('id' => $delid), 'id, courseid')) {
                $courseids[$enrolinstance->courseid] = $enrolinstance->courseid;
            }
        }
        shezar_cohort_delete_association($cohortid, $delid, $type, $value);
    }
}

foreach ($courseids as $courseid) {
    enrol_cohort_sync(new null_progress_trace(), $courseid, $cohortid);
}
