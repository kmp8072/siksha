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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

require_once($CFG->dirroot.'/lib/formslib.php');

class dp_objective_scale_value_edit_form extends moodleform {

    // Define the form
    function definition() {
        global $TEXTAREA_OPTIONS;

        $mform =& $this->_form;
        $scaleid = $this->_customdata['scaleid'];
        /// Add some extra hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'objscaleid');
        $mform->setType('objscaleid', PARAM_INT);
        $mform->addElement('hidden', 'sortorder');
        $mform->setType('sortorder', PARAM_INT);

        /// Print the required moodle fields first
        $mform->addElement('header', 'moodle', get_string('general'));

        $mform->addElement('static', 'scalename', get_string('objectivescale', 'shezar_plan'));
        $mform->addHelpButton('scalename', 'objectivescaleassign', 'shezar_plan', '', true);

        $mform->addElement('text', 'name', get_string('objectivescalevaluename', 'shezar_plan'), 'maxlength="255" size="20"');
        $mform->addHelpButton('name', 'objectivescalevaluename', 'shezar_plan', '', true);
        $mform->addRule('name', get_string('missingobjectivescalevaluename', 'shezar_plan'), 'required', null, 'client');
        $mform->setType('name', PARAM_MULTILANG);

        $mform->addElement('text', 'idnumber', get_string('objectivescalevalueidnumber', 'shezar_plan'), 'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'objectivescalevalueidnumber', 'shezar_plan', '', true);
        $mform->setType('idnumber', PARAM_TEXT);

        $mform->addElement('text', 'numericscore', get_string('objectivescalevaluenumeric', 'shezar_plan'), 'maxlength="100"  size="10"');
        $mform->addHelpButton('numericscore', 'objectivescalevaluenumeric', 'shezar_plan', '', true);
        $mform->setType('numericscore', PARAM_NUMBER);
        $mform->addRule('numericscore', null, 'numeric', null, 'client');

        $note = (dp_objective_scale_is_used($scaleid)) ? html_writer::tag('div', get_string('achievedvaluefrozen', 'shezar_plan'), array('class' => 'notifymessage')) : '';
        $mform->addElement('advcheckbox', 'achieved', get_string('achieved', 'shezar_plan'), $note);
        $mform->addHelpButton('achieved', 'objectivescalevalueachieved', 'shezar_plan', '', true);
        if (dp_objective_scale_is_used($scaleid)) {
            $mform->hardFreeze('achieved');
        }

        $mform->addElement('editor', 'description_editor', get_string('description'), null, $TEXTAREA_OPTIONS);
        $mform->setType('description_editor', PARAM_CLEANHTML);

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = array();
        $data = (object)$data;

        if (!empty($data->idnumber) && shezar_idnumber_exists('dp_objective_scale_value', $data->idnumber, $data->id)) {
            $errors['idnumber'] = get_string('idnumberexists', 'shezar_core');
        }

        return $errors;
    }
}
