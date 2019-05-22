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
 * @subpackage shezar_plan
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

class dp_manager_role extends dp_base_role {
    function user_has_role($userid=null) {
        global $USER;
        // use current user if none given
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        $context = context_system::instance();

        // Are they the manager of this plan's owner?
        if (\shezar_job\job_assignment::is_managing($userid, $this->plan->userid) && has_capability('shezar/plan:accessplan', $context, $userid)) {
            return 'manager';
        // Are they an administrative super-user?
        } else if (has_capability('shezar/plan:accessanyplan', $context, $userid)
                    || has_capability('shezar/plan:manageanyplan', $context, $userid)) {
            return 'manager';
        } else {
            return false;
        }
    }
}
