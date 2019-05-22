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
 * Block for displaying user-defined links
 *
 * @package   shezar
 * @author    Alastair Munro <alastair.munro@shezarlms.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_shezar_quicklinks_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', get_string('maintitle', 'block_shezar_quicklinks'));
        $mform->setType('config_title', PARAM_MULTILANG);

        $quicklinks = $DB->get_records('block_quicklinks', array('block_instance_id' => $this->block->instance->id), 'displaypos');

        $content = '';
        $titlestr = get_string('linktitle', 'block_shezar_quicklinks');
        $urlstr = get_string('url', 'block_shezar_quicklinks');
        foreach ($quicklinks as $ql) {
            $content .=  html_writer::tag('tr', html_writer::tag('td', $ql->title) . html_writer::tag('td', $ql->url));
        }
        $mform->addElement('static', 'linkstable', get_string('links', 'block_shezar_quicklinks'),
            html_writer::tag('table', html_writer::tag('tr', html_writer::tag('td', $titlestr) . html_writer::tag('td', $urlstr)) . $content));

        $blockcontext = context_block::instance($this->block->instance->id);
        if (has_capability('block/shezar_quicklinks:manageownlinks', $blockcontext)) {
            $mform->addElement('static', 'managelinks', '', html_writer::link(new moodle_url('/blocks/shezar_quicklinks/managelinks.php',
                    array('blockinstanceid' => $this->block->instance->id)), get_string('managelinks', 'block_shezar_quicklinks')));
        }
    }
}
