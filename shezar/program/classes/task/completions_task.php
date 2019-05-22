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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_program
 */

namespace shezar_program\task;

class completions_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('completionstask', 'shezar_program');
    }

    /**
     * Determine whether or not any users have completed any programs
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Don't run programs cron if programs and certifications are disabled.
        if (shezar_feature_disabled('programs') &&
            shezar_feature_disabled('certifications')) {
            return false;
        }

        // Get all programs.
        $program_records = $DB->get_records('prog');

        foreach ($program_records as $program_record) {

            $program = new \program($program_record->id);

            // Get all the users enrolled on this program.
            $program_users = $program->get_program_learners();

            if (count($program_users) == 0) {
                continue;
            }

            if (!empty($program_users)) {
                foreach ($program_users as $userid) {
                    prog_update_completion($userid, $program);
                }
            }
        }
    }
}

