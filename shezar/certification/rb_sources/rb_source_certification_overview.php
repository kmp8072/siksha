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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/shezar/certification/lib.php');
require_once($CFG->dirroot . '/shezar/program/rb_sources/rb_source_program_overview.php');

class rb_source_certification_overview extends rb_source_program_overview {

    /**
     * Overwrite instance type value of shezar_visibility_where() in rb_source_program->post_config().
     */
    protected $instancetype = 'certification';

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        parent::__construct($groupid, $globalrestrictionset);

        // Global Report Restrictions are applied in rb_source_program_overview and work for rb_source_certification_overview
        // as well.

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_certification_overview');
        $this->sourcewhere = $this->define_sourcewhere();
    }
    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_sourcewhere() {
        // Only consider whole certifications - not courseset completion.
        $sourcewhere = 'base.coursesetid = 0';

        // Exclude programs (they have their own source).
        $sourcewhere .= ' AND (program.certifid IS NOT NULL)';

        return $sourcewhere;
    }

    protected function define_joinlist() {
        $joinlist = parent::define_joinlist();

        /* Psuedo Explaination:
         *
         * if (window is open) {
         *      Use current record
         * } else {
         *      if (history record exists) {
         *          use history certifpath
         *      } else {
         *          default to certif
         *      }
         * }
         */
        $now = time();
        $path = CERTIFPATH_CERT;
        $joinlist[] = new rb_join(
            'prog_courseset',
            'INNER',
            '{prog_courseset}',
            "prog_courseset.programid = base.programid
            AND base.coursesetid = 0
            AND (
                   (certif_completion.timewindowopens < {$now} AND prog_courseset.certifpath = certif_completion.certifpath)
                OR (certif_completion.timewindowopens > {$now} AND history.certifpath IS NOT NULL AND prog_courseset.certifpath = history.certifpath)
                OR (certif_completion.timewindowopens > {$now} AND history.certifpath IS NULL AND prog_courseset.certifpath = {$path})
            )
            ",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            array('base', 'certif_completion', 'history')
        );

        $joinlist[] = new rb_join(
            'certif_completion',
            'INNER',
            '{certif_completion}',
            "certif_completion.userid = base.userid AND certif_completion.certifid = program.certifid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'program'
        );

        $joinlist[] = new rb_join(
            'history',
            'LEFT',
            '{certif_completion_history}',
            "certif_completion.userid = history.userid
             AND certif_completion.certifid = history.certifid
             AND history.timecompleted = (SELECT MAX(timecompleted)
                                            FROM {certif_completion_history} cch
                                           WHERE cch.userid = history.userid
                                             AND cch.certifid = history.certifid)",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'certif_completion'
        );

        $this->add_certification_table_to_joinlist($joinlist, 'program', 'certifid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $columnoptions = parent::define_columnoptions();

        $this->add_certification_fields_to_columns($columnoptions, 'certif', 'shezar_certification');

        // Override the parent (program) timeduenice columnoption
        foreach ($columnoptions as $i => $co) {
            if ($co->value == 'timeduenice') {
                $timedueindex = $i;
                break;
            }
        }
        unset($columnoptions[$timedueindex], $timedueindex);
        $columnoptions[] = new rb_column_option(
            'program_completion',
            'timeduenice',
            get_string('duedateextra', 'rb_source_program_overview'),
            'base.timedue',
            array(
                'joins' => array('base', 'certif_completion'),
                'displayfunc' => 'programduedate',
                'extrafields' => array(
                    'status' => 'certif_completion.status',
                    'programid' => 'base.programid',
                    'certifpath' => 'certif_completion.certifpath',
                    'certifstatus' => 'certif_completion.status',
                    'userid' => 'base.userid',
                )
            )
        );

        // Certification path col.
        $columnoptions[] = new rb_column_option(
            'certif_completion',
            'certifpath',
            get_string('certifpath', 'rb_source_certification_overview'),
            'certif_completion.certifpath',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certif_certifpath'
            )
        );

        $columnoptions[] = new rb_column_option(
            'certif_completion',
            'status',
            get_string('status', 'rb_source_dp_certification'),
            'certif_completion.status',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certif_status',
                'extrafields' => array(
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'certif_completion',
            'renewalstatus',
            get_string('renewalstatus', 'rb_source_dp_certification'),
            'certif_completion.renewalstatus',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'certif_renewalstatus',
                'extrafields' => array(
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'certif_completion',
            'timewindowopens',
            get_string('timewindowopens', 'rb_source_dp_certification'),
            'certif_completion.timewindowopens',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'nice_date',
                'extrafields' => array(
                    'status' => 'certif_completion.status'
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'certif_completion',
            'timeexpires',
            get_string('timeexpires', 'rb_source_dp_certification'),
            'certif_completion.timeexpires',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'nice_date',
                'extrafields' => array(
                    'status' => 'certif_completion.status'
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'certif_completion',
            'timecompleted',
            get_string('timecompleted', 'rb_source_dp_certification'),
            'certif_completion.timecompleted',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'nice_date'
            )
        );

        $columnoptions[] = new rb_column_option(
            'certif_completion',
            'progress',
            get_string('programcompletionprogress', 'rb_source_program_overview'),
            $DB->sql_concat_join("'|'", array(sql_cast2char('prog_courseset.id'), sql_cast2char("prog_completion.status"))),
            array(
                'extrafields' => array(
                    'completion' => "certif_completion.timecompleted",
                    'window' => "certif_completion.timewindowopens",
                    'histpath' => "history.certifpath",
                    'histcomp' => "history.timecompleted",
                ),
                'displayfunc' => 'certif_completion_progress',
                'grouping' => 'comma_list',
                'joins' => array('prog_completion', 'certif_completion', 'history'),
                'nosort' => true,
            )
        );

        $columnoptions[] = new rb_column_option(
            'course',
            'shortname',
            get_string('courseshortname', 'rb_source_program_overview'),
            'COALESCE('.$DB->sql_concat('course.id', "'|'", 'course.shortname').', \'-\')',
            array(
                'joins' => 'course',
                'grouping' => 'sql_aggregate',
                'grouporder' => array(
                    'csorder'  => 'prog_courseset.sortorder',
                    'cscid'    => 'prog_courseset_course.id'
                ),
                'nosort' => true, // You can't sort concatenated columns.
                'displayfunc' => 'coursename_list',
                'style' => array('white-space' => 'pre'),
            )

        );

        $columnoptions[] = new rb_column_option(
            'course',
            'status',
            get_string('coursecompletionstatus', 'rb_source_program_overview'),
            sql_cast2char('COALESCE(course_completions.status, '.COMPLETION_STATUS_NOTYETSTARTED.')'),
            array(
                'joins' => 'course_completions',
                'grouping' => 'sql_aggregate',
                'grouporder' => array(
                    'csorder'  => 'prog_courseset.sortorder',
                    'cscid'    => 'prog_courseset_course.id'
                ),
                'nosort' => true, // You can't sort concatenated columns.
                'displayfunc' => 'course_status_list',
                'style' => array('white-space' => 'pre'),
            )

        );

        return $columnoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = parent::define_defaultcolumns();

        $defaultcolumns[] = array('type' => 'certif_completion', 'value' => 'certifpath');

        return $defaultcolumns;
    }

    protected function define_filteroptions() {
        $filteroptions = parent::define_filteroptions();

        $this->add_certification_fields_to_filters($filteroptions, 'shezar_certification');

        $filteroptions[] = new rb_filter_option(
            'certif_completion',
            'status',
            get_string('status', 'rb_source_dp_certification'),
            'select',
            array(
                'selectfunc' => 'certif_completion_status',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certif_completion',
            'renewalstatus',
            get_string('renewalstatus', 'rb_source_dp_certification'),
            'select',
            array(
                'selectfunc' => 'certif_completion_renewalstatus',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $filteroptions[] = new rb_filter_option(
            'certif_completion',
            'timecompleted',
            get_string('timecompleted', 'rb_source_dp_certification'),
            'date'
        );


        return $filteroptions;
    }

    function rb_display_certif_completion_progress($status, $row, $isexport = false) {
        global $PAGE;

        $now = time();
        $shezar_renderer = $PAGE->get_renderer('shezar_core');

        if ($row->window < $now) {
            // The window is open, use the current record.
            $completions = array();
            $tempcompletions = explode(', ', $status);

            foreach ($tempcompletions as $completion) {
                $coursesetstatus = explode("|", $completion);
                if (isset($coursesetstatus[1])) {
                    $completions[$coursesetstatus[0]] = $coursesetstatus[1];
                } else {
                    $completions[$coursesetstatus[0]] =  STATUS_COURSESET_INCOMPLETE;
                }
            }

            $cnt = count($completions);
            if ($cnt == 0) {
                return '-';
            }
            $complete = 0;

            foreach ($completions as $comp) {
                if ($comp == STATUS_COURSESET_COMPLETE) {
                    $complete++;
                }
            }

            $percentage = round(($complete / $cnt) * 100, 2);
        } else {
            // The window is not open
            if (!empty($row->histcompletion) || !empty($row->completion)) {
                // But they have previously completed or currently completed.
                $percentage = 100;
            } else {
                // They havent had a chance to do anything yet, or did not previously complete.
                $percentage = 0;
            }
        }

        // Get relevant progress bar and return for display.
        return $shezar_renderer->progressbar($percentage, 'medium', $isexport, $percentage . '%');
    }

    /**
     * Certification display the certification status as string.
     *
     * @param string $status
     * @param array $row
     * @return string
     */
    function rb_display_certif_status($status, $row) {
        global $CERTIFSTATUS;

        $strstatus = '';
        if ($status && isset($CERTIFSTATUS[$status])) {
            $strstatus = get_string($CERTIFSTATUS[$status], 'shezar_certification');
        }
        return $strstatus;
    }

    /**
     * Certification completion status filter options
     */
    function rb_filter_certif_completion_status() {
        global $CERTIFSTATUS;

        $out = array();
        foreach ($CERTIFSTATUS as $key => $status) {
            $out[$key] = get_string($status, 'shezar_certification');
        }

        return $out;
    }

    /**
     * Certification renewal status filter options
     */
    function rb_filter_certif_completion_renewalstatus() {
        global $CERTIFRENEWALSTATUS;

        $out = array();
        foreach ($CERTIFRENEWALSTATUS as $key => $status) {
            $out[$key] = get_string($status, 'shezar_certification');
        }

        return $out;
    }

}
