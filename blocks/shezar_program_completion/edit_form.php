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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package block
 * @subpackage shezar_program_completion
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

class block_shezar_program_completion_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG, $PAGE;

        require_once($CFG->dirroot.'/blocks/shezar_program_completion/locallib.php');

        $mform = $this->_form;

        // Javascript include.
        require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');
        local_js(array(
            shezar_JS_DIALOG,
            shezar_JS_TREEVIEW,
            shezar_JS_PLACEHOLDER
        ));

        $programids = isset($this->block->config->programids) ? $this->block->config->programids : '';
        $PAGE->requires->strings_for_js(array('addprograms'), 'block_shezar_program_completion');

        $PAGE->requires->js_call_amd('block_shezar_program_completion/edit', 'init',
            array('blockid' => $this->block->instance->id, 'programsselected' => $programids));

        $mform->addElement('text', 'config_title', get_string('title', 'block_shezar_program_completion'),
                array('size' => '25'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('text', 'config_titlelink', get_string('titlelink', 'block_shezar_program_completion'),
                array('size' => '25'));
        $mform->setType('config_titlelink', PARAM_URL);

        $objs = array();
        $objs[] = $mform->createElement('static', 'programselector', '',
            html_writer::tag('span', '', array('id' => 'programtitle', 'class' => 'dialog-result-title')));
        $objs[] = $mform->createElement('static', 'selectorbutton',
            '',
            html_writer::empty_tag('input', array('type' => 'button',
                'class' => '',
                'value' => get_string('addprograms', 'block_shezar_program_completion'),
                'id' => 'add-block-programs-dialog')));

        $mform->addElement('group', 'program_grp', get_string('programs', 'block_shezar_program_completion'), $objs, '',
                false);

        $progcompletions = new block_shezar_program_completion_programs($this->block->instance->id);
        $mform->addElement('html', $progcompletions->display(true));

        $mform->addElement('advcheckbox', 'config_shownotassigned',
                get_string('shownotassigned', 'block_shezar_program_completion'));
        $mform->addHelpButton('config_shownotassigned', 'shownotassigned', 'block_shezar_program_completion');

        $mform->addElement('text', 'config_maxshow', get_string('maxshow', 'block_shezar_program_completion'),
                array('size' => '5'));
        $mform->addHelpButton('config_maxshow', 'maxshow', 'block_shezar_program_completion');
        $mform->setType('config_maxshow', PARAM_INT);

        $mform->addElement('hidden', 'blockid', $this->block->instance->id);
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('hidden', 'config_programids', $programids);
        $mform->setType('config_programids', PARAM_SEQUENCE);

    }
}
