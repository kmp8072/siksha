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
 * @author Dan Marsden <dan@catalyst.net.nz>
 * @package shezar
 * @subpackage blocks_shezar_stats
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

class block_shezar_stats_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $USER;
        $mform = $this->_form;
        $stats = shezar_stats_manager_stats($USER);
        foreach ($stats as $stat) {
            $varname = 'config_'.$stat->string;
            $mform->addElement('header', $varname.'_header', get_string($stat->string.'_config', 'block_shezar_stats'));
            $mform->addElement('advcheckbox', $varname, get_string('enable'));
            $mform->setDefault($varname, 1);
            if ($stat->string == 'statlearnerhours') {
                $mform->addElement('text', 'config_statlearnerhours_hours', get_string('statlearnerhours_confighours', 'block_shezar_stats'), array('size' => '2'));
                $mform->setDefault('config_statlearnerhours_hours', 12);
                $mform->setType('config_statlearnerhours_hours', PARAM_INT);
            }
        }
    }
}
