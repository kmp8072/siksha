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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_form
 */

namespace shezar_form;

/**
 * Base class for form controllers.
 *
 * @package   shezar_form
 * @copyright 2016 shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@shezarlms.com>
 */
abstract class form_controller {
    /**
     * This method is responsible for:
     *  - access control
     *  - getting and very strict validation of parameters
     *  - getting of current data
     *  - constructing of idsuffix if missing on first access
     *
     * and returning of the form instance usable in ajax requests.
     *
     * @param string $idsuffix string extra for identifier to allow repeated forms on one page
     * @return form
     */
    abstract public function get_ajax_form_instance($idsuffix);

    /**
     * Process the submitted form.
     *
     * @return array processed data
     */
    abstract public function process_ajax_data();
}
