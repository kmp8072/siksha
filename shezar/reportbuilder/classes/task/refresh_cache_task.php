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
 * Generate cached reports
 */
class refresh_cache_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('refreshcachetask', 'shezar_reportbuilder');
    }


    /**
     * Generate cached reports
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
        require_once($CFG->dirroot . '/shezar/reportbuilder/groupslib.php');
        require_once($CFG->dirroot . '/shezar/core/lib/scheduler.php');

        if (isset($CFG->enablereportcaching) && $CFG->enablereportcaching == 0) {
            reportbuilder_purge_all_cache(true);
        }
        $caches = reportbuilder_get_all_cached();
        foreach ($caches as $cache) {
            // For disabled cache just ensure to remove cache table.
            if (!$cache->cache) {
                if ($cache->reportid) {
                    mtrace('Disable caching for report: ' . $cache->fullname);
                    reportbuilder_purge_cache($cache, true);
                }
                continue;
            }

            $schedule = new \scheduler($cache, array('nextevent' => 'nextreport'));
            if ($schedule->is_time()) {
                $schedule->next(time(), true, \core_date::get_server_timezone());

                mtrace("Caching report '$cache->fullname'...");
                $track_start = microtime(true);

                $result = reportbuilder_generate_cache($cache->reportid);

                if ($result) {
                    $t = sprintf ("%.2f", (microtime(true) - $track_start));
                    mtrace("report '$cache->fullname' done in $t seconds");
                } else {
                    mtrace("report '$cache->fullname' failed");
                }
            }

            if ($schedule->is_changed()) {
                if (!$cache->id) {
                    $DB->insert_record('report_builder_cache', $schedule->to_object());
                } else {
                    $DB->update_record('report_builder_cache', $schedule->to_object());
                }
            }
        }
    }
}
