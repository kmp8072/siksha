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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package block_shezar_stats
 */

namespace block_shezar_stats\task;

class update_shezar_stats_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updateshezarstatstask', 'block_shezar_stats');
    }


    /**
     * Preprocess report groups
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/shezar_stats/locallib.php');

        $lastrun = (int)get_config('block_shezar_stats', 'cronlastrun');
        if (empty($lastrun)) {
            // Set $lastrun to one month ago: (only process one month of historical stats).
            $lastrun = time() -(60*60*24*30);
        }
        if (time() > ($lastrun + (24*60*60))) {
            // If at least 24 hours since last run.
            require_once($CFG->dirroot.'/blocks/shezar_stats/locallib.php');
            $nextrun = time();
            $stats = shezar_stats_timespent($lastrun, $nextrun);
            foreach ($stats as $userid => $timespent) {
                // Insert daily stat for each user returned above into new stats table for reading.
                shezar_stats_add_event($nextrun, $userid, STATS_EVENT_TIME_SPENT, '', $timespent);
            }
            set_config('cronlastrun', $nextrun, 'block_shezar_stats');
        }
    }
}