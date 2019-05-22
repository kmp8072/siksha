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
 * Block for displaying the last course accessed by the user.
 *
 * @package block_last_block_accessed
 * @author Rob Tyler <rob.tyler@shezarlearning.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Recent learning block
 *
 * Displays recent completed courses
 */
class block_last_course_accessed extends block_base {

    public function init() {
        $this->title = get_string('lastcourseaccessed', 'block_last_course_accessed');
    }

    public function get_content() {
        global $CFG, $DB, $USER;

        // Required for generating course completion progress bar.
        require_once("{$CFG->libdir}/completionlib.php");

        // If the content is already defined, return it.
        if ($this->content !== null) {
            return $this->content;
        }

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // The USER global has the data we need about last course access.
        // It will only exist if a course has been accessed. If it doesn't
        // exist, retrieve the data directly.
        if (!empty($USER->currentcourseaccess)) {
            $courseaccess = $USER->currentcourseaccess;
            // Get the data from the last course access.
            arsort($courseaccess);
            $timestamp = reset($courseaccess);
            $courseid = key($courseaccess);
        } else {
            $params = array('userid' => $USER->id);
            // Get the course data delivered in the right order with the latest first, so use get_records.
            $last_access = $DB->get_records('user_lastaccess', $params, 'timeaccess DESC', "courseid, timeaccess", 0, 1);

            if ($last_access) {
                // We should only have one record, so get the object from it.
                $last_access = reset($last_access);
                $courseid = $last_access->courseid;
                $timestamp = $last_access->timeaccess;
            }
        }

        if (!isset($courseid) || !isset($timestamp)) {
            return $this->content;
        }

        // Get the course and completion data for the course and user. Using a LEFT JOIN allows for
        // the possibility of no completion data, in which case we won't display the progress bar.
        $sql = "SELECT c.id, c.fullname, cc.status, " . context_helper::get_preload_record_columns_sql('ctx') . "
                FROM {course} c
                LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                LEFT JOIN {course_completions} cc ON c.id = cc.course AND cc.userid = :userid
                WHERE c.id = :courseid";
        $params = array('courseid' => $courseid, 'userid' => $USER->id, 'contextlevel' => CONTEXT_COURSE);
        $course = $DB->get_record_sql($sql, $params);

        if (!$course) {
            return $this->content;
        }

        // Get the text that describes when the course was last accessed.
        $last_accessed = \block_last_course_accessed\helper::get_last_access_text($timestamp);

        // As we have the instance from the database we can use it to set the context for format_string below.
        context_helper::preload_from_record($course);
        $context = context_course::instance($course->id);

        // Build the data object for the template.
        $templateobject = new stdClass();
        $templateobject->course_url = (string) new moodle_url('/course/view.php', array('id' => $courseid));
        $templateobject->course_name = format_string($course->fullname, true, $context);
        $templateobject->last_accessed = $last_accessed;

        // Set the class to be used depending on the length of the course name.
        if (\core_text::strlen($templateobject->course_name) > 200) {
            $templateobject->course_name_class = 'small';
        } else if (\core_text::strlen($templateobject->course_name) > 100) {
            $templateobject->course_name_class = 'medium';
        } else {
            $templateobject->course_name_class = 'large';
        }

        // Get the renderer so we can render templates.
        $renderer = $this->page->get_renderer('shezar_core');

        // If there's no status, there's no completion data, so no progress bar.
        if ($course->status) {
            $templateobject->progress_bar = $renderer->course_progress_bar($USER->id, $courseid, $course->status);
        }

        // Get the block content from the template.
        $this->content->text = $renderer->render_from_template('block_last_course_accessed/block', $templateobject);

        return $this->content;
    }
}
