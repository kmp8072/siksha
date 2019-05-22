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
 * @author Sam Hemelryk <sam.hemelryk@shezarlearning.com>
 * @author Alastair Munro <alastair.munro@shezarlearning.com>
 * @package shezar_program
 */

namespace shezar_program\user_learning;

defined('MOODLE_INTERNAL') || die();

use \core_course\user_learning\item as core_course;
use \shezar_core\user_learning\designation_subitem;

class course extends core_course {

    use designation_subitem;

    public $duedate;
    public $points;

    /**
     * Gets the points this course.
     *
     * @param courseset $set The courseset used to determine the coursesumfield
     * @return int Number of point
     */
    public function get_points(courseset $set) {
        if ($this->points !== null) {
            return $this->points;
        }

        if (empty($set->coursesumfield)) {
            return false;
        }

        $sumfield = customfield_get_field_instance($this->learningitemrecord, $set->coursesumfield, 'course', 'course');
        if ($sumfield) {
            $this->points += (int)$sumfield->display_data();
        }
        return $this->points;
    }
}
