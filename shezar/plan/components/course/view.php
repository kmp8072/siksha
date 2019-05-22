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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @package shezar
 * @subpackage plan
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot.'/shezar/plan/lib.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot . '/shezar/plan/components/evidence/evidence.class.php');

// Check if Learning plans are enabled.
check_learningplan_enabled();

require_login();

$id = required_param('id', PARAM_INT); // plan id
$caid = required_param('itemid', PARAM_INT); // course assignment id
$action = optional_param('action', 'view', PARAM_TEXT);

$plan = new development_plan($id);
$systemcontext = context_system::instance();

$PAGE->set_context($systemcontext);
$PAGE->set_url('/shezar/plan/components/course/view.php', array('id' => $id, 'itemid' => $caid));
$PAGE->set_pagelayout('report');
$PAGE->set_shezar_menu_selected('learningplans');

// Permissions check.
$can_access = dp_can_view_users_plans($plan->userid);
$can_view = dp_role_is_allowed_action($plan->role, 'view');
$can_manage = dp_can_manage_users_plans($plan->userid);

if (!$can_access || !$can_view) {
    print_error('error:nopermissions', 'shezar_plan');
}

// Check the item is in this plan
if (!$DB->record_exists('dp_plan_course_assign', array('planid' => $plan->id, 'id' => $caid))) {
    print_error('error:itemnotinplan', 'shezar_plan');
}

$plancompleted = $plan->status == DP_PLAN_STATUS_COMPLETE;
$componentname = 'course';
$component = $plan->get_component($componentname);
$currenturl = new moodle_url('/shezar/plan/components/course/view.php', array('id' => $id, 'itemid' => $caid));

$evidence = new dp_evidence_relation($id, $componentname, $caid);

$competenciesenabled = shezar_feature_visible('competencies') && $plan->get_component('competency')->get_setting('enabled');
$competencyname = get_string('competencyplural', 'shezar_plan');
$objectivesenabled = $plan->get_component('objective')->get_setting('enabled');
$objectivename = get_string('objectiveplural', 'shezar_plan');
$canupdate = $component->can_update_items() && $can_manage;
$mandatory_list = $component->get_mandatory_linked_components($caid, 'course');

$fullname = $plan->name;
$pagetitle = format_string(get_string('learningplan', 'shezar_plan').': '.$fullname);

$role = $plan->get_user_role($USER->id);
$permission = dp_get_template_permission($plan->templateid, 'course', 'deletemandatory', $role);
$delete_mandatory = $permission >= DP_PERMISSION_ALLOW;

// Check if we are performing an action
if ($data = data_submitted() && $canupdate) {
    require_sesskey();

    if ($action === 'removelinkedcomps' && !$plan->is_complete()) {
        $deletions = array();

        // Load existing list of linked competencies
        $fullidlist = $component->get_linked_components($caid, 'competency');

        // Grab all linked items for deletion
        $comp_assigns = optional_param_array('delete_linked_comp_assign', array(), PARAM_BOOL);
        if ($comp_assigns) {
            foreach ($comp_assigns as $linkedid => $delete) {
                if (!$delete || (!$delete_mandatory && in_array($linkedid, $mandatory_list))) {
                    //ignore if it isn't being deleted,
                    //or if it is mandatory and you do not have the correct permission
                    continue;
                }

                $deletions[] = $linkedid;
            }

            if ($fullidlist && $deletions) {
                $newidlist = array_diff($fullidlist, $deletions);
                $component->update_linked_components($caid, 'competency', $newidlist);
            }
        }

        if ($deletions) {
            shezar_set_notification(get_string('selectedlinkedcompetenciesremovedfromcourse', 'shezar_plan'), $currenturl, array('class' => 'notifysuccess'));
        } else {
            redirect($currenturl);
        }
        die();
    }

    if ($action === 'removelinkedevidence' && !$plan->is_complete()) {
        $selectedids = optional_param_array('delete_linked_evidence', array(), PARAM_BOOL);
        $evidence->remove_linked_evidence($selectedids, $currenturl);
    }
}

dp_get_plan_base_navlinks($plan->userid);
$PAGE->navbar->add($fullname, new moodle_url('/shezar/plan/view.php', array('id' => $id)));
$PAGE->navbar->add(get_string("{$component->component}plural", 'shezar_plan'), new moodle_url($component->get_url()));
$PAGE->navbar->add(get_string('viewitem', 'shezar_plan'));

/// Javascript stuff
// If we are showing dialog
if ($canupdate) {
    // Setup lightbox
    local_js(array(
        shezar_JS_DIALOG,
        shezar_JS_TREEVIEW
    ));

    $PAGE->requires->string_for_js('save', 'shezar_core');
    $PAGE->requires->string_for_js('cancel', 'moodle');
    $PAGE->requires->string_for_js('addlinkedcompetencies', 'shezar_plan');
    $PAGE->requires->string_for_js('addlinkedevidence', 'shezar_plan');

    // Get competency picker
    $jsmodule = array(
        'name' => 'shezar_plan_course_find_competency',
        'fullpath' => '/shezar/plan/components/course/find-competency.js',
        'requires' => array('json'));
    $PAGE->requires->js_init_call('M.shezar_plan_course_find_competency.init', array('args' => '{"plan_id":'.$id.', "course_id":'.$caid.'}'), false, $jsmodule);

    // Get evidence picker
    $jsmodule_evidence = array(
        'name' => 'shezar_plan_find_evidence',
        'fullpath' => '/shezar/plan/components/evidence/find-evidence.js',
        'requires' => array('json'));
    $PAGE->requires->js_init_call('M.shezar_plan_find_evidence.init',
            array('args' => '{"plan_id":'.$id.', "component_name":"'.$componentname.'", "item_id":'.$caid.'}'),
            false, $jsmodule_evidence);

}

$plan->print_header($componentname, array(), false);

echo $component->display_course_detail($caid);

// Display linked competencies
if ($competenciesenabled) {
    echo html_writer::empty_tag('br');
    echo $OUTPUT->heading(get_string('linkedx', 'shezar_plan', $competencyname), 3);
    echo $OUTPUT->container_start(null, "dp-course-competencies-container");
    if ($linkedcomps = $component->get_linked_components($caid, 'competency')) {
        $url = new moodle_url($currenturl, array('action' => 'removelinkedcomps', 'sesskey' => sesskey()));
        echo html_writer::start_tag('form', array('id' => "dp-component-update",  'action' => $url->out(false), "method" => "POST"));
        if ($delete_mandatory) {
            echo $plan->get_component('competency')->display_linked_competencies($linkedcomps);
        } else {
            echo $plan->get_component('competency')->display_linked_competencies($linkedcomps, $mandatory_list);
        }
        if ($canupdate) {
            echo $OUTPUT->single_button($url, get_string('removeselected', 'shezar_plan'), 'post', array('class' => 'plan-remove-selected'));
        }
        echo html_writer::end_tag('form');
    } else {
        echo html_writer::tag('p', get_string('nolinkedx', 'shezar_plan', mb_strtolower($competencyname, "UTF-8")), array('class' => 'noitems-assigncompetencies'));
    }
    echo $OUTPUT->container_end();

    if (!$plancompleted && $canupdate) {
        echo $component->display_competency_picker($caid);
    }
}

// Display linked objectives
if ($objectivesenabled) {
    echo html_writer::empty_tag('br');
    echo $OUTPUT->heading(get_string('linkedx', 'shezar_plan', $objectivename), 3);

    if ($linkedobjectives = $component->get_linked_components( $caid, 'objective')) {
        echo $plan->get_component('objective')->display_linked_objectives($linkedobjectives);
    } else {
        echo html_writer::tag('p', get_string('nolinkedx', 'shezar_plan', mb_strtolower($objectivename, "UTF-8")));
    }
}

// Display linked evidence
echo $evidence->display_linked_evidence($currenturl, $canupdate, $plancompleted);

// Comments
echo $OUTPUT->heading(get_string('comments', 'shezar_plan'), 3, null, 'comments');
require_once($CFG->dirroot.'/comment/lib.php');
comment::init();
$options = new stdClass;
$options->area    = 'plan_course_item';
$options->context = context_system::instance();
$options->itemid  = $caid;
$options->showcount = true;
$options->component = 'shezar_plan';
$options->autostart = true;
$options->notoggle = true;
$comment = new comment($options);
echo $comment->output(true);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
