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

use \shezar_connect\util;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class shezar_connect_form_client_edit extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $client = $this->_customdata;

        $strrequired = get_string('required');

        $mform->addElement('text', 'clientname', get_string('name'), 'size="70"');
        $mform->setType('clientname', PARAM_TEXT);
        $mform->addRule('clientname', $strrequired, 'required', null, 'client');

        $types = array(
            '' => '',
            'shezarlms' => 'shezar LMS',
            'shezarsocial' => 'shezar Social',
        );
        $mform->addElement('select', 'clienttype', get_string('clienttype', 'shezar_connect'), $types);
        $mform->hardFreeze('clienttype');

        $mform->addElement('static', 'clienturl', get_string('url'));
        $mform->addHelpButton('clienturl', 'clienturl', 'shezar_connect');

        $cohorts = $DB->get_records_menu('cohort', array('contextid' => context_system::instance()->id), 'name ASC', 'id, name');
        $cohorts[0] = get_string('no');
        $mform->addElement('select', 'cohortid', get_string('restricttocohort', 'shezar_connect'), $cohorts);
        $mform->addHelpButton('cohortid', 'restricttocohort', 'shezar_connect');

        $mform->addElement('advcheckbox', 'addnewcourses', get_string('addnewcourses', 'shezar_connect'));
        $mform->addHelpButton('addnewcourses', 'addnewcourses', 'shezar_connect');
        $mform->addElement('advcheckbox', 'addnewcohorts', get_string('addnewcohorts', 'shezar_connect'));
        $mform->addHelpButton('addnewcohorts', 'addnewcohorts', 'shezar_connect');

        if ($client->status == util::CLIENT_STATUS_OK) {
            $mform->addElement('header', 'cohortshdr', get_string('cohorts', 'shezar_connect'));
            $cohortsclass = new shezar_connect_cohorts($client);
            $cohortsclass->init_page_js();
            $cohortsclass->build_table();
            $mform->addElement('html', $cohortsclass->display(true));
            $mform->addElement('button', 'cohortsadd', get_string('cohortsadd', 'shezar_connect'));
            $mform->addElement('hidden', 'cohorts', implode(',', array_keys($cohortsclass->get_cohorts('c.id'))));
            $mform->setType('cohorts', PARAM_SEQUENCE);
            $mform->setExpanded('cohortshdr', $cohortsclass->has_data());

            $mform->addElement('header', 'courseshdr', get_string('courses', 'shezar_connect'));
            $coursesclass = new shezar_connect_courses($client);
            $coursesclass->init_page_js();
            $coursesclass->build_table();
            $mform->addElement('html', $coursesclass->display(true));
            $mform->addElement('button', 'coursesadd', get_string('coursesadd', 'shezar_connect'));
            $mform->addElement('hidden', 'courses', implode(',', array_keys($coursesclass->get_courses('c.id'))));
            $mform->setType('courses', PARAM_SEQUENCE);
            $mform->setExpanded('courseshdr', $coursesclass->has_data());

            $mform->addElement('header', '');
        }

        $mform->addElement('textarea', 'clientcomment', get_string('comment', 'shezar_connect'));
        $mform->setType('clientcomment', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();

        $this->set_data($client);
    }
}
