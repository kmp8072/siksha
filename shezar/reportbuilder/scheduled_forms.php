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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

/**
 * Moodle Formslib templates for scheduled reports settings forms
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Formslib template for the new report form
 */
class scheduled_reports_new_form extends moodleform {
    function definition() {
        global $DB;

        $mform =& $this->_form;
        $id = $this->_customdata['id'];
        $frequency = $this->_customdata['frequency'];
        $schedule = $this->_customdata['schedule'];
        $format = $this->_customdata['format'];
        $ownerid = $this->_customdata['ownerid'];
        $report = $this->_customdata['report'];
        $savedsearches = $this->_customdata['savedsearches'];
        $exporttofilesystem = $this->_customdata['exporttofilesystem'];
        $context = context_system::instance();

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'reportid', $report->_id);
        $mform->setType('reportid', PARAM_INT);

        // Export type options.
        $exportformatselect = reportbuilder_get_export_options($format, false);

        $exporttofilesystemenabled = false;
        if (get_config('reportbuilder', 'exporttofilesystem') == 1) {
            $exporttofilesystemenabled = true;
        }

        $mform->addElement('header', 'general', get_string('scheduledreportsettings', 'shezar_reportbuilder'));
        $mform->addElement('static', 'report', get_string('report', 'shezar_reportbuilder'), format_string($report->fullname));
        if (empty($savedsearches)) {
            $mform->addElement('static', '', get_string('data', 'shezar_reportbuilder'),
                    html_writer::div(get_string('scheduleneedssavedfilters', 'shezar_reportbuilder', $report->report_url()),
                            'notifyproblem'));
        } else {
            $mform->addElement('select', 'savedsearchid', get_string('data', 'shezar_reportbuilder'), $savedsearches);
        }
        $mform->addElement('select', 'format', get_string('export', 'shezar_reportbuilder'), $exportformatselect);

        if ($exporttofilesystemenabled) {
            $exporttosystemarray = array();
            $exporttosystemarray[] =& $mform->createElement('radio', 'emailsaveorboth', '',
                    get_string('exporttoemail', 'shezar_reportbuilder'), REPORT_BUILDER_EXPORT_EMAIL);
            $exporttosystemarray[] =& $mform->createElement('radio', 'emailsaveorboth', '',
                    get_string('exporttoemailandsave', 'shezar_reportbuilder'), REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE);
            $exporttosystemarray[] =& $mform->createElement('radio', 'emailsaveorboth', '',
                    get_string('exporttosave', 'shezar_reportbuilder'), REPORT_BUILDER_EXPORT_SAVE);
            $mform->setDefault('emailsaveorboth', $exporttofilesystem);
            $mform->addGroup($exporttosystemarray, 'exporttosystemarray',
                    get_string('exportfilesystemoptions', 'shezar_reportbuilder'), array('<br />'), false);
        } else {
            $mform->addElement('hidden', 'emailsaveorboth', REPORT_BUILDER_EXPORT_EMAIL);
            $mform->setType('emailsaveorboth', PARAM_TEXT);
        }

        $schedulestr = get_string('schedule', 'shezar_reportbuilder');

        // A little trick to help with scheduling of reports belonging to other users.
        $owner = $DB->get_record('user', array('id' => $ownerid, 'deleted' => 0));
        if ($owner) {
            $ownertz = core_date::get_user_timezone($owner);
            if (core_date::get_user_timezone() !== $ownertz) {
                $schedulestr .= '<br />(' . core_date::get_localised_timezone($ownertz) . ')';
            }
        }

        $mform->addElement('scheduler', 'schedulegroup', $schedulestr,
                           array('frequency' => $frequency, 'schedule' => $schedule));

        // Email to, setting for the schedule reports.
        $mform->addElement('header', 'emailto', get_string('scheduledemailtosettings', 'shezar_reportbuilder'));
        $mform->addElement('html', html_writer::tag('p', get_string('warngrrvisibility', 'shezar_reportbuilder')));
        $mform->addElement('static', 'emailrequired', '', '');

        // Input hidden fields for system_users and audiences.
        $mform->addElement('hidden', 'systemusers');
        $mform->setType('systemusers', PARAM_SEQUENCE);

        $mform->addElement('hidden', 'audiences');
        $mform->setType('audiences', PARAM_SEQUENCE);

        $mform->addElement('hidden', 'externalemails');
        $mform->setType('externalemails', PARAM_TEXT);

        if (has_capability('moodle/cohort:view', $context)) {
            // Create a place to show existing audiences.
            $audiences = array();
            $audiences[] =& $mform->createElement('static', 'audiences_list', '', html_writer::div('', 'list-audiences'));
            $audiences[] =& $mform->createElement('button', 'addaudiences', get_string('addcohorts', 'shezar_reportbuilder'), array('id' => 'show-audiences-dialog'));
            $mform->addGroup($audiences, 'audiences_group', get_string('cohorts', 'shezar_cohort'), '');
        }

        if (has_capability('moodle/user:viewdetails', $context)) {
            // Create a place to show existing system users.
            $sysusers = array();
            $sysusers[] =& $mform->createElement('static', 'systemusers_list', get_string('systemusers', 'shezar_reportbuilder'), html_writer::div('', 'list-systemusers'));
            $sysusers[] =& $mform->createElement('button', 'addsystemusers', get_string('addsystemusers', 'shezar_reportbuilder'), array('id' => 'show-systemusers-dialog'));
            $mform->addGroup($sysusers, 'systemusers_list_group', get_string('systemusers', 'shezar_reportbuilder'), '');
        }

        // Text input to add new emails for external users.
        $objs = array();
        $objs[] =& $mform->createElement('static', 'externalemails_list', '', html_writer::div('', 'list-externalemails'));
        $objs[] =& $mform->createElement('text', 'emailexternals', get_string('externalemail', 'shezar_reportbuilder'), array('class' => 'reportbuilder_scheduled_addexternal', 'maxlength' => 150, 'size' => 30));
        $objs[] =& $mform->createElement('button', 'addemail', get_string('addexternalemail', 'shezar_reportbuilder'),
            array('id' => 'addexternalemail'));

        // Create a group for the elements.
        $mform->addGroup($objs, 'externalemailsgrp', get_string('emailexternalusers', 'shezar_reportbuilder'), '');
        $mform->setType('externalemailsgrp[emailexternals]', PARAM_TEXT);
        $mform->addHelpButton('externalemailsgrp', 'emailexternalusers', 'shezar_reportbuilder');

        $mform->setType('emailexternals', PARAM_EMAIL);

        if (!empty($savedsearches)) {
            $this->add_action_buttons();
        }
    }

    public function set_data($data) {
        global $PAGE;

        $mform =& $this->_form;
        $renderer = $PAGE->get_renderer('shezar_reportbuilder');

        $audiences = $data->audiences;
        $sysusers = $data->systemusers;
        $extusers = $data->externalusers;

        unset($data->audiences);
        unset($data->systemusers);
        unset($data->externalusers);

        parent::set_data($data);

        if (!empty($audiences)) {
            // Render all audiences.
            $audiencesrecords = array();
            $audienceids = array();
            foreach ($audiences as $audience) {
                $audiencesrecords[] = $renderer->schedule_email_setting($audience, 'audiences');
                $audienceids[] = $audience->id;
            }
            $divcontainer = html_writer::div(implode($audiencesrecords, ''), 'list-audiences');
            $mform->getElement('audiences_group')->getElements()[0]->setValue($divcontainer);
            $mform->getElement('audiences')->setValue(implode(',', $audienceids));
        }

        if (!empty($sysusers)) {
            // Render system users.
            $systemusers = array();
            $userids = array();
            foreach ($sysusers as $user) {
                $systemusers[] = $renderer->schedule_email_setting($user, 'systemusers');
                $userids[] = $user->id;
            }
            $divcontainer = html_writer::div(implode($systemusers, ''), 'list-systemusers');

            $mform->getElement('systemusers_list_group')->getElements()[0]->setValue($divcontainer);
            $mform->getElement('systemusers')->setValue(implode(',', $userids));
        }

        if (!empty($extusers)) {
            // Render external emails.
            $externalemails = array();
            foreach ($extusers as $extuser) {
                $external = new stdClass();
                $external->id = $extuser;
                $external->name = $extuser;
                $externalemails[] = $renderer->schedule_email_setting($external, 'externalemails');
            }
            $divcontainer = html_writer::div(implode($externalemails, ''), 'list-externalemails');
            $mform->getElement('externalemailsgrp')->getElements()[0]->setValue($divcontainer);
            $mform->getElement('externalemails')->setValue(implode(',', $extusers));
        }
    }

    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        $audiences = $data['audiences'];
        $sysusers  = $data['systemusers'];
        $extusers  = $data['externalemails'];
        $emailsaveorboth = $data['emailsaveorboth'];

        if (empty($audiences) && empty($sysusers) && empty($extusers) && $emailsaveorboth != REPORT_BUILDER_EXPORT_SAVE) {
            $errors['emailrequired'] = get_string('error:emailrequired', 'shezar_reportbuilder');
        }

        return $errors;
    }
}


class scheduled_reports_add_form extends moodleform {
    function definition() {

        $mform =& $this->_form;

        $sources = array();

        //Report type options
        $reports = reportbuilder::get_user_permitted_reports();
        $reportselect = array();
        foreach ($reports as $report) {
            if (!isset($sources[$report->source])) {
                $sources[$report->source] = reportbuilder::get_source_object($report->source);
            }

            if ($sources[$report->source]->scheduleable) {
                try {
                    if ($report->embedded) {
                        $reportobject = new reportbuilder($report->id);
                    }
                    $reportselect[$report->id] = format_string($report->fullname);
                } catch (moodle_exception $e) {
                    if ($e->errorcode != "nopermission") {
                        // The embedded report creation failed, almost certainly due to a failed is_capable check.
                        // In this case, we just don't add it to $reportselect.
                    } else {
                        throw ($e);
                    }
                }
            }
        }

        if (!empty($reportselect)) {
            $elements = array ();
            $elements[] = &$mform->createElement('select', 'reportid', get_string('addnewscheduled', 'shezar_reportbuilder'), $reportselect);
            $elements[] = &$mform->createElement('submit', 'submitbutton', get_string('addscheduledreport', 'shezar_reportbuilder'));
            $mform->addGroup($elements, 'addanewscheduledreport', get_string('addanewscheduledreport', 'shezar_reportbuilder'), '');
        }
    }
}
