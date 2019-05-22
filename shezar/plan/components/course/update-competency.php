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
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php');

require_login();

// Check if Learning plans are enabled.
check_learningplan_enabled();

// Check if Competencies are enabled.
if (shezar_feature_disabled('competencies')) {
    echo html_writer::tag('div', get_string('competenciesdisabled', 'shezar_hierarchy'), array('class' => 'notifyproblem'));
    die();
}

///
/// Setup / loading data
///

// Plan id
$planid = required_param('planid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

// Updated competency lists
$idlist = optional_param('update', null, PARAM_SEQUENCE);
if ($idlist == null) {
    $idlist = array();
} else {
    $idlist = explode(',', $idlist);
}
$context = context_system::instance();
$PAGE->set_context($context);
require_capability('shezar/plan:accessplan', $context);
$plan = new development_plan($planid);
$plancompleted = $plan->status == DP_PLAN_STATUS_COMPLETE;
$component = $plan->get_component('course');

if (!$component->can_update_items()) {
    print_error('error:cannotupdatecourses', 'shezar_plan');
}
if ($plancompleted) {
    print_error('plancompleted', 'shezar_plan');
}

$component->update_linked_components($courseid, 'competency', $idlist);
if ($linkedcompetencies =
    $component->get_linked_components($courseid, 'competency')) {
    echo $plan->get_component('competency')->display_linked_competencies($linkedcompetencies);
} else {
    $competencyname = get_string('competencyplural', 'shezar_plan');
    $message = get_string('nolinkedx', 'shezar_plan', strtolower($competencyname));
    echo html_writer::tag('p', $message, array('class' => 'noitems-assigncompetencies'));
}
