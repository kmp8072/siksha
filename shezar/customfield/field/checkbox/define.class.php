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
class customfield_define_checkbox extends customfield_define_base {

    function define_form_specific(&$form) {
        /// select whether or not this should be checked by default
        $form->addElement('selectyesno', 'defaultdata', get_string('defaultchecked', 'shezar_customfield'));
        $form->setDefault('defaultdata', 0); // defaults to 'no'
        $form->setType('defaultdata', PARAM_BOOL);
        $form->addHelpButton('defaultdata', 'customfielddefaultdatacheckbox', 'shezar_customfield');
    }
}
