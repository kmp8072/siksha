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
 * @package shezar_dashboard
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/shezar/dashboard/lib.php');
require_once($CFG->dirroot . '/shezar/core/lib.php');

/**
 * Dashboard generator.
 */
class shezar_dashboard_generator extends component_generator_base {
    protected static $ind = 0;

    /**
     * Creates dashboard.
     * All parameter keys are optional.
     *
     * @param array('name' => Name of dashboard, 'locked' => bool, 'pusblished' => bool, 'cohorts' => array('cohortid', ...))
     * @return shezar_dashboard instance
     */
    public function create_dashboard(array $data = array()) {
        global $DB;
        $dashboard = new shezar_dashboard();
        if (!isset($data['name'])) {
            $data['name'] = 'Test' . self::$ind++;
        }
        if (!isset($data['locked'])) {
            $data['locked'] = false;
        }
        if (!isset($data['published'])) {
            $data['published'] = true;
        }
        if (isset($data['cohorts'])) {
            $cohorts = $data['cohorts'];
            if (!is_array($data['cohorts'])) {
                $cohorts = explode(', ', $data['cohorts']);
            }
            $data['cohorts'] = array();
            foreach ($cohorts as $cohort) {
                $cohort = trim($cohort);
                if ($cohort == '') {
                    continue;
                }
                if ((string)intval($cohort) == $cohort) {
                    $data['cohorts'][] = (int)$cohort;
                } else {
                    // Convert cohort name to id.
                    $record = $DB->get_record_select('cohort', 'name = ? OR idnumber = ?', array($cohort, $cohort));
                    $data['cohorts'][] = $record->id;
                }
            }
        }
        $dashboard->set_from_form((object)$data)->save();

        return $dashboard;
    }
}
