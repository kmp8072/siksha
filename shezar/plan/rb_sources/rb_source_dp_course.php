<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas 
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
 * @package shezar
 * @subpackage reportbuilder 
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
// needed for approval constants etc
require_once($CFG->dirroot . '/shezar/plan/lib.php');
// needed to access completion status codes
require_once($CFG->dirroot . '/completion/completion_completion.php');

/**
 * A report builder source for DP courses
 */
class rb_source_dp_course extends rb_base_source {

    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    /**
     * Constructor
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = $this->get_dp_status_base_sql();

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = array();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_course');
        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $CFG
     * @return array
     */
    protected function define_joinlist() {
        global $CFG, $DB;
        $joinlist = array();

        // to get access to position type constants
        require_once($CFG->dirroot . '/shezar/reportbuilder/classes/rb_join.php');

        /**
         * dp_plan has userid, dp_plan_course_assign has courseid. In order to
         * avoid multiplicity we need to join them together before we join
         * against the rest of the query
         */
        $joinlist[] = new rb_join(
                'dp_course',
                'LEFT',
                "(select
                    p.id as planid,
                    p.templateid as templateid,
                    p.userid as userid,
                    p.name as planname,
                    p.description as plandescription,
                    p.startdate as planstartdate,
                    p.enddate as planenddate,
                    p.status as planstatus,
                    pc.id as id,
                    pc.courseid as courseid,
                    pc.priority as priority,
                    pc.duedate as duedate,
                    pc.approved as approved,
                    pc.completionstatus as completionstatus,
                    pc.grade as grade
                  from
                    {dp_plan} p
                  inner join {dp_plan_course_assign} pc
                    on p.id = pc.planid)",
                'dp_course.userid = base.userid and dp_course.courseid = base.courseid',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                array('base')
        );

        $joinlist[] = new rb_join(
                'dp_template',
                'LEFT',
                '{dp_template}',
                'dp_course.templateid = dp_template.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array('dp_course','base')
        );

        $joinlist[] = new rb_join(
                'priority',
                'LEFT',
                '{dp_priority_scale_value}',
                'dp_course.priority = priority.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                array('dp_course','base')
        );
        $joinlist[] = new rb_join(
                'course_completion',
                'LEFT',
                '{course_completions}',
                '(base.courseid = course_completion.course
                    AND base.userid = course_completion.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
                'criteria',
                'LEFT',
                '{course_completion_criteria}',
                '(criteria.course = base.courseid AND ' .
                    'criteria.criteriatype = ' .
                    COMPLETION_CRITERIA_TYPE_GRADE . ')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
                'grade_items',
                'LEFT',
                '{grade_items}',
                '(grade_items.courseid = base.courseid AND ' .
                    'grade_items.itemtype = \'course\')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
                'grade_grades',
                'LEFT',
                '{grade_grades}',
                '(grade_grades.itemid = grade_items.id AND ' .
                    'grade_grades.userid = base.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'grade_items'
        );
        $joinlist[] = new rb_join(
                'course_completion_history',
                'LEFT',
                '(SELECT ' . $DB->sql_concat('userid', 'courseid') . ' uniqueid,
                    userid,
                    courseid,
                    COUNT(id) historycount
                    FROM {course_completion_history}
                    GROUP BY userid, courseid)',
                '(course_completion_history.courseid = base.courseid AND ' .
                    'course_completion_history.userid = base.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
                'enrolment',
                'LEFT',
                '(SELECT DISTINCT ue.userid, enrol.courseid, 1 AS enrolled
                    FROM {user_enrolments} ue
                    JOIN {enrol} enrol ON ue.enrolid = enrol.id)',
                '(enrolment.userid = base.userid AND ' .
                'enrolment.courseid = base.courseid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        $this->add_course_table_to_joinlist($joinlist, 'base', 'courseid', 'INNER');
        $this->add_context_table_to_joinlist($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_user_table_to_joinlist($joinlist, 'base','userid');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_cohort_user_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_cohort_course_tables_to_joinlist($joinlist, 'base', 'courseid');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = array();

        $this->add_course_fields_to_columns($columnoptions);

        $columnoptions[] = new rb_column_option(
                'plan',
                'name',
                get_string('planname', 'rb_source_dp_course'),
                'dp_course.planname',
                array(
                    'defaultheading' => get_string('plan', 'rb_source_dp_course'),
                    'joins' => 'dp_course',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'planlink',
                get_string('plannamelink', 'rb_source_dp_course'),
                'dp_course.planname',
                array(
                    'defaultheading' => get_string('plan', 'rb_source_dp_course'),
                    'joins' => 'dp_course',
                    'displayfunc' => 'planlink',
                    'extrafields' => array( 'plan_id' => 'dp_course.planid' )
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'startdate',
                get_string('planstartdate', 'rb_source_dp_course'),
                'dp_course.planstartdate',
                array(
                    'joins' => 'dp_course',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'enddate',
                get_string('planenddate', 'rb_source_dp_course'),
                'dp_course.planenddate',
                array(
                    'joins' => 'dp_course',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'plan',
                'status',
                get_string('planstatus', 'rb_source_dp_course'),
                'dp_course.planstatus',
                array(
                    'joins' => 'dp_course',
                    'displayfunc' => 'plan_status'
                )
        );

        $columnoptions[] = new rb_column_option(
                'plan',
                'courseduedate',
                get_string('courseduedate', 'rb_source_dp_course'),
                'dp_course.duedate',
                array(
                    'joins' => 'dp_course',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );

        $columnoptions[] = new rb_column_option(
                'plan',
                'coursepriority',
                get_string('coursepriority', 'rb_source_dp_course'),
                'priority.name',
                array(
                    'joins' => 'priority',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
        );

        $columnoptions[] = new rb_column_option(
                'course',
                'status',
                get_string('coursestatus', 'rb_source_dp_course'),
                'dp_course.approved',
                array(
                    'joins' => 'dp_course',
                    'displayfunc' => 'plan_item_status'
                )
        );

        $columnoptions[] = new rb_column_option(
                'course_completion',
                'timecompleted',
                get_string('coursecompletedate', 'rb_source_dp_course'),
                'course_completion.timecompleted',
                array(
                    'joins' => 'course_completion',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );

        $columnoptions[] = new rb_column_option(
                'template',
                'name',
                get_string('templatename', 'rb_source_dp_course'),
                'dp_template.shortname',
                array(
                    'defaultheading' => get_string('plantemplate', 'rb_source_dp_course'),
                    'joins' => 'dp_template',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                )
        );
        $columnoptions[] = new rb_column_option(
                'template',
                'startdate',
                get_string('templatestartdate', 'rb_source_dp_course'),
                'dp_template.startdate',
                array(
                    'joins' => 'dp_template',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'template',
                'enddate',
                get_string('templateenddate', 'rb_source_dp_course'),
                'dp_template.enddate',
                array(
                    'joins' => 'dp_template',
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                )
        );
        $columnoptions[] = new rb_column_option(
                'course_completion',
                'status',
                get_string('completionstatus', 'rb_source_dp_course'),
                // use 'live' values except for completed plans
                "CASE WHEN dp_course.planstatus = " . DP_PLAN_STATUS_COMPLETE . "
                THEN
                    dp_course.completionstatus
                ELSE
                    course_completion.status
                END",
                array(
                    'joins' => array('course_completion','dp_course'),
                    'displayfunc' => 'course_completion_progress',
                    'defaultheading' => get_string('progress', 'rb_source_dp_course'),
                    'extrafields' => array('userid' => 'base.userid', 'courseid' => 'base.courseid'),
                )
            );
        $columnoptions[] = new rb_column_option(
                'course_completion',
                'enroldate',
                get_string('enrolled', 'shezar_core'),
                "course_completion.timeenrolled",
                array(
                    'joins' => array('course_completion'),
                    'displayfunc' => 'nice_date',
                )
            );
        $columnoptions[] = new rb_column_option(
                'course_completion',
                'statusandapproval',
                get_string('completionstatusandapproval', 'rb_source_dp_course'),
                "course_completion.status",
                array(
                    'joins' => array('course_completion', 'dp_course'),
                    'displayfunc' => 'course_completion_progress_and_approval',
                    'defaultheading' => get_string('progress', 'rb_source_dp_course'),
                    'extrafields' => array('approved' => 'dp_course.approved', 'userid' => 'base.userid', 'courseid' => 'base.courseid'),
                )
            );
        $columnoptions[] = new rb_column_option(
                'course_completion',
                'grade',
                get_string('grade', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN course_completion.rplgrade
                      ELSE grade_grades.finalgrade END',
                array(
                    'joins' => array(
                        'grade_grades',
                        'course_completion'
                    ),
                    'displayfunc' => 'course_grade_percent',
                )
            );
        $columnoptions[] = new rb_column_option(
                'course_completion',
                'passgrade',
                get_string('passgrade', 'rb_source_course_completion'),
                'grade_items.gradepass',
                array(
                    'joins' => 'grade_items',
                    'displayfunc' => 'percent',
                )
            );
        $columnoptions[] = new rb_column_option(
                'course_completion',
                'gradestring',
                get_string('requiredgrade', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN course_completion.rplgrade
                      ELSE grade_grades.finalgrade END',
                array(
                    'joins' => array('criteria', 'grade_grades'),
                    'displayfunc' => 'grade_string',
                    'extrafields' => array(
                        'gradepass' => 'criteria.gradepass',
                    ),
                    'defaultheading' => get_string('grade', 'rb_source_course_completion'),
                )
            );
        $columnoptions[] = new rb_column_option(
                'course_completion_history',
                'course_completion_previous_completion',
                get_string('course_completion_previous_completion', 'rb_source_dp_course'),
                'course_completion_history.historycount',
                array(
                    'joins' => 'course_completion_history',
                    'defaultheading' => get_string('course_completion_previous_completion', 'rb_source_dp_course'),
                    'displayfunc' => 'course_completion_previous_completion',
                    'extrafields' => array(
                        'courseid' => 'base.courseid',
                        'userid' => 'base.userid',
                    ),
                )
            );
        $columnoptions[] = new rb_column_option(
                'course_completion_history',
                'course_completion_history_count',
                get_string('course_completion_history_count', 'rb_source_dp_course'),
                'course_completion_history.historycount',
                array(
                    'joins' => 'course_completion_history',
                )
             );

        $this->add_user_fields_to_columns($columnoptions);
        $this->add_job_assignment_fields_to_columns($columnoptions);
        $this->add_cohort_user_fields_to_columns($columnoptions);
        $this->add_cohort_course_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
                'user',
                'id',
                get_string('userid', 'rb_source_dp_course'),
                'number'
        );
        $filteroptions[] = new rb_filter_option(
                'course',
                'courselink',
                get_string('coursetitle', 'rb_source_dp_course'),
                'text'
        );
        $filteroptions[] = new rb_filter_option(
                'course_completion',
                'status',
                get_string('completionstatus', 'rb_source_dp_course'),
                'select',
                array(
                    'selectfunc' => 'coursecompletion_status',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
        );
        $filteroptions[] = new rb_filter_option(
                'course_completion',
                'timecompleted',
                get_string('coursecompletedate', 'rb_source_dp_course'),
                'date'
        );
        $filteroptions[] = new rb_filter_option(
                'plan',
                'name',
                get_string('planname', 'rb_source_dp_course'),
                'text'
        );
        $filteroptions[] = new rb_filter_option(
                'plan',
                'courseduedate',
                get_string('courseduedate', 'rb_source_dp_course'),
                'date'
        );
        $filteroptions[] = new rb_filter_option(
                'course_completion',
                'grade',
                get_string('grade', 'rb_source_course_completion'),
                'number'
        );
        $filteroptions[] = new rb_filter_option(
                'course_completion',
                'passgrade',
                'Required Grade',
                'number'
        );
        $filteroptions[] = new rb_filter_option(
                'course_completion_history',
                'course_completion_history_count',
                get_string('course_completion_history_count', 'rb_source_dp_course'),
                'number'
        );

        $this->add_user_fields_to_filters($filteroptions);
        $this->add_job_assignment_fields_to_filters($filteroptions);
        $this->add_cohort_user_fields_to_filters($filteroptions);
        $this->add_cohort_course_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {

        $paramoptions = array();
        $paramoptions[] = new rb_param_option(
                'userid',
                'base.userid',
                'base'
        );
        $paramoptions[] = new rb_param_option(
                'rolstatus',
                // if plan complete use completion status from within plan
                // otherwise use 'live' completion status
                "(CASE WHEN dp_course.planstatus = " . DP_PLAN_STATUS_COMPLETE . "
                THEN
                    CASE WHEN dp_course.completionstatus >= " . COMPLETION_STATUS_COMPLETE . "
                    THEN
                        'completed'
                    ELSE
                        'active'
                    END
                ELSE
                    CASE WHEN course_completion.status >= " . COMPLETION_STATUS_COMPLETE . "
                    THEN
                        'completed'
                    ELSE
                        'active'
                    END
                END)",
                array('course_completion', 'dp_course'),
                'string'
        );
        $paramoptions[] = new rb_param_option(
            'enrolled',
            "enrolment.enrolled",
            array('enrolment')
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'course',
                'value' => 'coursetypeicon',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'plan',
                'value' => 'planlink',
            ),
            array(
                'type' => 'plan',
                'value' => 'courseduedate',
            ),
            array(
                'type' => 'course_completion',
                'value' => 'statusandapproval',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array();

        $requiredcolumns[] = new rb_column(
            'course',
            'coursevisible',
            '',
            "course.visible",
            array('joins' => 'course')
        );

        $requiredcolumns[] = new rb_column(
            'course',
            'courseaudiencevisible',
            '',
            "course.audiencevisible",
            array('joins' => 'course')
        );

        $requiredcolumns[] = new rb_column(
            'ctx',
            'id',
            '',
            "ctx.id",
            array('joins' => 'ctx')
        );

        return $requiredcolumns;
    }

    public function post_config(reportbuilder $report) {
        // Visibility checks are only applied if viewing a single user's records.
        if ($report->get_param_value('userid')) {
            $fieldalias = 'course';
            $fieldbaseid = $report->get_field('course', 'id', 'base.courseid');
            $fieldvisible = $report->get_field('course', 'visible', 'course.visible');
            $fieldaudvis = $report->get_field('course', 'audiencevisible', 'course.audiencevisible');
            $report->set_post_config_restrictions(shezar_visibility_where($report->get_param_value('userid'),
                $fieldbaseid, $fieldvisible, $fieldaudvis, $fieldalias, 'course', $report->is_cached(), true));
        }
    }

    function rb_display_course_completion_progress($status, $row, $isexport) {
        if ($isexport) {
            global $COMPLETION_STATUS;
            if (in_array($status, array_keys($COMPLETION_STATUS))) {
                return get_string($COMPLETION_STATUS[$status], 'completion');
            } else {
                return '';
            }
        }

        return shezar_display_course_progress_icon($row->userid, $row->courseid, $status);
    }

    function rb_display_course_completion_progress_and_approval($status, $row, $isexport) {

        $approved = isset($row->approved) ? $row->approved : null;

        // get the progress bar
        $content = $this->rb_display_course_completion_progress($status, $row, $isexport);

        // highlight if the item has not yet been approved
        if ($approved == DP_APPROVAL_UNAPPROVED ||
            $approved == DP_APPROVAL_REQUESTED) {
            if ($isexport) {
                $content .= ' (' . $this->rb_display_plan_item_status($approved) . ')';
            } else {
                $content .= $this->rb_display_plan_item_status($approved);
            }
        }
        return $content;
    }

    public function rb_display_course_completion_previous_completion($name, $row) {
        global $OUTPUT;
        if ($name !== '') {
            return $OUTPUT->action_link(new moodle_url('/shezar/plan/record/courses.php',
                    array('courseid' => $row->courseid, 'userid' => $row->userid, 'history' => 1)), $name);
        } else {
            return '';
        }
    }

    function rb_filter_coursecompletion_status() {
        global $COMPLETION_STATUS;

        $out = array();
        foreach ($COMPLETION_STATUS as $code => $statusstring) {
            $out[$code] = get_string($statusstring, 'completion');
        }
        return $out;

    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public function is_ignored() {
        return !shezar_feature_visible('recordoflearning');
    }
}
