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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/shezar/dashboard/lib.php');
/**
 * Dashboard block
 *
 * Displays dashboards menu
 */
class block_shezar_dashboard extends block_base {

    public function init() {
        $this->title   = get_string('pluginname', 'block_shezar_dashboard');
    }

    public function get_content() {
        global $USER;

        if (!shezar_feature_visible('shezardashboard')) {
            return '';
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $dashboards = shezar_dashboard::get_user_dashboards($USER->id);

        // Don't show the block if it has only one item (or none at all).
        if (count($dashboards) < 2) {
            return null;
        }

        $renderer = $this->page->get_renderer('block_shezar_dashboard');
        $this->content->text = $renderer->display_dashboards($dashboards, $this->get_current_id());
        return $this->content;
    }

    /**
     * Get dashboard id currently viewed by user.
     */
    public function get_current_id() {
        global $PAGE;
        if (substr($PAGE->pagetype, 0, 20) == 'my-shezar-dashboard-') {
            return (int)substr($PAGE->pagetype, 20);
        }
        return false;
    }
}
