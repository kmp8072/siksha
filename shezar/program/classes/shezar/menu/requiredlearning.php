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
 * shezar navigation edit page.
 *
 * @package    shezar
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@shezarlms.com>
 */

namespace shezar_program\shezar\menu;

use \shezar_core\shezar\menu\menu as menu;

/**
 * Class to store, render and manage the Required Learning Node
 *
 * @property-read int $userhaslearning;
 *
 * @package    shezar
 * @subpackage navigation
 */

class requiredlearning extends \shezar_core\shezar\menu\item {

    /**
    * @var bool Whether this user actually has current programs or certifications assigned.
    */
    protected $userhaslearning = false;

    /**
     * Constructor.
     *
     * @param object $node
     */
    public function __construct($node) {
        global $CFG, $USER;
        parent::__construct($node);
        require_once($CFG->dirroot . '/shezar/program/lib.php');
        $this->url = prog_get_tab_link($USER->id);
        if ($this->url === false) {
            $this->url = '/shezar/program/required.php';
        } else {
            $this->userhaslearning = true;
        }
    }

    protected function get_default_title() {
        return get_string('requiredlearningmenu', 'shezar_program');
    }

    protected function get_default_url() {
        return $this->url;
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    public function get_default_sortorder() {
        return 84000;
    }

    protected function check_visibility() {
        // Only show Required Learning if programs/certifications are enabled.
        // And if the user actually has programs or certifications assigned and active.
        if ($this->userhaslearning && (shezar_feature_visible('programs') || shezar_feature_visible('certifications'))) {
            return menu::SHOW_ALWAYS;
        } else {
            return menu::HIDE_ALWAYS;
        }
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return (shezar_feature_disabled('programs') && shezar_feature_disabled('certifications'));
    }

    protected function get_default_parent() {
        return '\shezar_core\shezar\menu\unused';
    }
}
