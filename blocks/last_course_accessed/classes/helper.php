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
 * @package block_last_course_accessed
 * @author Rob Tyler <rob.tyler@shezarlearning.com>
 */

namespace block_last_course_accessed;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Using a timestamp return a natural language string describing the
     * timestamp relative to the current time provided by the web server.
     *
     * @param integer $timestamp Describes the last access time in a timestamp.
     * @param integer $compare_to Describes what time the comparison should be made against.
     * @return string Natural language string describing the time difference.
     */
    public static function get_last_access_text ($timestamp, $compare_to = null) {

        if (!$compare_to) {
            $compare_to = time();
        }

        // Get a nice natural language string that says when the course was last accessed.
        if ($timestamp >= strtotime('-5 minutes', $compare_to)) {
            $last_accessed = get_string('last_access_five_minutes', 'block_last_course_accessed');
        } else if ($timestamp >= strtotime('-30 minutes', $compare_to)) {
            $last_accessed = get_string('last_access_half_hour', 'block_last_course_accessed');
        } else if ($timestamp >= strtotime('-1 hour', $compare_to)) {
            $last_accessed = get_string('last_access_hour', 'block_last_course_accessed');
        } else if ($timestamp >= strtotime('today', $compare_to)) {
            $last_accessed = date_format_string($timestamp, get_string('strftimetodayattime', 'core_langconfig'));
        } else if ($timestamp >= strtotime('yesterday', $compare_to)) {
            $last_accessed = date_format_string($timestamp, get_string('strftimeyesterdayattime', 'core_langconfig'));
        } else if ($timestamp >= strtotime('-1 week', $compare_to)) {
            $last_accessed = date_format_string($timestamp, get_string('strftimedayattime', 'core_langconfig'));
        } else {
            $last_accessed = date_format_string($timestamp, get_string('strftimedaydateattime', 'core_langconfig'));
        }

        return $last_accessed;
    }

}