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
 * @author Jon Sharp <jonathans@catalyst-eu.net>
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_certification
 */

namespace shezar_certification\task;

/**
 * Update learner assignments for active appraisals.
 */
class update_certification_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatecertificationstask', 'shezar_certification');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     *
     * @param boolean   $quiet  Whether or not we hide the mtraces.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/shezar/certification/lib.php');

        // Suppress output during tests.
        $quiet = PHPUNIT_TEST || defined('BEHAT_SITE_RUNNING');

        if (shezar_feature_disabled('certifications')) {
            return;
        }

        if (!$quiet) {
            mtrace("Checking for missing certif_completion records");
        }

        $processed = certification_fix_missing_certif_completions();

        if (!$quiet) {
            mtrace("... ".$processed.' processed');
            if ($processed > 0) {
                debugging("!WARNING! The number above should have been 0. Greater than 0 indicates that a problem\n" .
                    "occurred during user assignment. The records should now be repaired, but if this\n" .
                    "problem persists then it should be reported to the shezar support staff. !WARNING!");
            }
        }

        if (!$quiet) {
            mtrace("Doing recertify_window_opens_stage");
        }

        $processed = recertify_window_opens_stage();

        if (!$quiet) {
            mtrace("... ".$processed.' processed');
        }

        if (!$quiet) {
            mtrace("Doing recertify_window_abouttoclose_stage");
        }

        $processed = recertify_window_abouttoclose_stage();

        if (!$quiet) {
            mtrace("... ".$processed.' processed');
        }

        if (!$quiet) {
            mtrace("Doing recertify_expires_stage");
        }

        $processed = recertify_expires_stage();

        if (!$quiet) {
            mtrace("... ".$processed.' processed');
        }
    }
}
