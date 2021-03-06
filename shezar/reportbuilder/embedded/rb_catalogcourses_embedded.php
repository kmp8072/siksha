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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

require_once($CFG->dirroot.'/shezar/reportbuilder/embedded/rb_findcourses_embedded.php');

class rb_catalogcourses_embedded extends rb_base_embedded {
    public function __construct($data) {
        $this->url = '/shezar/coursecatalog/courses.php';
        $this->source = 'courses';
        $this->shortname = 'catalogcourses';
        $this->fullname = get_string('catalogcourses', 'shezar_coursecatalog');
        $this->defaultsortcolumn = 'course_courselinkicon';

        $this->columns = array(
            array(
                'type' => 'course',
                'value' => 'courseexpandlink',
                'heading' => get_string('coursename', 'shezar_reportbuilder')
            ),
            array(
                'type' => 'course',
                'value' => 'startdate',
                'heading' => get_string('report:startdate', 'shezar_reportbuilder')
            ),
            array(
                'type' => 'course',
                'value' => 'mods',
                'heading' => get_string('content', 'shezar_reportbuilder')
            )
        );

        $this->filters = array(
            array(
                'type' => 'course',
                'value' => 'coursetype',
                'region' => rb_filter_type::RB_FILTER_REGION_SIDEBAR,
                'fieldname' => get_string('type', 'shezar_reportbuilder')
            ),
            array(
                'type' => 'course',
                'value' => 'mods',
                'region' => rb_filter_type::RB_FILTER_REGION_SIDEBAR,
                'fieldname' => get_string('content', 'shezar_reportbuilder')
            )
        );

        $this->toolbarsearchcolumns = array(
            array(
                'type' => 'course',
                'value' => 'fullname'
            ),
            array(
                'type' => 'course',
                'value' => 'summary'
            )
        );

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * Hide this embedded report if feature disabled or hidden.
     * @return bool
     */
    public function is_ignored() {
        global $CFG;
        return empty($CFG->enhancedcatalog);
    }

    /**
     * Check if the user is capable of accessing this report.
     * We use $reportfor instead of $USER->id and $report->get_param_value() instead of getting report params
     * some other way so that the embedded report will be compatible with the scheduler (in the future).
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        return true;
    }

    /**
     * Returns true if require_login should be executed when the report is access through a page other than
     * report.php or an embedded report's webpage, e.g. through ajax calls.
     *
     * @return boolean True if require_login should be executed
     */
    public function needs_require_login() {
        global $CFG;
        return $CFG->forcelogin;
    }

    public function get_extrabuttons() {
        global $OUTPUT;

        $categoryid = shezar_get_categoryid_with_capability('moodle/course:create');

        $buttons = "";

        // Show the course request button, if it is enabled (returns empty string if not).
        ob_start();
        print_course_request_buttons(context_system::instance());
        $buttons .= ob_get_contents();
        ob_end_clean();

        if ($categoryid !== false) {
            $createurl = new moodle_url("/course/edit.php", array('category' => $categoryid));
            $createbutton = new single_button($createurl, get_string('addcourse', 'shezar_coursecatalog'), 'get');
            $buttons .= $OUTPUT->render($createbutton);
        }

        return $buttons;
    }
}
