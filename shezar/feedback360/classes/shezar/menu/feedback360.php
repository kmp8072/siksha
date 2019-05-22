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

namespace shezar_feedback360\shezar\menu;

use \shezar_core\shezar\menu\menu as menu;

class feedback360 extends \shezar_core\shezar\menu\item {

    protected function get_default_title() {
        return get_string('feedback360', 'shezar_feedback360');
    }

    protected function get_default_url() {
        return '/shezar/feedback360/index.php';
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    public function get_default_sortorder() {
        return 43000;
    }

    protected function check_visibility() {
        global $CFG, $USER;
        static $cache = null;

        if (!shezar_feature_visible('feedback360')) {
            $cache = null;
            return menu::HIDE_ALWAYS;
        }

        if (isset($cache)) {
            return $cache;
        }

        require_once($CFG->dirroot . '/shezar/feedback360/lib.php');
        if (\feedback360::can_view_feedback360s($USER->id)) {
            $cache = menu::SHOW_ALWAYS;
        } else {
            $cache = menu::HIDE_ALWAYS;
        }
        return $cache;
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return shezar_feature_disabled('feedback360');
    }

    protected function get_default_parent() {
        return '\shezar_appraisal\shezar\menu\appraisal';
    }
}
