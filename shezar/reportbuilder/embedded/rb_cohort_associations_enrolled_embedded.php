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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package shezar
 * @subpackage cohort
 */
global $CFG;
require_once($CFG->dirroot.'/cohort/lib.php');

class rb_cohort_associations_enrolled_embedded extends rb_base_embedded {
    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;

    public function __construct($data) {
        $this->url = '/shezar/cohort/enrolledlearning.php';
        $this->source = 'cohort_associations';
        $this->shortname = 'cohort_associations_enrolled';
        $this->fullname = get_string('cohortenrolledreportname', 'shezar_cohort');
        $this->columns = array(
            array(
                'type' => 'associations',
                'value' => 'nameiconlink',
                'heading' => get_string('associationname', 'shezar_cohort')
            ),
            array(
                'type' => 'associations',
                'value' => 'type',
                'heading' => get_string('associationtype', 'shezar_cohort')
            ),
            array(
                'type' => 'associations',
                'value' => 'programcompletionlink',
                'heading' => get_string('assignmentduedate', 'shezar_program')
            ),
            array(
                'type' => 'associations',
                'value' => 'programviewduedateslink',
                'heading' => get_string('actualduedate', 'shezar_program')
            ),
            array(
                'type' => 'associations',
                'value' => 'actionsenrolled',
                'heading' => get_string('associationactionsenrolled', 'shezar_cohort')
            ),
        );

        // no filters
        $this->filters = array(
            array(
                'type' => 'associations',
                'value' => 'name',
                'advanced' => 0,
            ),
            array(
                'type' => 'associations',
                'value' => 'type',
                'advanced' => 0,
            ),
        );

        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $this->embeddedparams = array();
        if (array_key_exists('cohortid', $data)) {
            $this->embeddedparams['cohortid'] = $data['cohortid'];
        }
        $this->embeddedparams['value'] = COHORT_ASSN_VALUE_ENROLLED;

        parent::__construct();
    }

    /**
     * Check if the user is capable of accessing this report.
     * We use $reportfor instead of $USER->id and $report->get_param_value() instead of getting report params
     * some other way so that the embedded report will be compatible with the scheduler (in the future).
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        global $DB;

        if (isset($this->embeddedparams['cohortid'])) {
            $cohort = $DB->get_record('cohort', array('id' => $this->embeddedparams['cohortid']));
            $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
        } else {
            $context = context_system::instance();
        }

        return has_capability('moodle/cohort:view', $context, $reportfor);
    }
}
