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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_dashboard
 */

namespace shezar_dashboard\shezar\menu;

use \shezar_core\shezar\menu\menu as menu;

class dashboard extends \shezar_core\shezar\menu\item {

    protected function get_default_title() {
        return get_string('dashboard', 'shezar_dashboard');
    }

    protected function get_default_url() {
        return '/shezar/dashboard/index.php';
    }

    public function get_default_sortorder() {
        return 20000;
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    protected function check_visibility() {
        global $CFG, $USER;

        if (!isloggedin() or isguestuser()) {
            return menu::HIDE_ALWAYS;
        }

        static $cache = null;
        if (isset($cache)) {
            return $cache;
        }

        require_once($CFG->dirroot . '/shezar/dashboard/lib.php');

        if (shezar_feature_visible('shezardashboard')
            && count(\shezar_dashboard::get_user_dashboards($USER->id))) {
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
        return shezar_feature_disabled('shezardashboard');
    }
}
