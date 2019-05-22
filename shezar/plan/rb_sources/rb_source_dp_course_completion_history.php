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
 * @author Nathan Lewis <nathan.lewis@shezarlms.com>
 * @package shezar_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/shezar/reportbuilder/rb_sources/rb_source_course_completion_all.php');

class rb_source_dp_course_completion_history extends rb_source_course_completion_all {

    protected function define_base() {
        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $base = "{course_completion_history}";
        return $base;
    }

    protected function define_sourcetitle() {
        return get_string('sourcetitle', 'rb_source_dp_course_completion_history');
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public function is_ignored() {
        return !shezar_feature_visible('recordoflearning');
    }
}
