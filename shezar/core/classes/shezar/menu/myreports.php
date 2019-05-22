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
namespace shezar_core\shezar\menu;

use \shezar_core\shezar\menu\menu as menu;

class myreports extends \shezar_core\shezar\menu\item {

    protected function get_default_title() {
        return get_string('reports', 'shezar_core');
    }

    protected function get_default_url() {
        return '/my/reports.php';
    }

    public function get_default_visibility() {
        return menu::SHOW_WHEN_REQUIRED;
    }

    public function get_default_sortorder() {
        return 60000;
    }

    protected function check_visibility() {
        global $CFG;

        static $cache = null;
        if (isset($cache)) {
            return $cache;
        }

        require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
        $reportbuilder_permittedreports = \reportbuilder::get_user_permitted_reports();
        $hasreports = (is_array($reportbuilder_permittedreports) && (count($reportbuilder_permittedreports) > 0));
        if ($hasreports) {
            $cache = menu::SHOW_ALWAYS;
        } else {
            $cache = menu::HIDE_ALWAYS;
        }
        return $cache;
    }
}
