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
 * @package shezar_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_site_logstore extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'auser');

        $this->base = '{logstore_standard_log}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_site_logstore');
        $this->sourcewhere = 'anonymous = 0';

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {

        $joinlist = array();

        // Include some standard joins.
        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        $this->add_course_table_to_joinlist($joinlist, 'base', 'courseid');
        // Requires the course join.
        $this->add_course_category_table_to_joinlist($joinlist,
            'course', 'category');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_tag_tables_to_joinlist('course', $joinlist, 'base', 'courseid');
        $this->add_cohort_user_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_cohort_course_tables_to_joinlist($joinlist, 'base', 'courseid');

        // Add related user support.
        $this->add_relateduser_table_to_joinlist($joinlist, 'base', 'relateduserid');
        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $eventextrafields = array(
            'eventname' =>'base.eventname',
            'component' => 'base.component',
            'action' => 'base.action',
            'target' => 'base.target',
            'objecttable' => 'base.objecttable',
            'objectid' => 'base.objectid',
            'crud' => 'base.crud',
            'edulevel' => 'base.edulevel',
            'contextid' => 'base.contextid',
            'contextlevel' => 'base.contextlevel',
            'contextinstanceid' => 'base.contextinstanceid',
            'userid' => 'base.userid',
            'courseid' => 'base.courseid',
            'relateduserid' => 'base.relateduserid',
            'anonymous' => 'base.anonymous',
            'other' => 'base.other',
            'timecreated' => 'base.timecreated',
        );

        $columnoptions = array(
            new rb_column_option(
                'logstore_standard_log',
                'timecreated',
                get_string('time', 'rb_source_site_logstore'),
                'base.timecreated',
                array('displayfunc' => 'nice_datetime', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'ip',
                get_string('ip', 'rb_source_site_logstore'),
                'base.ip',
                array('displayfunc' => 'iplookup')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'targetaction',
                get_string('targetaction', 'rb_source_site_logstore'),
                $DB->sql_concat('base.target', "' '", 'base.action'),
                array('dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'other',
                get_string('other', 'rb_source_site_logstore'),
                'base.other',
                array('displayfunc' => 'serialized')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'eventname',
                get_string('eventclass', 'rb_source_site_logstore'),
                'base.eventname',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'component',
                get_string('component', 'rb_source_site_logstore'),
                'base.component',
                array('displayfunc' => 'component',
                      'extrafields' => $eventextrafields)
            ),
            new rb_column_option(
                'logstore_standard_log',
                'context',
                get_string('context', 'rb_source_site_logstore'),
                'base.contextid',
                array('displayfunc' => 'context',
                      'extrafields' => $eventextrafields)
            ),
            new rb_column_option(
                'logstore_standard_log',
                'action',
                get_string('action', 'rb_source_site_logstore'),
                'base.action',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'target',
                get_string('target', 'rb_source_site_logstore'),
                'base.target',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'objecttable',
                get_string('objecttable', 'rb_source_site_logstore'),
                'base.objecttable',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'objectid',
                get_string('objectid', 'rb_source_site_logstore'),
                'base.objectid',
                array('dbdatatype' => 'integer')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'origin',
                get_string('origin', 'rb_source_site_logstore'),
                'base.origin',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'crud',
                get_string('crud', 'rb_source_site_logstore'),
                'base.crud',
                array('dbdatatype' => 'char',
                      'displayfunc' => 'crud')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'edulevel',
                get_string('edulevel', 'moodle'),
                'base.edulevel',
                array('dbdatatype' => 'char',
                      'displayfunc' => 'edulevel')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'name',
                get_string('name', 'rb_source_site_logstore'),
                'base.id',
                array('displayfunc' => 'name',
                      'extrafields' => $eventextrafields
                     )
            ),
            new rb_column_option(
                'logstore_standard_log',
                'namelink',
                get_string('namelink', 'rb_source_site_logstore'),
                'base.id',
                array('displayfunc' => 'name_link',
                      'extrafields' => $eventextrafields
                     )
            ),
            new rb_column_option(
                'logstore_standard_log',
                'description',
                get_string('description', 'moodle'),
                'base.id',
                array('displayfunc' => 'description',
                      'extrafields' => $eventextrafields
                )
            ),
        );

        // Include some standard columns.
        $this->add_user_fields_to_columns($columnoptions);
        $this->add_course_fields_to_columns($columnoptions);
        $this->add_course_category_fields_to_columns($columnoptions);
        $this->add_job_assignment_fields_to_columns($columnoptions);
        $this->add_tag_fields_to_columns('course', $columnoptions);
        $this->add_cohort_user_fields_to_columns($columnoptions);
        $this->add_cohort_course_fields_to_columns($columnoptions);
        // Add related user support.
        $this->add_user_fields_to_columns($columnoptions, 'ruser', 'relateduser');

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'logstore_standard_log',
                'action',
                get_string('action', 'rb_source_site_logstore'),
                'text',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'eventname',
                get_string('name', 'rb_source_site_logstore'),
                'select',
                array(
                    'selectfunc' => 'event_names_list',
                )
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'component',
                get_string('component', 'rb_source_site_logstore'),
                'text',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'objecttable',
                get_string('objecttable', 'rb_source_site_logstore'),
                'text',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'objectid',
                get_string('objectid', 'rb_source_site_logstore'),
                'number',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'timecreated',
                get_string('time', 'rb_source_site_logstore'),
                'date',
                array()
            ),
        );

        // Include some standard filters.
        $this->add_user_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);
        $this->add_course_category_fields_to_filters($filteroptions);
        $this->add_job_assignment_fields_to_filters($filteroptions);
        $this->add_tag_fields_to_filters('course', $filteroptions);
        $this->add_cohort_user_fields_to_filters($filteroptions);
        $this->add_cohort_course_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('date', 'rb_source_site_logstore'),
            'base.timecreated'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.userid',
                null
            ),
            new rb_param_option(
                'courseid',
                'base.courseid'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'logstore_standard_log',
                'value' => 'timecreated',
            ),
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'logstore_standard_log',
                'value' => 'ip',
            ),
            array(
                'type' => 'logstore_standard_log',
                'value' => 'other',
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
            array(
                'type' => 'logstore_standard_log',
                'value' => 'eventname',
            ),
            array(
                'type' => 'logstore_standard_log',
                'value' => 'action',
                'advanced' => 1,
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_category',
                'value' => 'id',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allpositions',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allorganisations',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }


    protected function define_requiredcolumns() {
        $requiredcolumns = array(
        );
        return $requiredcolumns;
    }

    /**
     * Adds the user table to the $joinlist array with different join name
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'user id' field
     * @param string $field Name of user id field to join on
     * @return boolean True
     */
    protected function add_relateduser_table_to_joinlist(&$joinlist, $join, $field) {
        $joinlist[] = new rb_join(
            'ruser',
            'LEFT',
            '{user}',
            "ruser.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'base'
        );
    }

    /**
     * Display serialized info in preformated view.
     * @param string $other
     * @param stdClass $row
     * @return string
     */
    public function rb_display_serialized($other, $row) {
        return html_writer::tag('pre', print_r(unserialize($other), true));
    }

    /**
     * Convert IP address into a link to IP lookup page
     * @param string $ip
     * @param stdClass $row
     * @return string
     */
    public function rb_display_iplookup($ip, $row) {
        if (!isset($ip) || $ip == '') {
            return '';
        }
        $params = array('id' => $ip);
        if (isset($row->userid)) {
            $params['user'] = $row->user_id;
        }
        $url = new moodle_url('/iplookup/index.php', $params);
        return html_writer::link($url, $ip);
    }

    /**
     * Displays related educational level.
     * @param string $edulevel
     * @param stdClass $row
     * @return string
     */
    public function rb_display_edulevel($edulevel, $row) {
        switch ($edulevel) {
            case \core\event\base::LEVEL_PARTICIPATING:
                return get_string('edulevelparticipating', 'moodle');
                break;
            case \core\event\base::LEVEL_TEACHING:
                return get_string('edulevelteacher', 'moodle');
                break;
            case \core\event\base::LEVEL_OTHER:
                return get_string('edulevelother', 'moodle');
                break;
        }
        return get_string('unrecognized', 'rb_source_site_logstore', $edulevel);
    }

    /**
     * Displays CRUD verbs.
     * @param string $edulevel
     * @param stdClass $row
     * @return string
     */
    public function rb_display_crud($crud, $row) {
        switch ($crud) {
            case 'c':
                return get_string('crud_c', 'rb_source_site_logstore');
                break;
            case 'r':
                return get_string('crud_r', 'rb_source_site_logstore');
                break;
            case 'u':
                return get_string('crud_u', 'rb_source_site_logstore');
                break;
            case 'd':
                return get_string('crud_d', 'rb_source_site_logstore');
                break;
        }
        return get_string('unrecognized', 'rb_source_site_logstore', $crud);
    }

    /**
     * Displays event name
     * @param string $id
     * @param stdClass $row
     * @return string
     */
    public function rb_display_name($id, $row) {
        $event = \core\event\base::restore((array)$row, array());
        return $event->get_name();
    }

    /**
     * Displays event name as link to event
     * @param string $id
     * @param stdClass $row
     * @return string
     */
    public function rb_display_name_link($id, $row) {
        $event = \core\event\base::restore((array)$row, array());
        return html_writer::link($event->get_url(), $event->get_name());
    }

    /**
     * Displays event description.
     * @param string $id
     * @param stdClass $row
     * @return string
     */
    public function rb_display_description($id, $row) {
        $eventdata = (array)$row;
        $eventdata['other'] = unserialize($eventdata['other']);
        $event = \core\event\base::restore($eventdata, array());
        return $event->get_description();
    }

    /**
     * Generate the context column.
     * @param string $id
     * @param stdClass $row
     * @return string
     */
    public function rb_display_context($id, $row) {
        $event = \core\event\base::restore((array)$row, array());
        // Code used from report/log/classes/table_log.php:col_context.
        // Add context name.
        if ($event->contextid) {
            // If context name was fetched before then return, else get one.
            if (isset($this->contextname[$event->contextid])) {
                return $this->contextname[$event->contextid];
            } else {
                $context = context::instance_by_id($event->contextid, IGNORE_MISSING);
                if ($context) {
                    $contextname = $context->get_context_name(true);
                    if (empty($this->download) && $url = $context->get_url()) {
                        $contextname = html_writer::link($url, $contextname);
                    }
                } else {
                    $contextname = get_string('other');
                }
            }
        } else {
            $contextname = get_string('other');
        }

        $this->contextname[$event->contextid] = $contextname;
        return $contextname;
    }

    /**
     * Generate the component localised name.
     * @param string $componentname
     * @return string
     */
    protected function get_component_str($componentname) {
        // Code used from report/log/classes/table_log.php:col_component.
        if (($componentname === 'core') || ($componentname === 'legacy')) {
            return  get_string('coresystem');
        } else if (get_string_manager()->string_exists('pluginname', $componentname)) {
            return get_string('pluginname', $componentname);
        } else {
            return $componentname;
        }
    }

    /**
     * Generate the component column.
     * @param string $desc
     * @param stdClass $row
     * @return string
     */
    public function rb_display_component($desc, $row) {
        return $this->get_component_str($row->component);
    }

    /**
     * Get list of event names
     * @return array
     */
    function rb_filter_event_names_list() {
        global $DB;

        $completelist = $DB->get_recordset_sql("SELECT DISTINCT(eventname) FROM $this->base");

        if (empty($completelist)) {
            return array("" => get_string("nofilteroptions", "shezar_reportbuilder"));
        }

        $events = array();
        foreach ($completelist as $eventfullpath => $eventname) {
            if (method_exists($eventfullpath, 'get_static_info')) {
                $ref = new \ReflectionClass($eventfullpath);
                if (!$ref->isAbstract()) {
                    // Get additional information.
                    $strdata = new stdClass();
                    $strdata->eventfullpath = $eventfullpath;
                    $strdata->eventname = $eventfullpath::get_name();
                    // Add to list.
                    $events[$eventfullpath] = get_string('eventandcomponent', 'rb_source_site_logstore', $strdata);
                }
            }
        }
        uasort($events, 'strcoll');

        return $events;
    }
}

