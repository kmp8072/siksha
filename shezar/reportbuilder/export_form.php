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
 * @subpackage reportbuilder
 */

/**
 * Formslib template for generating an export report form
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/lib/formslib.php');

class report_builder_export_form extends moodleform {

    /**
     * Definition of the export report form
     */
    function definition() {
        $mform =& $this->_form;

        $select = reportbuilder_get_export_options(null, true);

        if (count($select) == 0) {
            // No export options - don't show form.
            return false;
        } else if (count($select) == 1) {
            // No options - show a button.
            $mform->addElement('hidden', 'format', key($select));
            $mform->setType('format', PARAM_INT);
            $mform->addElement('submit', 'export', current($select));
        } else {
            // Show pulldown menu.
            $group=array();
            $group[] =& $mform->createElement('select', 'format', get_string('exportformat', 'shezar_core'), $select);
            $group[] =& $mform->createElement('submit', 'export', get_string('export', 'shezar_reportbuilder'));
            $mform->addGroup($group, 'exportgroup', get_string('exportas', 'shezar_reportbuilder'), array(' '), false);
        }

    }

}


