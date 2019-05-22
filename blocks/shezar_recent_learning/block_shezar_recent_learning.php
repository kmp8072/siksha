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
 * @author Alastair Munro <alastair.munro@@shezarlms.com>
 * @package shezar
 * @subpackage shezar_recent_learning
 */

require_once("{$CFG->libdir}/completionlib.php");
/**
 * Recent learning block
 *
 * Displays recent completed courses
 */
class block_shezar_recent_learning extends block_base {

    public function init() {
        $this->title   = get_string('recentlearning', 'block_shezar_recent_learning');
        $this->version = 2010112300;
    }

    public function get_content() {
        global $USER, $DB, $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $completions = completion_info::get_all_courses($USER->id);

        list($visibilitysql, $visibilityparams) = shezar_visibility_where($USER->id, 'c.id', 'c.visible', 'c.audiencevisible');
        $params = array('userid' => $USER->id, 'roleid' => $CFG->learnerroleid);
        $params = array_merge($params, $visibilityparams);

        $sql = "SELECT c.id,c.fullname, MAX(ra.timemodified)
            FROM {role_assignments} ra
            INNER JOIN {context} ctx
                ON ra.contextid = ctx.id
                AND ctx.contextlevel = " . CONTEXT_COURSE . "
            LEFT JOIN {course} c
                ON ctx.instanceid = c.id
            WHERE ra.userid = :userid
            AND ra.roleid = :roleid
            AND {$visibilitysql}
            GROUP BY c.id, c.fullname
            ORDER BY MAX(ra.timemodified) DESC";

        $courses = $DB->get_records_sql($sql, $params);
        if (!$courses) {
            $this->content->text = get_string('norecentlearning', 'block_shezar_recent_learning');
            return $this->content;
        }
        if ($courses) {
            $table = new html_table();
            $table->attributes['class'] = 'recent_learning';

            foreach ($courses as $course) {
                $id = $course->id;
                $name = format_string($course->fullname);
                $status = array_key_exists($id, $completions) ? $completions[$id]->status : null;
                $completion = shezar_display_course_progress_icon($USER->id, $course->id, $status);
                $link = html_writer::link(new moodle_url('/course/view.php', array('id' => $id)), $name, array('title' => $name));
                $cell1 = new html_table_cell($link);
                $cell1->attributes['class'] = 'course';
                $cell2 = new html_table_cell($completion);
                $cell2->attributes['class'] = 'status';
                $table->data[] = new html_table_row(array($cell1, $cell2));
            }
            $this->content->footer = html_writer::link(new moodle_url('/shezar/plan/record/courses.php', array('userid' => $USER->id)), get_string('allmycourses', 'shezar_core'));
        }

        $this->content->text = html_writer::table($table);
        return $this->content;
    }
}
