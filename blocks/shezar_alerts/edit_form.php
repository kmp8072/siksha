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
 * @author Yuliya Bozhko <yuliya.bozhko@shezarlms.com>
 * @package shezar
 * @subpackage block_shezar_alerts
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class block_shezar_alerts_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $options = array(0 => get_string('no'), 1 => get_string('yes'));
        $attributes = array();
        if (empty($CFG->block_shezar_alerts_showempty)) {
            $attributes['disabled'] = 'disabled';
        }

        $mform->addElement('select', 'config_showempty', get_string('showempty', 'block_shezar_alerts'), $options, $attributes);
        $mform->addHelpButton('config_showempty', 'showempty', 'block_shezar_tasks');
        $mform->setDefault('config_showempty', 0);
    }
}
