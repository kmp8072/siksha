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
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

header("Content-Type:text/plain");
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/evidence/lib.php');
require_once($CFG->dirroot.'/shezar/plan/development_plan.class.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

// 1. Get information
$competencyid = required_param('competencyid', PARAM_INT);
$prof = required_param('prof', PARAM_INT);
$planid = required_param('planid', PARAM_INT);

// Permissions check
require_login();

// Check permission to access the plan
$plan = new development_plan($planid);
$userid = $plan->userid;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/shezar/plan/components/competency/update-competency-setting.php',
    array('competencyid' => $competencyid, 'prof' => $prof, 'planid' => $planid)));

$componentname = 'competency';
$component = $plan->get_component($componentname);

$result = hierarchy_can_add_competency_evidence($plan, $component, $userid, $competencyid);

if ($result !== true) {
    die(get_string($result[0],$result[1]));
}

// Log it
$competencyname = $DB->get_field('comp', 'fullname', array('id' => $competencyid));
\shezar_plan\event\component_updated::create_from_component(
    $plan, 'competencyproficiency', $competencyid, $competencyname)->trigger();

// Update the competency evidence
$details = new stdClass();

// Get user's current primary position and organisation (if any)
$jobassignment = \shezar_job\job_assignment::get_first($plan->userid, false);
if ($jobassignment) {
    $details->positionid = $jobassignment->positionid;
    $details->organisationid = $jobassignment->organisationid;
}

$details->assessorname = fullname($USER);
$details->assessorid = $USER->id;

$result = hierarchy_add_competency_evidence($competencyid, $userid, $prof, $component, $details);

if ($result) {
    dp_plan_item_updated($userid, 'competency', $competencyid);
    echo "OK";
} else {
    echo "FAIL";
}
