<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
require_once($CFG->dirroot.'/shezar/plan/components/competency/dialog_content_linked_competencies.class.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php');

$PAGE->set_context(context_system::instance());
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

$planid = required_param('planid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

///
/// Load plan
///
require_capability('shezar/plan:accessplan', context_system::instance());

$plan = new development_plan($planid);
$component = $plan->get_component('course');
$linkedcompetencies = $component->get_linked_components($courseid, 'competency');
$selected = array();
if (!empty($linkedcompetencies)) {
    list($insql, $params) = $DB->get_in_or_equal($linkedcompetencies);
    $sql = "SELECT ca.id, c.fullname
            FROM {dp_plan_competency_assign} ca
            INNER JOIN {comp} c ON ca.competencyid = c.id
            WHERE ca.id $insql
            ORDER BY c.fullname";
    $selected = $DB->get_records_sql($sql, $params);
}
// Access control check
if (!$permission = $component->can_update_items()) {
    print_error('error:cannotupdatecompetencies', 'shezar_plan');
}


///
/// Setup dialog
///

// Load dialog content generator
$dialog = new shezar_dialog_linked_competencies_content_competencies();

// Set type to multiple
$dialog->type = shezar_dialog_content::TYPE_CHOICE_MULTI;
$dialog->selected_title = 'itemstoadd';

// Add data
$dialog->load_competencies($planid);

// Set selected items
$dialog->selected_items = $selected;

// Display page
echo $dialog->generate_markup();
