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
 * @subpackage shezar_customfield
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/shezar/customfield/definelib.php');
class customfield_define_text extends customfield_define_base {

    function define_form_specific(&$form) {
        /// Default data
        $form->addElement('text', 'defaultdata', get_string('defaultdata', 'shezar_customfield'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);
        $form->addHelpButton('defaultdata', 'customfielddefaultdatatext', 'shezar_customfield');

        /// Param 1 for text type is the size of the field
        $form->addElement('text', 'param1', get_string('fieldsize', 'shezar_customfield'), 'size="6"');
        $form->setDefault('param1', 30);
        $form->setType('param1', PARAM_INT);
        $form->addHelpButton('param1', 'customfieldfieldsizetext', 'shezar_customfield');

        /// Param 2 for text type is the maxlength of the field
        $form->addElement('text', 'param2', get_string('fieldmaxlength', 'shezar_customfield'), 'size="6"');
        $form->setDefault('param2', 2048);
        $form->setType('param2', PARAM_INT);
        $form->addHelpButton('param2', 'customfieldmaxlengthtext', 'shezar_customfield');

        // Param4 is storing settings as json.
        // Regex pattern validation.
        $form->addElement('text', 'regex', get_string('regexpattern', 'shezar_customfield'));
        $form->setType('regex', PARAM_TEXT);
        $form->addHelpButton('regex', 'regexpattern', 'shezar_customfield');

        // Param5 is regex pattern validation message.
        $form->addElement('text', 'param5', get_string('regexpatternmessage', 'shezar_customfield'), 'size="50"');
        $form->setType('param5', PARAM_TEXT);
        $form->addHelpButton('param5', 'regexpatternmessage', 'shezar_customfield');
    }

    function define_validate_specific($data, $files, $tableprefix) {
        $errors = parent::define_validate_specific($data, $files, $tableprefix);
        if (!empty($data->regex) && empty($errors['regex'])) {
            $errors = array_merge($errors, $this->validate_regex($data));
        }
        if (!empty($data->defaultdata) && empty($errors['regex']) && empty($errors['defaultdata'])) {
            $errors = array_merge($errors, $this->validate_defaultdata($data));
        }
        return $errors;
    }

    /**
     * Performs regex pattern validation
     * @param stdClass $data form data
     *
     * @return array with regex field validation error (if any)
     */
    protected function validate_regex(stdClass $data) {
        if (!preg_match("/^\/.*\/[i]?$/", $data->regex)) {
            return array('regex' => get_string('regexpatterndelimitererror', 'shezar_customfield'));
        }
        if (@preg_match($data->regex, null) === false) {
            return array('regex' => get_string('regexpatternerror', 'shezar_customfield'));
        }
        return array();
    }

    /**
     * Performs default data validation
     * @param stdClass $data form data
     *
     * @return array with regex field validation error (if any)
     */
    protected function validate_defaultdata(stdClass $data) {
        if (!empty($data->defaultdata) && !empty($data->regex) && !preg_match($data->regex, $data->defaultdata)) {
            $fieldname = get_string('defaultdata', 'shezar_customfield');
            return array('defaultdata' => get_string('regexvalidationfailed', 'shezar_customfield', $fieldname));
        }
        return array();
    }

    public function define_save_preprocess($data, $old = null) {
        if (!empty($data->regex)) {
            $data->param4 = json_encode(array('regex' => $data->regex));
        }
        return $data;
    }

    public function define_load_preprocess($data) {
        if (!empty($data->param4) && $param4 = json_decode($data->param4, true)) {
            $data->regex = !empty($param4['regex']) ? $param4['regex'] : '';
        }
        return $data;
    }

}
