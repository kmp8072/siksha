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
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @package shezar
 * @subpackage certification
 */

/**
 * Program view page
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('HTML/QuickForm/Renderer/QuickHtml.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');
require_once($CFG->dirroot . '/shezar/program/lib.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once('edit_certification_form.php');

// Check if certifications are enabled.
check_certification_enabled();

$id = required_param('id', PARAM_INT); // Certification id.

require_login();

$systemcontext = context_system::instance();
$program = new program($id);
$programcontext = $program->get_context();
$timeallowance = new stdClass();
$timeallowance->seconds = $program->content->get_total_time_allowance(CERTIFPATH_RECERT);
$timeallowance->timestring = prog_format_seconds($timeallowance->seconds, true);

$certification = $DB->get_record('certif', array('id' => $program->certifid));

if (!$certification) {
    print_error(get_string('nocertifdetailsfound', 'shezar_certification'));
}

$iscertif = 1;

if (!has_capability('shezar/certification:configurecertification', $programcontext)) {
    print_error('error:nopermissions', 'shezar_program');
}

$PAGE->set_url(new moodle_url('/shezar/certification/edit_certification.php', array('id' => $id)));
$PAGE->set_context($programcontext);
$PAGE->set_title(format_string($program->fullname));
$PAGE->set_heading(format_string($program->fullname));

// Javascript include.
local_js(array(
    shezar_JS_DIALOG)
);

$currenturl = qualified_me();
$currenturl_noquerystring = strip_querystring($currenturl);
$viewurl = $currenturl_noquerystring."?id={$id}";
$overviewurl = $CFG->wwwroot."/shezar/certification/edit_certification.php?id={$id}&action=view";
$customdata = array('certification' => $certification, 'timeallowance' => $timeallowance);
$form = new edit_certification_form($currenturl, $customdata, 'post', '', array('name'=>'form_certif_details'));

if ($form->is_cancelled()) {
    shezar_set_notification(get_string('programupdatecancelled', 'shezar_program'), $overviewurl,
                                                                                array('class' => 'notifysuccess'));
}

// This is where we validate and check the submitted data before saving it.
if ($data = $form->get_data()) {

    if (isset($data->savechanges)) {
        $certification->activeperiod = $data->activenum.' '.$data->activeperiod;
        $certification->minimumactiveperiod = $data->minimumactivenum.' '.$data->minimumactiveperiod;
        $certification->windowperiod = $data->windownum.' '.$data->windowperiod;
        $certification->timemodified = time();
        $certification->recertifydatetype = $data->recertifydatetype;
        $DB->update_record('certif', $certification);

        // Trigger event.
        $event = \shezar_certification\event\certification_updated::create_from_instance($program)->trigger();

        shezar_set_notification(get_string('certificationdetailssaved', 'shezar_certification'),
                new moodle_url('/shezar/certification/edit_certification.php', array('id' => $program->id)),
                array('class' => 'notifysuccess'));
    }

}

// Display.

$heading = format_string($program->fullname);
$heading .= ' ('.get_string('certification', 'shezar_certification').')';

// Javascript includes.
$PAGE->requires->strings_for_js(array('editcertif', 'saveallchanges', 'confirmchanges',
                 'youhaveunsavedchanges', 'youhaveunsavedchanges', 'tosaveall'), 'shezar_certification');
$args = array('args'=>'{"id":'.$program->id.'}');
$jsmodule = array(
     'name' => 'shezar_certificationconfirm',
     'fullpath' => '/shezar/certification/certification_confirm.js',
     'requires' => array('json'));
$PAGE->requires->js_init_call('M.shezar_certificationconfirm.init', $args, false, $jsmodule);

echo $OUTPUT->header();

echo $OUTPUT->container_start('certification details', 'cf'.$id);

echo $OUTPUT->heading($heading);

$renderer = $PAGE->get_renderer('shezar_certification');

// Display the current status.
echo $program->display_current_status();
$exceptions = $program->get_exception_count();
$currenttab = 'certification';
require_once($CFG->dirroot . '/shezar/program/tabs.php');


// Display the form.
$form->display();

echo $renderer->get_cancel_button(array('id' => $program->id));

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
