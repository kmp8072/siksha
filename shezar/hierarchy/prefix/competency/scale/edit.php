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

require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php';
require_once $CFG->libdir.'/adminlib.php';
require_once($CFG->dirroot.'/shezar/hierarchy/lib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/competency/lib.php');
require_once 'edit_form.php';


///
/// Setup / loading data
///

// Get paramters
// Scale id; 0 if creating a new scale
$id = optional_param('id', 0, PARAM_INT);
$prefix = required_param('prefix', PARAM_ALPHA);

// Cache user capabilities.
$sitecontext = context_system::instance();

// Check if Competencies are enabled.
competency::check_feature_enabled();

// Set up the page.
admin_externalpage_setup($prefix.'manage');

if ($id == 0) {
    // Creating new competency scale.
    require_capability('shezar/hierarchy:createcompetencyscale', $sitecontext);

    $scale = new stdClass();
    $scale->id = 0;
    $scale->sortorder = $DB->get_field('comp_framework', 'MAX(sortorder) + 1', array());
    if (!$scale->sortorder) {
        $scale->sortorder = 1;
    }

} else {
    // Editing existing competency scale.
    require_capability('shezar/hierarchy:updatecompetencyscale', $sitecontext);

    if (!$scale = $DB->get_record('comp_scale', array('id' => $id))) {
        print_error('incorrectcompetencyscaleid', 'shezar_hierarchy');
    }
}


///
/// Handle form data
///
$scale->description = isset($scale->description) ? $scale->description : '';
$scale->descriptionformat = FORMAT_HTML;
$scale = file_prepare_standard_editor($scale, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                          'shezar_hierarchy', 'comp_scale', $scale->id);
$mform = new edit_scale_form(
    null, // method (default)
    array( // customdata
      'scaleid' => $id
      )
    );
$mform->set_data($scale);

// If cancelled
if ($mform->is_cancelled()) {

  redirect("$CFG->wwwroot/shezar/hierarchy/framework/index.php?prefix=competency");

  // Update data
} else if ($scalenew = $mform->get_data()) {

    $scalenew->timemodified = time();
    $scalenew->usermodified = $USER->id;
    $scalenew->description = '';
    //class to hold shezar_set_notification info
    $notification = new stdClass();

    // New scale
    if (empty($scalenew->id)) {
        unset($scalenew->id);
        $transaction = $DB->start_delegated_transaction();
        $scalenew->id = $DB->insert_record('comp_scale', $scalenew);
        $scalenew = file_postupdate_standard_editor($scalenew, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'shezar_hierarchy', 'comp_scale', $scalenew->id);
        $DB->set_field('comp_scale', 'description', $scalenew->description, array('id' => $scalenew->id));
        $scalevalues = explode("\n", trim($scalenew->scalevalues));
        unset($scalenew->scalevalues);
        $sortorder = 1;
        $scaleidlist = array();
        foreach ($scalevalues as $scaleval) {
            if (strlen(trim($scaleval)) != 0) {
                $scalevalrec = new stdClass();
                $scalevalrec->scaleid = $scalenew->id;
                $scalevalrec->name = trim($scaleval);
                $scalevalrec->sortorder = $sortorder;
                $scalevalrec->timemodified = time();
                $scalevalrec->usermodified = $USER->id;
                $scalevalrec->proficient = ($sortorder == 1) ? 1 : 0;
                $result = $DB->insert_record('comp_scale_values', $scalevalrec);
                $scaleidlist[] = $result;
                $sortorder++;
            }
        }
        // Set the default scale value to the least competent one, and the
        // "proficient" scale value to the most competent one
        if (count($scaleidlist)) {
            $scalenew->defaultid = $scaleidlist[count($scaleidlist)-1];
            $scalenew->proficient = $scaleidlist[0];
            $DB->update_record('comp_scale', $scalenew);
        }
        $transaction->allow_commit();

        $scalenew = $DB->get_record('comp_scale', array('id' => $scalenew->id));
        \hierarchy_competency\event\scale_created::create_from_instance($scalenew)->trigger();

        $notification->text = 'scaleadded';
        $notification->url = "$CFG->wwwroot/shezar/hierarchy/prefix/competency/scale/view.php?id={$scalenew->id}&amp;prefix=competency";
        $notification->params = array('class' => 'notifysuccess');
    } else {
        // Existing scale
        $scalenew = file_postupdate_standard_editor($scalenew, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'shezar_hierarchy', 'comp_scale', $scalenew->id);
        $DB->update_record('comp_scale', $scalenew);

        $scalenew = $DB->get_record('comp_scale', array('id' => $scalenew->id));
        \hierarchy_competency\event\scale_updated::create_from_instance($scalenew)->trigger();

        $notification->text = 'scaleupdated';
        $notification->url = "$CFG->wwwroot/shezar/hierarchy/prefix/competency/scale/view.php?id={$scalenew->id}&amp;prefix=competency";
        $notification->params = array('class' => 'notifysuccess');
    }
    shezar_set_notification(get_string($notification->text, 'shezar_hierarchy', $scalenew->name),
                    $notification->url, $notification->params);

}

/// Print Page
$PAGE->navbar->add(get_string("competencyframeworks", 'shezar_hierarchy'),
    new moodle_url('/shezar/hierarchy/framework/index.php', array('prefix' => 'competency')));
if ($id == 0) { // Add
  $PAGE->navbar->add(get_string('scalescustomcreate'));
  $heading = get_string('scalescustomcreate');
} else {    //Edit
  $PAGE->navbar->add(get_string('editscale', 'grades', format_string($scale->name)));
  $heading = get_string('editscale', 'grades');
}

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
$mform->display();

echo $OUTPUT->footer();