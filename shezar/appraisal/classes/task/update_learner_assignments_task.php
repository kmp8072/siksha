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
 * @package shezar_appraisal
 */
namespace shezar_appraisal\task;

/**
 * Update learner assignments for active appraisals.
 */
class update_learner_assignments_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatelearnerassignmentstask', 'shezar_appraisal');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/shezar/appraisal/lib.php');

        $timenow = time();

        // Execute the cron if Appraisals are not disabled or static.
        if (shezar_feature_disabled('appraisals')) {
            return;
        }

        if (!empty($CFG->dynamicappraisals)) {
            // Update learner assignments for active appraisals.
            $appraisals = $DB->get_records('appraisal', array('status' => \appraisal::STATUS_ACTIVE));
            foreach ($appraisals as $app) {
                $appraisal = new \appraisal($app->id);
                $appraisal->check_assignment_changes();
            }
        }

        \shezar_appraisal_observer::send_scheduled($timenow);
    }
}
