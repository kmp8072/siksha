<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(__FILE__) . '/../config.php');

/**
 * In 9.0 the my learning page has been removed from shezar and replaced
 * by a dashboard. This page therefore just redirects to dashboards (if enabled) in
 * case there are any stray links left about. See TL-8515 for more details.
 */

require_login();

$redirecturl = shezar_feature_disabled('shezardashboard') ? new moodle_url('/') :
    new moodle_url('/shezar/dashboard/manage.php');

debugging('The My Learning page has been removed. Existing My Learning content has been moved to a hidden dashboard called "Legacy My Learning".');

redirect($redirecturl);
