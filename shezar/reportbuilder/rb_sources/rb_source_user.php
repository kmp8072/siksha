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
require_once("{$CFG->dirroot}/completion/completion_completion.php");

/**
 * A report builder source for the "user" table.
 */
class rb_source_user extends rb_base_source {

    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;
    /**
     * Whether the "staff_facetoface_sessions" report exists or not (used to determine
     * whether or not to display icons that link to it)
     * @var boolean
     */
    private $staff_f2f;

    /**
     * Constructor
     *
     * @param int $groupid (ignored)
     * @param rb_global_restriction_set $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = '{user}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->staff_f2f = $DB->get_field('report_builder', 'id', array('shortname' => 'staff_facetoface_sessions'));
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_user');

        // Apply global report restrictions.
        $this->add_global_report_restriction_join('base', 'id', 'base');

        parent::__construct();
    }

    /**
     * Are the global report restrictions implemented in the source?
     * @return null|bool
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
     * @return array
     */
    protected function define_joinlist() {

        $joinlist = array(
            new rb_join(
                'shezar_stats_comp_achieved',
                'LEFT',
                "(SELECT userid, count(data2) AS number
                    FROM {block_shezar_stats}
                    WHERE eventtype = 4
                    GROUP BY userid)",
                'base.id = shezar_stats_comp_achieved.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'course_completions_courses_started',
                'LEFT',
                "(SELECT userid, COUNT(id) as number
                    FROM {course_completions}
                    WHERE timestarted > 0 OR timecompleted > 0
                    GROUP BY userid)",
                'base.id = course_completions_courses_started.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'shezar_stats_courses_completed',
                'LEFT',
                "(SELECT userid, count(DISTINCT course) AS number
                    FROM {course_completions}
                    WHERE status >= " . COMPLETION_STATUS_COMPLETE . "
                    GROUP BY userid)",
                'base.id = shezar_stats_courses_completed.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'shezar_stats_course_completion_imports',
                'LEFT',
                // Note that a userid does not exist on the tcic table
                // so we have to use username for joining.
                "(SELECT u.id, count(DISTINCT tcic.courseidnumber) AS number
                    FROM {shezar_compl_import_course} tcic
                    INNER JOIN {user} u ON tcic.username = u.username
                    WHERE tcic.importevidence = 1
                    GROUP BY u.id)",
                'base.id = shezar_stats_course_completion_imports.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'prog_extension_count',
                'LEFT',
                "(SELECT userid, count(*) as extensioncount
                    FROM {prog_extension} pe
                    WHERE pe.userid = userid AND pe.status = 0
                    GROUP BY pe.userid)",
                'base.id = prog_extension_count.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        );

        $this->add_user_table_to_joinlist($joinlist, 'base', 'id');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'base', 'id');
        $this->add_cohort_user_tables_to_joinlist($joinlist, 'base', 'id');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        global $DB;

        $columnoptions = array();
        $this->add_user_fields_to_columns($columnoptions, 'base');
        $this->add_job_assignment_fields_to_columns($columnoptions);

        // A column to display a user's profile picture
        $columnoptions[] = new rb_column_option(
                        'user',
                        'userpicture',
                        get_string('userspicture', 'rb_source_user'),
                        'base.id',
                        array(
                            'displayfunc' => 'user_picture',
                            'noexport' => true,
                            'defaultheading' => get_string('picture', 'rb_source_user'),
                            'extrafields' => array(
                                'userpic_picture' => 'base.picture',
                                'userpic_firstname' => 'base.firstname',
                                'userpic_firstnamephonetic' => 'base.firstnamephonetic',
                                'userpic_middlename' => 'base.middlename',
                                'userpic_lastname' => 'base.lastname',
                                'userpic_lastnamephonetic' => 'base.lastnamephonetic',
                                'userpic_alternatename' => 'base.alternatename',
                                'userpic_imagealt' => 'base.imagealt',
                                'userpic_email' => 'base.email'
                            )
                        )
        );

        // A column to display the "My Learning" icons for a user
        $columnoptions[] = new rb_column_option(
                        'user',
                        'userlearningicons',
                        get_string('mylearningicons', 'rb_source_user'),
                        'base.id',
                        array(
                            'displayfunc' => 'learning_icons',
                            'noexport' => true,
                            'defaultheading' => get_string('options', 'rb_source_user')
                        )
        );

        // A column to display the number of achieved competencies for a user
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
                        'statistics',
                        'competenciesachieved',
                        get_string('usersachievedcompcount', 'rb_source_user'),
                        'COALESCE(shezar_stats_comp_achieved.number,0)',
                        array(
                            'displayfunc' => 'count',
                            'joins' => 'shezar_stats_comp_achieved',
                            'dbdatatype' => 'integer',
                        )
        );

        // A column to display the number of started courses for a user
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
                        'statistics',
                        'coursesstarted',
                        get_string('userscoursestartedcount', 'rb_source_user'),
                        'COALESCE(course_completions_courses_started.number,0)',
                        array(
                            'displayfunc' => 'count',
                            'joins' => 'course_completions_courses_started',
                            'dbdatatype' => 'integer',
                        )
        );

        // A column to display the number of completed courses for a user
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
                        'statistics',
                        'coursescompleted',
                        get_string('userscoursescompletedcount', 'rb_source_user'),
                        'COALESCE(shezar_stats_courses_completed.number,0)',
                        array(
                            'displayfunc' => 'count',
                            'joins' => 'shezar_stats_courses_completed',
                            'dbdatatype' => 'integer',
                        )
        );

        // A column to display the number of course completions as evidence for a user.
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
            'statistics',
            'coursecompletionsasevidence',
            get_string('coursecompletionsasevidence', 'rb_source_user'),
            'COALESCE(shezar_stats_course_completion_imports.number,0)',
            array(
                'displayfunc' => 'count',
                'joins' => 'shezar_stats_course_completion_imports',
                'dbdatatype' => 'integer',
            )
        );

        $usednamefields = shezar_get_all_user_name_fields_join('base', null, true);
        $allnamefields = shezar_get_all_user_name_fields_join('base');
        $columnoptions[] = new rb_column_option(
                        'user',
                        'namewithlinks',
                        get_string('usernamewithlearninglinks', 'rb_source_user'),
                        $DB->sql_concat_join("' '", $usednamefields),
                        array(
                            'displayfunc' => 'user_with_links',
                            'defaultheading' => get_string('user', 'rb_source_user'),
                            'extrafields' => array_merge(array('id' => 'base.id',
                                                               'picture' => 'base.picture',
                                                               'imagealt' => 'base.imagealt',
                                                               'email' => 'base.email',
                                                               'deleted' => 'base.deleted'),
                                                         $allnamefields),
                            'dbdatatype' => 'char',
                            'outputformat' => 'text'
                        )
        );

        $columnoptions[] = new rb_column_option(
                        'user',
                        'extensionswithlink',
                        get_string('extensions', 'shezar_program'),
                        'prog_extension_count.extensioncount',
                        array(
                            'joins' => 'prog_extension_count',
                            'displayfunc' => 'extension_link',
                            'extrafields' => array('user_id' => 'base.id')
                        )
        );

        $this->add_cohort_user_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_user_fields_to_filters($filteroptions);
        $this->add_job_assignment_fields_to_filters($filteroptions, 'base');
        $this->add_cohort_user_fields_to_filters($filteroptions);

        return $filteroptions;
    }


    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelinkicon',
            ),
            array(
                'type' => 'user',
                'value' => 'username',
            ),
            array(
                'type' => 'user',
                'value' => 'lastlogin',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
        );

        return $defaultfilters;
    }
    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'base');

        // Add the time created content option.
        $contentoptions[] = new rb_content_option(
            'date',
            get_string('timecreated', 'rb_source_user'),
            'base.timecreated'
        );

        return $contentoptions;
    }

    /**
     * A rb_column_options->displayfunc helper function to display the
     * "My Learning" icons for each user row
     *
     * @global object $CFG
     * @param integer $itemid ID of the user
     * @param object $row The rest of the data for the row
     * @return string
     */
    public function rb_display_learning_icons($itemid, $row) {
        global $CFG, $OUTPUT;

        static $systemcontext;
        if (!isset($systemcontext)) {
            $systemcontext = context_system::instance();
        }

        $disp = html_writer::start_tag('span', array('style' => 'white-space:nowrap;'));

        // Learning Records icon
        if (shezar_feature_visible('recordoflearning')) {
            $disp .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/shezar/plan/record/index.php?userid='.$itemid));
            $disp .= $OUTPUT->flex_icon('recordoflearning', ['classes' => 'ft-size-300']);
            $disp .= html_writer::end_tag('a');
        }

        // Face To Face Bookings icon
        if ($this->staff_f2f) {
            $disp .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/my/bookings.php?userid='.$itemid));
            $disp .= $OUTPUT->flex_icon('calendar', ['classes' => 'ft-size-300']);
            $disp .= html_writer::end_tag('a');
        }

        // Individual Development Plans icon
        if (shezar_feature_visible('learningplans')) {
            if (has_capability('shezar/plan:accessplan', $systemcontext)) {
                $disp .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/shezar/plan/index.php?userid=' . $itemid));
                $disp .= $OUTPUT->flex_icon('learningplan', ['classes' => 'ft-size-300']);
                $disp .= html_writer::end_tag('a');
            }
        }

        $disp .= html_writer::end_tag('span');
        return $disp;
    }


    function rb_display_extension_link($extensioncount, $row, $isexport) {
        global $CFG;
        if (empty($extensioncount)) {
            return '0';
        }
        if (isset($row->user_id) && !$isexport) {
            return html_writer::link("{$CFG->wwwroot}/shezar/program/manageextensions.php?userid={$row->user_id}", $extensioncount);
        } else {
            return $extensioncount;
        }
    }


    /**
     * A rb_column_options->displayfunc helper function for showing a user's links column on the My Team page.
     * To pass the correct data, first:
     *      $usednamefields = shezar_get_all_user_name_fields_join($base, null, true);
     *      $allnamefields = shezar_get_all_user_name_fields_join($base);
     * then your "field" param should be:
     *      $DB->sql_concat_join("' '", $usednamefields)
     * to allow sorting and filtering, and finally your extrafields should be:
     *      array_merge(array('id' => $base . '.id',
     *                        'picture' => $base . '.picture',
     *                        'imagealt' => $base . '.imagealt',
     *                        'email' => $base . '.email'),
     *                  $allnamefields)
     *
     * @param string $user Users name
     * @param object $row All the data required to display a user's name, icon and link
     * @param boolean $isexport If the report is being exported or viewed
     * @return string
     */
    function rb_display_user_with_links($user, $row, $isexport = false) {
        global $CFG, $OUTPUT, $USER;

        require_once($CFG->dirroot . '/shezar/feedback360/lib.php');

        // Process obsolete calls to this display function.
        if (isset($row->userpic_picture)) {
            $picuser = new stdClass();
            $picuser->id = $row->user_id;
            $picuser->picture = $row->userpic_picture;
            $picuser->imagealt = $row->userpic_imagealt;
            $picuser->firstname = $row->userpic_firstname;
            $picuser->firstnamephonetic = $row->userpic_firstnamephonetic;
            $picuser->middlename = $row->userpic_middlename;
            $picuser->lastname = $row->userpic_lastname;
            $picuser->lastnamephonetic = $row->userpic_lastnamephonetic;
            $picuser->alternatename = $row->userpic_alternatename;
            $picuser->email = $row->userpic_email;
            $row = $picuser;
        }

        $userid = $row->id;

        if ($isexport) {
            return $this->rb_display_user($user, $row, true);
        }

        $usercontext = context_user::instance($userid, MUST_EXIST);
        $show_profile_link = user_can_view_profile($row, null, $usercontext);

        $user_pic = $OUTPUT->user_picture($row, array('courseid' => 1, 'link' => $show_profile_link));

        $recordstr = get_string('records', 'rb_source_user');
        $requiredstr = get_string('required', 'rb_source_user');
        $planstr = get_string('plans', 'rb_source_user');
        $profilestr = get_string('profile', 'rb_source_user');
        $bookingstr = get_string('bookings', 'rb_source_user');
        $appraisalstr = get_string('appraisals', 'shezar_appraisal');
        $feedback360str = get_string('feedback360', 'shezar_feedback360');
        $goalstr = get_string('goalplural', 'shezar_hierarchy');
        $rol_link = html_writer::link("{$CFG->wwwroot}/shezar/plan/record/index.php?userid={$userid}", $recordstr);
        $required_link = html_writer::link(new moodle_url('/shezar/program/required.php',
                array('userid' => $userid)), $requiredstr);
        $plan_link = html_writer::link("{$CFG->wwwroot}/shezar/plan/index.php?userid={$userid}", $planstr);
        $profile_link = html_writer::link("{$CFG->wwwroot}/user/view.php?id={$userid}", $profilestr);
        $booking_link = html_writer::link("{$CFG->wwwroot}/my/bookings.php?userid={$userid}", $bookingstr);
        $appraisal_link = html_writer::link("{$CFG->wwwroot}/shezar/appraisal/index.php?subjectid={$userid}", $appraisalstr);
        $feedback_link = html_writer::link("{$CFG->wwwroot}/shezar/feedback360/index.php?userid={$userid}", $feedback360str);
        $goal_link = html_writer::link("{$CFG->wwwroot}/shezar/hierarchy/prefix/goal/mygoals.php?userid={$userid}", $goalstr);

        $show_plan_link = shezar_feature_visible('learningplans') && dp_can_view_users_plans($userid);

        $links = html_writer::start_tag('ul');
        $links .= $show_plan_link ? html_writer::tag('li', $plan_link) : '';
        $links .= $show_profile_link ? html_writer::tag('li', $profile_link) : '';
        $links .= html_writer::tag('li', $booking_link);
        $links .= html_writer::tag('li', $rol_link);

        // Show link to managers, but not to temporary managers.
        $ismanager = \shezar_job\job_assignment::is_managing($USER->id, $userid, null, false);
        if ($ismanager && shezar_feature_visible('appraisals')) {
            $links .= html_writer::tag('li', $appraisal_link);
        }

        if (shezar_feature_visible('feedback360') && feedback360::can_view_other_feedback360s($userid)) {
            $links .= html_writer::tag('li', $feedback_link);
        }

        if (shezar_feature_visible('goals')) {
            if (has_capability('shezar/hierarchy:viewstaffcompanygoal', $usercontext, $USER->id) ||
                has_capability('shezar/hierarchy:viewstaffpersonalgoal', $usercontext, $USER->id)) {
                $links .= html_writer::tag('li', $goal_link);
            }
        }

        if ((shezar_feature_visible('programs') || shezar_feature_visible('certifications')) && prog_can_view_users_required_learning($userid)) {
            $links .= html_writer::tag('li', $required_link);
        }

        $links .= html_writer::end_tag('ul');

        if ($show_profile_link) {
            $user_tag = html_writer::link(new moodle_url("/user/profile.php", array('id' => $userid)),
                fullname($row), array('class' => 'name'));
        }
        else {
            $user_tag = html_writer::span(fullname($row), 'name');
        }

        $return = $user_pic . $user_tag . $links;

        return $return;
    }

    function rb_display_count($result) {
        return $result ? $result : 0;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'deleted',
                'base.deleted'
            ),
        );

        return $paramoptions;
    }
}

// end of rb_source_user class

