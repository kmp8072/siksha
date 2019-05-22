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
 * @author Michael Gwynne <michael.gwynne@kineo.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');

class rb_source_facetoface_summary extends rb_facetoface_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $sourcetitle;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Global report restrictions are applied in define_joinlist() method.

        $this->base = '{facetoface_sessions_dates}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->paramoptions = $this->define_paramoptions();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_summary');
        $this->add_customfields();

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    public function define_joinlist() {
        $global_restriction_join_su = $this->get_global_report_restriction_join('su', 'userid');
        $joinlist = array();

        $this->add_session_common_to_joinlist($joinlist);

        $joinlist[] = new rb_join(
            'assetdate',
            'LEFT',
            '{facetoface_asset_dates}',
            'assetdate.sessionsdateid = base.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE
        );

        $joinlist[] = new rb_join(
            'asset',
            'LEFT',
            '{facetoface_asset}',
            'assetdate.assetid = asset.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            'assetdate'
        );

        $joinlist[] = new rb_join(
            'allattendees',
            'LEFT',
            "(SELECT su.sessionid, su.userid, ss.id AS ssid, ss.statuscode
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                WHERE ss.superceded = 0)",
            'allattendees.sessionid = sessions.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'sessions'
        );

        $this->add_session_status_to_joinlist($joinlist);
        $this->add_course_table_to_joinlist($joinlist, 'facetoface', 'course');
        $this->add_course_category_table_to_joinlist($joinlist, 'course', 'category');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'allattendees', 'userid');
        $this->add_user_table_to_joinlist($joinlist, 'allattendees', 'userid');
        $this->add_facetoface_session_roles_to_joinlist($joinlist, 'sessions.id');

        return $joinlist;
    }

    public function define_columnoptions() {

        global $CFG;
        $intimezone = '';
        if (!empty($CFG->facetoface_displaysessiontimezones)) {
            $intimezone = '_in_timezone';
        }

        $columnoptions = array(
            new rb_column_option(
                'session',
                'totalnumattendees',
                get_string('totalnumattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode >= ' . MDL_F2F_STATUS_REQUESTED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'waitlistattendees',
                get_string('waitlistattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_WAITLISTED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'numspaces',
                get_string('numspaces', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode >= ' . MDL_F2F_STATUS_APPROVED . ' THEN 1 ELSE NULL END)',
                array('joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'displayfunc' => 'session_spaces',
                    'extrafields' => array('overall_capacity' => 'sessions.capacity'),
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'cancelledattendees',
                get_string('cancelledattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode IN (' . MDL_F2F_STATUS_USER_CANCELLED . ', ' . MDL_F2F_STATUS_SESSION_CANCELLED . ') THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'fullyattended',
                get_string('fullyattended', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_FULLY_ATTENDED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'partiallyattended',
                get_string('partiallyattended', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_PARTIALLY_ATTENDED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'noshowattendees',
                get_string('noshowattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_NO_SHOW . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'declinedattendees',
                get_string('declinedattendees', 'rb_source_facetoface_summary'),
                '(CASE WHEN allattendees.statuscode = ' . MDL_F2F_STATUS_DECLINED . ' THEN 1 ELSE NULL END)',
                array(
                    'joins' => array('allattendees', 'sessions'),
                    'grouping' => 'count',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'sessions.details',
                array('joins' => 'sessions')
            ),
            new rb_column_option(
                'session',
                'overbookingallowed',
                get_string('overbookingallowed', 'rb_source_facetoface_summary'),
                'sessions.allowoverbook',
                array(
                    'joins' => 'sessions',
                    'displayfunc' => 'yes_or_no'
                )
            ),
            new rb_column_option(
                'session',
                'signupperiod',
                get_string('signupperiod', 'rb_source_facetoface_summary'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_two_datetime_in_timezone',
                    'extrafields' => array('finishdate' => 'sessions.registrationtimefinish', 'timezone' => 'base.sessiontimezone'),
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'session',
                'signupstartdate',
                get_string('signupstartdate', 'rb_source_facetoface_summary'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_datetime_in_timezone',
                    'extrafields' => array('timezone' => 'base.sessiontimezone'),
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'session',
                'signupenddate',
                get_string('signupenddate', 'rb_source_facetoface_summary'),
                'sessions.registrationtimefinish',
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_datetime_in_timezone',
                    'extrafields' => array('timezone' => 'base.sessiontimezone'),
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'facetoface',
                'minbookings',
                get_string('minbookings', 'rb_source_facetoface_summary'),
                'sessions.mincapacity',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'date',
                'sessiondate_link',
                get_string('sessdatetimelink', 'rb_source_facetoface_summary'),
                'base.timestart',
                array(
                    'joins' => 'sessions',
                    'extrafields' => array(
                        'session_id' => 'sessions.id',
                        'timezone' => 'base.sessiontimezone',
                    ),
                    'defaultheading' => get_string('sessdatetime', 'rb_source_facetoface_summary'),
                    'displayfunc' => 'link_f2f_session' . $intimezone,
                    'dbdatatype' => 'timestamp'
                )
            )
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $columnoptions[] = new rb_column_option(
                'facetoface',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_summary'),
                'sessions.normalcost',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'decimal'
                )
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $columnoptions[] = new rb_column_option(
                    'facetoface',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_summary'),
                    'sessions.discountcost',
                    array(
                        'joins' => 'sessions',
                        'dbdatatype' => 'decimal'
                    )
                );
            }
        }

        $this->add_session_status_to_columns($columnoptions);
        $this->add_session_common_to_columns($columnoptions);
        $this->add_facetoface_common_to_columns($columnoptions);
        $this->add_facetoface_session_roles_to_columns($columnoptions);

        // Include some standard columns.
        $this->add_course_category_fields_to_columns($columnoptions);
        $this->add_course_fields_to_columns($columnoptions);
        $this->add_assets_fields_to_columns($columnoptions);
        $this->add_rooms_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'facetoface',
                'name',
                get_string('ftfname', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'date',
                'sessionstartdate',
                get_string('sessdate', 'rb_source_facetoface_sessions'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'signupstartdate',
                get_string('signupstartdate', 'rb_source_facetoface_summary'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'signupenddate',
                get_string('signupenddate', 'rb_source_facetoface_summary'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'bookingstatus',
                get_string('bookingstatus', 'rb_source_facetoface_summary'),
                'select',
                array(
                    'selectchoices' => self::get_bookingstatus_options(),
                )
            ),
            new rb_filter_option(
                'session',
                'overallstatus',
                get_string('overallstatus', 'rb_source_facetoface_summary'),
                'select',
                array(
                    'selectfunc' => 'overallstatus',
                )
            ),
            new rb_filter_option(
                'asset',
                'assetavailable',
                get_string('assetavailable', 'rb_source_facetoface_asset'),
                'f2f_assetavailable',
                array(),
                'asset.id',
                array('asset')
            ),
            new rb_filter_option(
                'room',
                'roomavailable',
                get_string('roomavailable', 'rb_source_facetoface_rooms'),
                'f2f_roomavailable',
                array(),
                'room.id',
                array('room')
            )
        );

        $this->add_facetoface_session_role_fields_to_filters($filteroptions);

        // Add session custom fields to filters.
        $this->add_course_category_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('thedate', 'rb_source_facetoface_sessions'),
            'base.timestart'
        );

        return $contentoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
            ),
            array(
                'type' => 'facetoface',
                'value' => 'namelink',
            ),
        );

        return $defaultcolumns;
    }

    /**
     * Convert a f2f date into a link to that session with timezone.
     *
     * @param string $date Date of session
     * @param object $row Report row
     * @param bool $isexport
     * @return string Display html
     */
    function rb_display_link_f2f_session_in_timezone($date, $row, $isexport = false) {
        global $OUTPUT;
        $sessionid = $row->session_id;
        if ($date && is_numeric($date)) {
            $date = $this->rb_display_nice_datetime_in_timezone($date, $row);
            if ($isexport) {
                return $date;
            }
            return $OUTPUT->action_link(new moodle_url('/mod/facetoface/attendees.php', array('s' => $sessionid)), $date);
        } else {
            $unknownstr = get_string('unknowndate', 'rb_source_facetoface_summary');
             if ($isexport) {
                return $unknownstr;
            }
            return $OUTPUT->action_link(new moodle_url('/mod/facetoface/attendees.php', array('s' => $sessionid)), $unknownstr);
        }
    }

    public function rb_display_actions($session, $row, $isexport = false) {
        global $OUTPUT;

        if ($isexport) {
            return null;
        }

        $cm = get_coursemodule_from_instance('facetoface', $row->facetofaceid);
        $context = context_module::instance($cm->id);
        if (!has_capability('mod/facetoface:viewattendees', $context)) {
            return null;
        }

        return html_writer::link(
            new moodle_url('/mod/facetoface/attendees.php', array('s' => $session)),
            $OUTPUT->pix_icon('t/cohort', get_string("attendees", "facetoface"))
        );
    }

    /**
     * Spaces left on session.
     *
     * @param string $count Number of signups
     * @param object $row Report row
     * @return string Display html
     */
    public function rb_display_session_spaces($count, $row) {
        $spaces = $row->overall_capacity - $count;
        return ($spaces > 0 ? $spaces : 0);
    }

    /**
     * Show if manager's approval required
     * @param bool $required True when approval required
     * @param stdClass $row
     */
    public function rb_display_approver($required, $row) {
        if ($required) {
            return get_string('manager', 'core_role');
        } else {
            return get_string('noone', 'rb_source_facetoface_summary');
        }
    }

    /**
     * Required columns.
     */
    protected function define_requiredcolumns() {
        // Session_id is needed so when grouping we can keep the information grouped by sessions.
        // This is done to cover the case when we have several sessions which are identical.
        $requiredcolumns = array(
            new rb_column(
                'sessions',
                'id',
                '',
                "sessions.id",
                array('joins' => 'sessions')
            )
        );

        $context = context_system::instance();
        if (has_any_capability(['mod/facetoface:viewattendees'], $context)) {
            $requiredcolumns[] = new rb_column(
                'admin',
                'actions',
                get_string('actions', 'rb_source_facetoface_summary'),
                'sessions.id',
                [
                    'noexport' => true,
                    'nosort' => true,
                    'extrafields' => ['facetofaceid' => 'sessions.facetoface'],
                    'displayfunc' => 'actions'
                ]
            );
        }

        return $requiredcolumns;
    }

    protected function add_customfields() {
        $this->add_custom_fields_for('facetoface_session', 'sessions', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_custom_fields_for('facetoface_sessioncancel', 'sessions', 'facetofacesessioncancelid', $this->joinlist, $this->columnoptions, $this->filteroptions);

        $this->add_custom_fields_for(
            'facetoface_room',
            'room',
            'facetofaceroomid',
            $this->joinlist,
            $this->columnoptions,
            $this->filteroptions
        );

        $this->add_custom_fields_for(
            'facetoface_asset',
            'asset',
            'facetofaceassetid',
            $this->joinlist,
            $this->columnoptions,
            $this->filteroptions
        );
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'roomid',
                'base.roomid',
                'room'
            ),
            new rb_param_option(
                'assetid',
                'asset.id',
                'asset'
            ),
        );

        return $paramoptions;
    }
}
