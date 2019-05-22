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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package shezar
 * @subpackage program
 */

/**
 * Page for adding a program
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot . '/shezar/cohort/lib.php');
require_once('edit_form.php');
require_once($CFG->dirroot . '/shezar/certification/lib.php');
require_once($CFG->dirroot . '/shezar/customfield/fieldlib.php');

require_login();

$categoryid = optional_param('category', 0, PARAM_INT); // course category - can be changed in edit form
$iscertif = optional_param('iscertif', 0, PARAM_INT); // program=0|certification=1 - passed from certification/add.php

// Check if programs or certifications are enabled.
if ($iscertif) {
    check_certification_enabled();
} else {
    check_program_enabled();
}

$systemcontext = context_system::instance();
$actualurl = new moodle_url('/shezar/program/add.php', array('category' => $categoryid, 'iscertif' => $iscertif));

// Integrate into the admin tree only if the user can create programs at the top level,
// otherwise the admin block does not appear to this user, and you get an error.
if ($iscertif) {
    if (has_capability('shezar/certification:createcertification', $systemcontext)) {
        admin_externalpage_setup('managecertifications', '', null, $actualurl);
    } else {
        $PAGE->set_context($systemcontext);
        $PAGE->set_url($actualurl);
        $PAGE->set_title(get_string('createnewcertification', 'shezar_certification'));
        $PAGE->set_heading(get_string('createnewcertification', 'shezar_certification'));
    }
} else {
    if (has_capability('shezar/program:createprogram', $systemcontext)) {
        admin_externalpage_setup('programmgmt', '', null, $actualurl);
    } else {
        $PAGE->set_context($systemcontext);
        $PAGE->set_url($actualurl);
        $PAGE->set_title(get_string('createnewprogram', 'shezar_program'));
        $PAGE->set_heading(get_string('createnewprogram', 'shezar_program'));
    }
}


$catcontext = context_coursecat::instance($categoryid, MUST_EXIST);
if ($categoryid) { // Creating new program in this category.
    if (!$category = $DB->get_record('course_categories', array('id' => $categoryid))) {
        print_error('Category ID was incorrect');
    }
    if (!$iscertif) {
        require_capability('shezar/program:createprogram', $catcontext);
    } else {
        require_capability('shezar/certification:createcertification', $catcontext);
    }
}


//Javascript include
local_js(array(
    shezar_JS_DIALOG,
    shezar_JS_UI,
    shezar_JS_ICON_PREVIEW,
    shezar_JS_TREEVIEW
));

$PAGE->requires->string_for_js('chooseicon', 'shezar_program');
$iconjsmodule = array(
        'name' => 'shezar_iconpicker',
        'fullpath' => '/shezar/core/js/icon.picker.js',
        'requires' => array('json'));

$iconargs = array('args' => '{"selected_icon":"default", "type":"program"}');

$PAGE->requires->js_init_call('M.shezar_iconpicker.init', $iconargs, false, $iconjsmodule);

// Visible audiences.
if (!empty($CFG->audiencevisibility)) {
    $PAGE->requires->strings_for_js(array('programcohortsvisible'), 'shezar_cohort');
    $jsmodule = array(
                    'name' => 'shezar_visiblecohort',
                    'fullpath' => '/shezar/cohort/dialog/visiblecohort.js',
                    'requires' => array('json'));
    $args = array('args'=>'{"visibleselected":"", "type":"program", "instancetype": "' .
        COHORT_ASSN_ITEMTYPE_CATEGORY . '", "instanceid": "' . $categoryid . '"}');
    $PAGE->requires->js_init_call('M.shezar_visiblecohort.init', $args, true, $jsmodule);
}
///
/// Data and actions
///

$item = new stdClass();
$item->id = 0;
$item->endnote = '';
$item->endnoteformat = FORMAT_HTML;
$item->summary = '';
$item->summaryformat = FORMAT_HTML;

$currenturl = qualified_me();
$progindexurl = "{$CFG->wwwroot}/shezar/program/index.php";

$item = file_prepare_standard_editor($item, 'summary', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                          'shezar_program', 'summary', 0);

$item = file_prepare_standard_editor($item, 'endnote', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                          'shezar_program', 'endnote', 0);

$overviewfilesoptions = prog_program_overviewfiles_options($item);
if ($overviewfilesoptions) {
    file_prepare_standard_filemanager($item, 'overviewfiles', $overviewfilesoptions, $systemcontext, 'shezar_program', 'overviewfiles', 0);
}
$form = new program_edit_form($currenturl, array('action' => 'add', 'category' => $category,
                'editoroptions' => $TEXTAREA_OPTIONS, 'iscertif' =>  $iscertif));

if ($form->is_cancelled()) {
    redirect($progindexurl);
}

// Set type.
$instancetype = COHORT_ASSN_ITEMTYPE_PROGRAM;
if ($iscertif) {
    $instancetype = COHORT_ASSN_ITEMTYPE_CERTIF;
}

// Handle form submit
if ($data = $form->get_data()) {

    if (isset($data->savechanges)) {

        $availablefrom = ($data->availablefrom) ? $data->availablefrom : 0;
        $availableuntil = ($data->availableuntil) ? $data->availableuntil : 0;

        $program_todb = new stdClass;
        $program_todb->category = $data->category;
        $program_todb->shortname = $data->shortname;
        $program_todb->fullname = $data->fullname;
        $program_todb->idnumber = $data->idnumber;
        $program_todb->icon = $data->icon;
        $program_todb->availablefrom = $availablefrom;
        $program_todb->availableuntil = $availableuntil;
        if (isset($data->visible)) {
            $program_todb->visible = $data->visible;
        }
        if (isset($data->audiencevisible)) {
            $program_todb->audiencevisible = $data->audiencevisible;
        }

        // Set up the new program.
        $program = program::create($program_todb);
        $newid = $program->id;

        $data->id = $newid;
        customfield_save_data($data, 'program', 'prog');

        $editoroptions = $TEXTAREA_OPTIONS;
        $editoroptions['context'] = context_program::instance($newid);

        $data = file_postupdate_standard_editor($data, 'summary', $editoroptions, $editoroptions['context'], 'shezar_program', 'summary', 0);
        $data = file_postupdate_standard_editor($data, 'endnote', $editoroptions, $editoroptions['context'], 'shezar_program', 'endnote', 0);
        if ($overviewfilesoptions = prog_program_overviewfiles_options($newid)) {
            // Save the course overviewfiles
            $data = file_postupdate_standard_filemanager($data, 'overviewfiles', $overviewfilesoptions, $editoroptions['context'], 'shezar_program', 'overviewfiles', 0);
        }
        $DB->set_field('prog', 'summary', $data->summary, array('id' => $newid));
        $DB->set_field('prog', 'endnote', $data->endnote, array('id' => $newid));

        // Visible audiences.
        if (!empty($CFG->audiencevisibility)) {
            $visiblecohorts = shezar_cohort_get_visible_learning($newid, $instancetype);
            $visiblecohorts = !empty($visiblecohorts) ? $visiblecohorts : array();
            $newvisible = !empty($data->cohortsvisible) ? explode(',', $data->cohortsvisible) : array();
            if ($todelete = array_diff(array_keys($visiblecohorts), $newvisible)) {
                // Delete removed cohorts.
                foreach ($todelete as $cohortid) {
                    shezar_cohort_delete_association($cohortid, $visiblecohorts[$cohortid]->associd,
                                                     $instancetype, COHORT_ASSN_VALUE_VISIBLE);
                }
            }

            if ($newvisible = array_diff($newvisible, array_keys($visiblecohorts))) {
                // Add new cohort associations.
                foreach ($newvisible as $cohortid) {
                    shezar_cohort_add_association($cohortid, $newid, $instancetype, COHORT_ASSN_VALUE_VISIBLE);
                }
            }
        }

        // take them straight to edit page if they have permissions,
        // otherwise view the program
        $programcontext = context_program::instance($newid);
        if (has_capability('shezar/program:configuredetails', $programcontext)) {
            $viewurl = "{$CFG->wwwroot}/shezar/program/edit.php?id={$newid}&amp;action=edit";
        } else {
            $viewurl = "{$CFG->wwwroot}/shezar/program/view.php?id={$newid}";
        }

        // Certification
        $newcertid = 0;
        if ($data->iscertif) {
            $certification_todb = new stdClass;
            $certification_todb->learningcomptype = CERTIFTYPE_PROGRAM;
            $certification_todb->activeperiod = '1 year';
            $certification_todb->windowperiod = '1 month';
            $certification_todb->recertifydatetype = CERTIFRECERT_EXPIRY;
            $certification_todb->timemodified = time();

            // TODO move to prog transaction?
            $transaction = $DB->start_delegated_transaction();

            // Set up the certification
            $newcertid = $DB->insert_record('certif', $certification_todb);
            $DB->set_field('prog', 'certifid', $newcertid , array('id' => $newid));

            $transaction->allow_commit();

            if (has_capability('shezar/certification:configuredetails', $programcontext)) {
                $viewurl = "{$CFG->wwwroot}/shezar/program/edit.php?id={$newid}";
            } else {
                $viewurl = "{$CFG->wwwroot}/shezar/program/view.php?id={$newid}";
            }

            $successmsg = get_string('certifprogramcreatesuccess', 'shezar_certification');
        } else {
            $successmsg = get_string('programcreatesuccess', 'shezar_program');
        }

        // Call prog_fix_program_sortorder to ensure new program is displayed properly and the counts are updated.
        // Needs to be called at the very end!
        prog_fix_program_sortorder($data->category);

        $event = \shezar_program\event\program_created::create(
            array(
                'objectid' => $newid,
                'context' => context_program::instance($newid),
                'userid' => $USER->id,
                'other' => array(
                    'certifid' => $newcertid,
                ),
            )
        );
        $event->trigger();

      shezar_set_notification($successmsg, $viewurl, array('class' => 'notifysuccess'));
    }
}

///
/// Display
///
if (!$iscertif) {
    $heading = get_string('createnewprogram', 'shezar_program');
    $pagetitle = format_string(get_string('program', 'shezar_program').': '.$heading);
} else {
    $heading = get_string('createnewcertifprog', 'shezar_certification');
    $pagetitle = format_string(get_string('certifprog', 'shezar_certification').': '.$heading);
}

prog_add_base_navlinks();
$PAGE->navbar->add($heading);

echo $OUTPUT->header();

echo $OUTPUT->container_start('program add', 'program-add');

$context = context_coursecat::instance($category->id);
$exceptions = 0;
echo $OUTPUT->heading($heading);

require('tabs.php');

$form->display();

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
