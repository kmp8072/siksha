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
 * @category test
 *
 * Reportbuilder generator.
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir  . '/testing/generator/data_generator.php');

/**
 * Report builder generator.
 *
 * Usage:
 *    $reportgenerator = $this->getDataGenerator()->get_plugin_generator('shezar_reportbuilder');
 */
class shezar_reportbuilder_generator extends component_generator_base {
    protected $globalrestrictioncount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();

        $this->globalrestrictioncount = 0;
    }

    /**
     * Create a test restriction.
     *
     * @param array|stdClass $record
     * @return rb_global_restriction
     */
    public function create_global_restriction($record = null) {
        global $CFG;
        require_once("$CFG->dirroot/shezar/reportbuilder/classes/rb_global_restriction.php");

        $this->globalrestrictioncount++;
        $i = $this->globalrestrictioncount;

        $record = (object)(array)$record;

        if (!isset($record->name)) {
            $record->name = 'Global report restriction '.$i;
        }

        $rest = new rb_global_restriction();
        $rest->insert($record);

        return $rest;
    }

    /**
     * Add user related data to restriction.
     *
     * Records of this cohort, org, pos or user are visible
     * in report with the restriction.
     *
     * @param stdClass|array $item - must contain prefix, restrictionid, itemid and optionally includechildren
     * @return stdClass the created record
     */
    public function assign_global_restriction_record($item) {
        global $DB;

        $item = (array)$item;

        if (empty($item['restrictionid'])) {
            throw new coding_exception('generator requires $item->restrictionid');
        }
        if (empty($item['prefix'])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }
        if (empty($item['itemid'])) {
            throw new coding_exception('generator requires $item->itemid');
        }

        $tables = array(
            'cohort' => 'reportbuilder_grp_cohort_record',
            'org' => 'reportbuilder_grp_org_record',
            'pos' => 'reportbuilder_grp_pos_record',
            'user' => 'reportbuilder_grp_user_record',
        );

        $prefix = $item['prefix'];
        if ($prefix === 'position') {
            $prefix = 'pos';
        }
        if ($prefix === 'organisation') {
            $prefix = 'org';
        }
        if (!isset($tables[$prefix])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }

        $record = new stdClass();
        $record->reportbuilderrecordid = $item['restrictionid'];
        $record->{$prefix . 'id'} = $item['itemid'];
        $record->timecreated = time();
        if (isset($item['includechildren'])) {
            $record->includechildren = $item['includechildren'];
        }

        $id = $DB->insert_record($tables[$prefix], $record);
        return $DB->get_record($tables[$prefix], array('id' => $id));
    }

    /**
     * Add user who is allowed to select restriction.
     *
     * @param stdClass|array $item - must contain prefix, restrictionid, itemid and optionally includechildren
     * @return stdClass the created record
     */
    public function assign_global_restriction_user($item) {
        global $DB;

        $item = (array)$item;

        if (empty($item['restrictionid'])) {
            throw new coding_exception('generator requires $item->restrictionid');
        }
        if (empty($item['prefix'])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }
        if (empty($item['itemid'])) {
            throw new coding_exception('generator requires $item->itemid');
        }

        $tables = array(
            'cohort' => 'reportbuilder_grp_cohort_user',
            'org' => 'reportbuilder_grp_org_user',
            'pos' => 'reportbuilder_grp_pos_user',
            'user' => 'reportbuilder_grp_user_user',
        );

        $prefix = $item['prefix'];
        if ($prefix === 'position') {
            $prefix = 'pos';
        }
        if ($prefix === 'organisation') {
            $prefix = 'org';
        }
        if (!isset($tables[$prefix])) {
            throw new coding_exception('generator requires valid $item->prefix');
        }

        $record = new stdClass();
        $record->reportbuilderuserid = $item['restrictionid'];
        $record->{$prefix . 'id'} = $item['itemid'];
        $record->timecreated = time();
        if (isset($item['includechildren'])) {
            $record->includechildren = $item['includechildren'];
        }

        $id = $DB->insert_record($tables[$prefix], $record);
        return $DB->get_record($tables[$prefix], array('id' => $id));
    }
}

/**
 * This class intended to generate different mock entities
 *
 * @package shezar_reportbuilder
 * @category test
 */
class shezar_reportbuilder_cache_generator extends testing_data_generator {
    protected static $cohortrulecount = 0;
    protected static $programcount = 0;
    protected static $certificationcount = 0;
    protected static $plancount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        self::$cohortrulecount = 0;
        self::$programcount = 0;
        self::$certificationcount = 0;
        self::$plancount = 0;
        parent::reset();
    }

    /**
     * Add particular mock params to cohort rules
     *
     * @staticvar int $paramid
     * @param int $ruleid
     * @param array $params Params to add
     * @param array $listofvalues List of values
     */
    public function create_cohort_rule_params($ruleid, $params, $listofvalues) {
        global $DB;
        $data = array($params);
        foreach ($listofvalues as $l) {
            $data[] = array('listofvalues' => $l);
        }
        foreach ($data as $d) {
            foreach ($d as $name => $value) {
                self::$cohortrulecount++;
                $todb = new stdClass();
                $todb->ruleid = $ruleid;
                $todb->name = $name;
                $todb->value = $value;
                $todb->timecreated = time();
                $todb->timemodified = time();
                $todb->modifierid = 2;
                $DB->insert_record('cohort_rule_params', $todb);
            }
        }
    }

    /**
     * Create program for testing.
     *
     * @param array $data Override default properties
     * @return program Program object
     */
    public function create_program($data = array()) {
        // Keep a record of how many test programs are being created.
        self::$programcount++;

        // Set up defaults and merge them with the given data.
        $defaults = array(
            'fullname' => 'Program ' . self::$programcount,
            'usermodified' => 2,
            'category' => 1,
        );
        $properties = array_merge($defaults, $data);

        // Create and return the program.
        $program = program::create($properties);
        return $program;
    }

    /**
     * Create program certification for testing.
     *
     * @param array $data Override default properties - use 'cert_' or 'prog_' prefix for each parameter name
     * @param array $coursesetdata Course set data which gets given to create_coursesets_in_program. Check that function for details.
     * @return program Program object
     */
    public function create_certification($data = array(), array $coursesetdata = null) {
        global $DB;

        // Keep a record of how many test certifications are being created.
        self::$certificationcount++;

        // Separate the program and certification parameters from the given data.
        $programdata = array();
        $certificationdata = array();
        foreach ($data as $key => $value) {
            if (substr($key, 0, 5) === 'prog_') {
                $programdata[substr($key, 5)] = $value;
            } else if (substr($key, 0, 5) === 'cert_') {
                $certificationdata[substr($key, 5)] = $value;
            } else {
                throw new \coding_exception("create_certification \$data keys must be prefixed with 'prog_' or 'cert_'");
            }
        }

        // Set up defaults and merge them with the given data.
        $programdefaults = array(
            'fullname' => 'Certification ' . self::$certificationcount,
            'usermodified' => 2,
        );
        $programmerged = array_merge($programdefaults, $programdata);

        // Set up defaults and merge them with the given data.
        $certifdefaults = array(
            'learningcomptype' => CERTIFTYPE_PROGRAM,
            'activeperiod' => '1 year',
            'windowperiod' => '1 month',
            'minimumactiveperiod' => '3 month',
            'recertifydatetype' => CERTIFRECERT_COMPLETION,
            'timemodified' => time(),
        );
        $certificationmerged = array_merge($certifdefaults, $certificationdata);

        // Create the certification first (the program will point to it).
        $certificationid = $DB->insert_record('certif', (object)$certificationmerged);

        // Set the certificationid in the program.
        $programmerged['certifid'] = $certificationid;

        // Create and return the program.
        $certifprogram = $this->create_program($programmerged);

        if ($coursesetdata !== null) {
            $this->create_coursesets_in_program($certifprogram, $coursesetdata);
        }

        return $certifprogram;
    }

    /**
     * Creates course sets and adds content given on the data passed through details.
     *
     * Details should be an array of course set data, each item can have the following keys:
     *
     *   - type int The type, one of CONTENTTYPE_MULTICOURSE, CONTENTTYPE_COMPETENCY, CONTENTTYPE_RECURRING
     *   - nextsetoperator int The next set operator, one of NEXTSETOPERATOR_THEN, NEXTSETOPERATOR_AND, NEXTSETOPERATOR_OR
     *   - completiontype The type, one of COMPLETIONTYPE_ALL, COMPLETIONTYPE_SOME, COMPLETIONTYPE_OPTIONAL
     *   - certifpath The certification path for this set, one of CERTIFPATH_STD, CERTIFPATH_RECERT
     *   - mincourses int The minimum number of courses the user is required to complete (only relevant with COMPLETIONTYPE_SOME)
     *   - courses array An array of courses created by create_course.
     *
     * @param program $program
     * @param array $details
     * @throws coding_exception
     */
    public function create_coursesets_in_program(program $program, array $details) {
        $expected_coursesets = count($details);

        $certifcontent = $program->get_content();

        foreach ($details as $detail) {
            /** @var course_set $courseset */
            $type = (isset($detail['type'])) ? $detail['type'] : CONTENTTYPE_MULTICOURSE;
            if (!$certifcontent->add_set($type)) {
                // We really need to know about this when it happens, and as its testing coding exception is going to be best.
                throw new coding_exception('Error adding set to course.');
            }
        }
        $certifcontent->fix_set_sortorder();

        if ($expected_coursesets !== count($certifcontent->get_course_sets())) {
            // We really need to know about this when it happens, and as its testing coding exception is going to be best.
            throw new coding_exception('Mis-match in the number of course sets created.');
        }

        $coursecounts = array();
        foreach ($certifcontent->get_course_sets() as $courseset) {
            /** @var course_set $courseset */

            $detail = array_shift($details);

            $nextsetoperator = (isset($detail['nextsetoperator'])) ? $detail['nextsetoperator'] : NEXTSETOPERATOR_THEN;
            $completiontype = (isset($detail['completiontype'])) ? $detail['completiontype'] : COMPLETIONTYPE_ALL;
            $certifpath = (isset($detail['certifpath'])) ? $detail['certifpath'] : CERTIFPATH_STD;
            $mincourses = (isset($detail['mincourses'])) ? (int)$detail['mincourses'] : 0;
            $courses = (isset($detail['courses']) && is_array($detail['courses'])) ? $detail['courses'] : false;

            $coursecount = 0;
            if ($courses) {
                /** @var multi_course_set $courseset */
                if ($courseset->contenttype != CONTENTTYPE_MULTICOURSE) {
                    throw new coding_exception('Courses can only be added to multi course sets.');
                }
                foreach ($courses as $course) {
                    $coursecount++;
                    $key = $courseset->get_set_prefix() . 'courseid';
                    $coursedata = new stdClass();
                    $coursedata->{$key} = $course->id;
                    if (!$courseset->add_course($coursedata)) {
                        // We really need to know about this when it happens, and as its testing coding exception is going to be best.
                        throw new coding_exception('Mis-match in the number of course sets created.');
                    }
                }
            }
            $coursecounts[] = $coursecount;

            $courseset->nextsetoperator = $nextsetoperator;
            $courseset->completiontype = $completiontype;
            $courseset->certifpath = $certifpath;
            $courseset->mincourses = $mincourses;
        }

        $certifcontent->save_content();
    }

    /**
     * Create mock user with assigned manager
     *
     * @see phpunit_util::create_user
     * @global stdClass $DB
     * @param  array|stdClass $record
     * @param  array $options
     * @return stdClass
     */
    public function create_user($record = null, array $options = null) {
        $user = parent::create_user($record, $options);

        if (is_object($record)) {
            $record = (array)$record;
        }
        // Assign manager for correct event messaging handler work.
        if (isset($record['managerid'])) {
            $managerid = $record['managerid'];
        } else {
            $admin = get_admin();
            $managerid = $admin->id;
        }
        $managerja = \shezar_job\job_assignment::get_first($managerid, false);
        if (empty($managerja)) {
            $managerja = \shezar_job\job_assignment::create_default($managerid);
        }
        \shezar_job\job_assignment::create_default($user->id, array('managerjaid' => $managerja->id));

        return $user;
    }

    /**
     * Get empty program assignment
     *
     * @param int $programid
     * @return stdClass
     */
    protected function get_empty_prog_assignment($programid) {
        $data = new stdClass();
        $data->id = $programid;
        $data->item = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completiontime = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completionevent = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completioninstance = array(ASSIGNTYPE_INDIVIDUAL => array());
        return $data;
    }

    /**
     * Assign users to a program
     * @todo remove this when program generator is merged in.
     *
     * @param int $programid Program id
     * @param int $assignmenttype Assignment type
     * @param int $itemid item to be assigned to the program. e.g Audience, position, organization, individual
     * @param null $record
     */
    public function assign_to_program($programid, $assignmenttype, $itemid, $record = null) {
        // Set completion values.
        $completiontime = (isset($record['completiontime'])) ? $record['completiontime'] : -1;
        $completionevent = (isset($record['completionevent'])) ? $record['completionevent'] : 0;
        $completioninstance = (isset($record['completioninstance'])) ? $record['completioninstance'] : 0;
        $includechildren = (isset($record['includechildren'])) ? $record['includechildren'] : null;

        // Create data.
        $data = new stdClass();
        $data->id = $programid;
        $data->item = array($assignmenttype => array($itemid => 1));
        $data->completiontime = array($assignmenttype => array($itemid => $completiontime));
        $data->completionevent = array($assignmenttype => array($itemid => $completionevent));
        $data->completioninstance = array($assignmenttype => array($itemid => $completioninstance));
        $data->includechildren = array ($assignmenttype => array($itemid => $includechildren));

        // Assign item to program.
        $assignmenttoprog = prog_assignments::factory($assignmenttype);
        $assignmenttoprog->update_assignments($data, false);
        $program = new program($programid);
        $program->update_learner_assignments(true);
    }

    /**
     * Add mock program to user
     *
     * @param int $programid Program id
     * @param array $userids User ids array of int
     */
    public function assign_program($programid, $userids) {
        $data = $this->get_empty_prog_assignment($programid);
        $category = new individuals_category();
        $a = 0;
        foreach ($userids as $key => $userid) {
            $data->item[ASSIGNTYPE_INDIVIDUAL][$userid] = 1;
            $data->completiontime[ASSIGNTYPE_INDIVIDUAL][$userid] = -1;
            $data->completionevent[ASSIGNTYPE_INDIVIDUAL][$userid] = 0;
            $data->completioninstance[ASSIGNTYPE_INDIVIDUAL][$userid] = 0;
            unset($userids[$key]);
            $a++;
            if ($a > 500) {
                $a = 0;
                // Write chunk.
                $category->update_assignments($data);
            }
        }
        // Last chunk.
        $category->update_assignments($data);

        $program = new program($programid);
        $assignments = $program->get_assignments();
        $assignments->init_assignments($programid);
        $program->update_learner_assignments(true);
    }

    /**
     * Add course to program
     *
     * @param int $programid Program id
     * @param array $courseids of int Course id
     * @param int $certifpath CERTIFPATH_XXX constant
     */
    public function add_courseset_program($programid, $courseids, $certifpath = CERTIFPATH_STD) {
        global $CERTIFPATHSUF;

        $rawdata = new stdClass();
        $rawdata->id = $programid;
        $rawdata->contentchanged = 1;
        $rawdata->contenttype = 1;
        $rawdata->setprefixes = '999';
        $rawdata->{'999courses'} = implode(',', $courseids);
        $rawdata->{'999contenttype'} = 1;
        $rawdata->{'999id'} = 0;
        $rawdata->{'999label'} = '';
        $rawdata->{'999sortorder'} = 2;
        $rawdata->{'999contenttype'} = 1;
        $rawdata->{'999nextsetoperator'} = '';
        $rawdata->{'999completiontype'} = 1;
        $rawdata->{'999timeallowedperiod'} = 2;
        $rawdata->{'999timeallowednum'} = 1;

        if ($certifpath === CERTIFPATH_RECERT) { // Re-certification path.
            $rawdata->setprefixes_rc = 999;
            $rawdata->certifpath_rc = CERTIFPATH_RECERT;
            $rawdata->iscertif = 1;
            $rawdata->contenttype_rc = 1;
            $rawdata->{'999certifpath'} = $certifpath;
            $rawdata->contenttype_rc = 1;
        } else { // Normal program or initial certification path.
            $rawdata->setprefixes_ce = 999;
            $rawdata->certifpath_ce = $certifpath;
            $rawdata->iscertif = 0;
            $rawdata->{'999certifpath'} = $certifpath;
            $rawdata->contenttype_ce = 1;
        }

        $program = new program($programid);
        $programcontent = $program->get_content();
        $programcontent->setup_content($rawdata);
        $programcontent->save_content();
    }

    /**
     * Create mock program
     *
     * @param int $userid User id
     * @param array|stdClass $record Ovveride default properties
     * @return stdClass Program record
     */
    public function create_plan($userid, $record = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/shezar/plan/lib.php');

        if (is_object($record)) {
            $record = (array)$record;
        }
        self::$plancount++;

        $default = array(
            'templateid' => 0,
            'userid' => $userid,
            'name' => 'Learning plan '. self::$plancount,
            'description' => '',
            'startdate' => null,
            'enddate' => time() + 23328000,
            'timecompleted' => null,
            'status' => DP_PLAN_STATUS_COMPLETE
        );
        $properties = array_merge($default, $record);

        $todb = (object)$properties;
        $newid = $DB->insert_record('dp_plan', $todb);

        $plan = new development_plan($newid);
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_CREATE);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        return $plan;
    }
}
