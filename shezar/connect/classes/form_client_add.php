<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_connect
 */

defined('MOODLE_INTERNAL') || die();

use \shezar_connect\util;

require_once($CFG->dirroot . '/lib/formslib.php');

class shezar_connect_form_client_add extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('text', 'clientname', get_string('name'), 'size="70"');
        $mform->setType('clientname', PARAM_TEXT);
        $mform->addRule('clientname', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'clienturl', get_string('clienturl', 'shezar_connect'), 'size="100"');
        $mform->setType('clienturl', PARAM_URL);
        $mform->addRule('clienturl', $strrequired, 'required', null, 'server');
        $mform->addHelpButton('clienturl', 'clienturl', 'shezar_connect');

        $mform->addElement('text', 'setupsecret', get_string('clientsetupsecret', 'shezar_connect'), 'size="70"');
        $mform->setType('setupsecret', PARAM_ALPHANUM);
        $mform->addRule('setupsecret', $strrequired, 'required', null, 'client');
        $mform->addHelpButton('setupsecret', 'clientsetupsecret', 'shezar_connect');

        $cohorts = $DB->get_records_menu('cohort', array('contextid' => context_system::instance()->id), 'name ASC', 'id, name');
        $cohorts[0] = get_string('no');
        $mform->addElement('select', 'cohortid', get_string('restricttocohort', 'shezar_connect'), $cohorts);
        $mform->setDefault('cohortid', 0);
        $mform->addHelpButton('cohortid', 'restricttocohort', 'shezar_connect');

        $mform->addElement('advcheckbox', 'addnewcourses', get_string('addnewcourses', 'shezar_connect'));
        $mform->addHelpButton('addnewcourses', 'addnewcourses', 'shezar_connect');
        $mform->addElement('advcheckbox', 'addnewcohorts', get_string('addnewcohorts', 'shezar_connect'));
        $mform->addHelpButton('addnewcohorts', 'addnewcohorts', 'shezar_connect');

        $mform->addElement('header','cohortshdr', get_string('cohorts', 'shezar_connect'));
        $cohortsclass = new shezar_connect_cohorts(null);
        $cohortsclass->init_page_js();
        $cohortsclass->build_table();
        $mform->addElement('html', $cohortsclass->display(true));
        $mform->addElement('hidden', 'cohorts', '');
        $mform->setType('cohorts', PARAM_SEQUENCE);
        $mform->addElement('button', 'cohortsadd', get_string('cohortsadd', 'shezar_connect'));
        $mform->setExpanded('cohortshdr', false);

        $mform->addElement('header', 'courseshdr', get_string('courses', 'shezar_connect'));
        $coursesclass = new shezar_connect_courses(null);
        $coursesclass->init_page_js();
        $coursesclass->build_table();
        $mform->addElement('html', $coursesclass->display(true));
        $mform->addElement('hidden', 'courses', '');
        $mform->setType('courses', PARAM_SEQUENCE);
        $mform->addElement('button', 'coursesadd', get_string('coursesadd', 'shezar_connect'));
        $mform->setExpanded('courseshdr', false);

        $mform->addElement('header', '');

        $mform->addElement('textarea', 'clientcomment', get_string('comment', 'shezar_connect'));
        $mform->setType('clientcomment', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('clientadd', 'shezar_connect'));
    }

    public function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        $clienturl = rtrim($data['clienturl'], '/');
        if ($DB->record_exists('shezar_connect_clients', array('clienturl' => $clienturl, 'status' => util::CLIENT_STATUS_OK))) {
            $errors['clienturl'] = get_string('errorduplicateclient', 'shezar_connect');
        } else if ($clienturl === $CFG->wwwroot) {
            // Prevent strange attempts to connect to self.
            $errors['clienturl'] = get_string('errorclientadd', 'shezar_connect');
        }

        return $errors;
    }
}
