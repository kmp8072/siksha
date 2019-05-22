<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@shezarlms.com>
 * @package   theme_standardshezarresponsive
 *
 * NOTE: this code is from Moodle block_settings_renderer class.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/blocks/settings/renderer.php");

class theme_bootstrapbase_block_settings_renderer extends block_settings_renderer {

    public function search_form(moodle_url $formtarget, $searchvalue) {
        $content = html_writer::start_tag('form', array('class'=>'adminsearchform', 'method'=>'get', 'action'=>$formtarget, 'role' => 'search'));
        $content .= html_writer::start_tag('div');
        $content .= html_writer::tag('label', s(get_string('searchinsettings', 'admin')), array('for'=>'adminsearchquery', 'class'=>'accesshide'));
        $content .= html_writer::empty_tag('input', array('id'=>'adminsearchquery', 'type'=>'text', 'name'=>'query', 'value'=>s($searchvalue)));
        $content .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>s(get_string('search'))));
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('form');
        return $content;
    }
}
