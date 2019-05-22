<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2014 onwards shezar Learning Solutions LTD
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
 * @author David Curry <david.curry@shezarlearning.com>
 * @package shezar_appraisal
 */


require_once($CFG->dirroot.'/shezar/job/classes/job_assignment.php');

use shezar_job\job_assignment;

/**
 * Make sure $param1 is json encoded for all aggregate questions.
 */
function appraisals_upgrade_clean_aggregate_params() {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    $aggregates = $DB->get_records('appraisal_quest_field', array('datatype' => 'aggregate'));

    foreach ($aggregates as $aggregate) {
        // We only need to fix comma deliminated strings, skip encoded params.
        if (strpos($aggregate->param1, ']') || strpos($aggregate->param1, '}')) {
            continue;
        }

        $param1 = str_replace('"', '', $aggregate->param1);
        $param1 = explode(',', $param1);
        $aggregate->param1 = json_encode($param1);

        $DB->update_record('appraisal_quest_field', $aggregate);
    }
}
