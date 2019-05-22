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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_reportbuilder
 */

namespace shezar_reportbuilder\task;

/**
 * Process Scheduled reports
 */
class process_scheduled_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('processscheduledtask', 'shezar_reportbuilder');
    }


    /**
     * Process Scheduled reports
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
        require_once($CFG->dirroot . '/shezar/reportbuilder/groupslib.php');
        require_once($CFG->dirroot . '/shezar/core/lib/scheduler.php');

        require_once($CFG->dirroot . '/calendar/lib.php');

        $sql = "SELECT rbs.*, rb.fullname
                FROM {report_builder_schedule} rbs
                JOIN {report_builder} rb
                ON rbs.reportid = rb.id
                JOIN {user} u
                ON rbs.userid = u.id AND u.deleted = 0 AND u.suspended = 0";

        $scheduledreports = $DB->get_records_sql($sql);

        mtrace('Processing ' . count($scheduledreports) . ' scheduled reports');

        // If exporting to file is turned off at system level, do not save reports.
        $exportsetting = get_config('reportbuilder', 'exporttofilesystem');
        if ($exportsetting == 0) {
            mtrace('Exporting of scheduled reports to file system is disabled');
        }

        // Make sure there are no stale reportbuilder caches in SESSION.
        cron_setup_user('reset');

        foreach ($scheduledreports as $report) {
            // Set the next report time if its not yet set.
            $schedule = new \scheduler($report, array('nextevent' => 'nextreport'));

            if ($schedule->is_time()) {
                $user = $DB->get_record('user', array('id' => $report->userid), '*', MUST_EXIST);
                $tz = \core_date::get_user_timezone($user);
                $schedule->next(time(), true, $tz);

                // Hack $USER - includes current language change, $PAGE init, etc.
                cron_setup_user($user);

                // Send email or save report.
                reportbuilder_send_scheduled_report($report);

                // Reset $USER and $SESSION.
                cron_setup_user('reset');

                // Store the next time to run this scheduled report.
                $DB->update_record('report_builder_schedule', $schedule->to_object());

                // Release memory if possible.
                gc_collect_cycles();

            } else if ($schedule->is_changed()) {
                $DB->update_record('report_builder_schedule', $schedule->to_object());
            }
        }
    }
}
