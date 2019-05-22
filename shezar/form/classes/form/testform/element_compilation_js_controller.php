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
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @package shezar_form
 */

namespace shezar_form\form\testform;

use shezar_form\form_controller;

/**
 * Controller for element_compilation_js
 *
 * @package   shezar_formexamples
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 */
class element_compilation_js_controller extends form_controller {

    /** @var element_compilation_js $form */
    protected $form;

    /**
     * This method is responsible for:
     *  - access control
     *  - getting of current data
     *  - getting of parameters
     *
     * and returning of the form instance.
     *
     * @param string $idsuffix string extra for identifier to allow repeated forms on one page
     * @return form
     */
    public function get_ajax_form_instance($idsuffix) {
        // Access control first.
        require_login();
        require_sesskey();
        $syscontext = \context_system::instance();
        require_capability('moodle/site:config', $syscontext);

        // Get the current data from id parameter.
        $currentdata = element_compilation_js::get_current_data_for_test();
        $currentdata['form_select'] = 'shezar_form\form\testform\element_compilation_js';

        // Create the form instance.
        $this->form = new element_compilation_js($currentdata, null, $idsuffix);

        return $this->form;
    }

    /**
     * Process the submitted form.
     *
     * @return array processed data
     */
    public function process_ajax_data() {
        $result = array();
        $result['data'] = (array)$this->form->get_data();
        if (isset($result['data']['datetime'])) {
            $result['data']['datetime'] = date('Y/m/d H:i', $result['data']['datetime']);
        }
        $result['files'] = array();

        $files = $this->form->get_files();
        foreach($files as $elname => $list) {
            $result['files'][$elname] = array();
            foreach ($list as $file) {
                /** @var \stored_file $file */
                if ($file->is_directory()) {
                    $path = $file->get_filepath();
                } else {
                    $path = $file->get_filepath() . $file->get_filename();
                }
                $result['files'][$elname][] = $path;
            }
        }

        return $result;
    }
}
