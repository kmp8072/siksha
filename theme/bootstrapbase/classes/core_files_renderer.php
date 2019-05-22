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
 * @package   theme_bootstrapbase
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/files/renderer.php");

class theme_bootstrapbase_core_files_renderer extends core_files_renderer {
    /**
     * Returns all FileManager JavaScript templates as an array.
     *
     * @return array
     */
    public function filemanager_js_templates() {
        $templates = parent::filemanager_js_templates();

        $templates['fileselectlayout'] = str_replace('form-group', 'control-group clearfix', $templates['fileselectlayout']);
        $templates['fileselectlayout'] = str_replace(' col-md-4', '', $templates['fileselectlayout']);
        $templates['fileselectlayout'] = str_replace(' col-md-8', '', $templates['fileselectlayout']);

        return $templates;
    }

    /**
     * Returns all FilePicker JavaScript templates as an array.
     *
     * @return array
     */
    public function filepicker_js_templates() {
        $templates = parent::filepicker_js_templates();

        $templates['selectlayout'] = str_replace('form-group', 'control-group clearfix', $templates['selectlayout']);
        $templates['selectlayout'] = str_replace(' col-md-4', '', $templates['selectlayout']);
        $templates['selectlayout'] = str_replace(' col-md-8', '', $templates['selectlayout']);

        $templates['uploadform'] = str_replace('form-group', 'control-group clearfix', $templates['uploadform']);
        $templates['uploadform'] = str_replace(' col-md-4', '', $templates['uploadform']);
        $templates['uploadform'] = str_replace(' col-md-8', '', $templates['uploadform']);

        $templates['loginform'] = str_replace('form-group', 'control-group clearfix', $templates['loginform']);
        $templates['loginform'] = str_replace(' col-md-4', '', $templates['loginform']);
        $templates['loginform'] = str_replace(' col-md-8', '', $templates['loginform']);

        return $templates;
    }
}