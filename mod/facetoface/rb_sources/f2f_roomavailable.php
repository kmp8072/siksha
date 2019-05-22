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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/facetoface/rb_sources/f2f_available.php');

/**
 * Empty rooms during specified time search implementation
 */
class rb_filter_f2f_roomavailable extends rb_filter_f2f_available {
    public function get_sql_snippet($sessionstarts, $sessionends) {
        $paramstarts = rb_unique_param('timestart');
        $paramends = rb_unique_param('timefinish');

        $field = $this->get_field();
        $sql = "$field NOT IN (
            SELECT fr.id
              FROM {facetoface_room} fr
              JOIN {facetoface_sessions_dates} fsd ON fsd.roomid = fr.id
             WHERE fr.allowconflicts = 0 AND :{$paramends} > fsd.timestart AND fsd.timefinish > :{$paramstarts}
             )";

        $params = array();
        $params[$paramstarts] = $sessionstarts;
        $params[$paramends] = $sessionends;

        return array($sql, $params);
    }
}
