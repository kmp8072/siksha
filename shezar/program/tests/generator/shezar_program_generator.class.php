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
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package shezar_program
 * @subpackage test
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/generator/lib.php');

/**
 * Program generator.
 *
 * @package shezar_program
 * @subpackage test
 */
class shezar_program_generator extends component_generator_base {
    protected $programcount = 0;
    protected $certificationcount = 0;
    // Default name for created programs.
    const DEFAULT_PROGRAM_NAME = 'Test Program';
    const DEFAULT_CERTIFICATION_NAME = 'Test Certification';

    /**
     * Create mock programs.
     *
     * @param int $size number of items to create.
     */
    public function create_programs($size) {
        global $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');

        // Add 1-$size programs
        // Randomly make some certifications
        for ($p=0; $p < $size; $p++) {
            if ($size < 2) {
                $certstocreate = 0;
            } else {
                $certstocreate = mt_rand(1, $size-1);
            }
            $default_name = ($this->certificationcount < $certstocreate) ? self::DEFAULT_CERTIFICATION_NAME : self::DEFAULT_PROGRAM_NAME;
            $id = shezar_generator_util::get_next_record_number('prog', 'fullname', $default_name);
            $fullname = "{$default_name} {$id}";
            echo "\nCREATE PROGRAM $fullname";
            $data = array('fullname' => $fullname);
            $prog = $this->create_program($data);
            // Add 1-$size coursesets, with 1-$size random courses in each.
            $coursesets = mt_rand(1, $size);
            for ($cs=0; $cs < $coursesets; $cs++) {
                $this->add_courseset_to_program($prog->id, ($cs+1), $size);
            }
            // Randomly make some as a certification
            if ($this->certificationcount < $certstocreate) {
                list($actperiod, $winperiod, $recerttype) = $this->get_random_certification_setting();
                // Covert this program to a certification.
                $this->create_certification_settings($prog->id, $actperiod, $winperiod, $recerttype);
                // Get a random course and assign as the recert path.
                $this->add_courseset_to_program($prog->id, ($cs+1), 1, CERTIFPATH_RECERT);
                $this->certificationcount++;
            }
            // Now do some random user assignments.
            $assigntypes = array(
                    'org' => ASSIGNTYPE_ORGANISATION,
                    'pos' => ASSIGNTYPE_POSITION,
                    'cohort' => ASSIGNTYPE_COHORT,
                    'manager' => ASSIGNTYPE_MANAGERJA,
                    'individual' => ASSIGNTYPE_INDIVIDUAL,
            );
            // Add at least 2 assignment types.
            $numassignments = mt_rand(2, count($assigntypes));
            $assigns = array_rand($assigntypes, $numassignments);
            $exceptions = false;
            foreach ($assigns as $assign) {
                echo "\nADD PROGRAM ASSIGNMENT $assign";
                // Get random selection of items for assignment type.
                $items = $this->get_assignment_items($assigntypes[$assign], $size);
                // Assign the items.
                foreach ($items as $item) {
                    $exception = $this->assign_to_program($prog->id, $assigntypes[$assign], $item);
                    if ($exception) { $exceptions = true;}
                }
            }
            // Finalise the assignments.
            $program = new program($prog->id);
            // reset the assignments property to ensure it only contains the current assignments.
            $assignments = $program->get_assignments();
            $assignments->init_assignments($prog->id);
            // Update the user assignments
            $program->update_learner_assignments(true);
            // Randomly resolve some exceptions and assign program anyway.
            if ($exceptions && mt_rand(0,1)) {
                $exceptions_manager = new prog_exceptions_manager($prog->id);
                $exceptions_manager->set_selections(-1, '');
                $selected_exceptions = $exceptions_manager->get_selected_exceptions();
                echo "\nRESOLVING EXCEPTIONS";
                foreach ($selected_exceptions as $exception_ob) {
                    $exception = null;

                    // Get an instance of the correct exception class
                    if (isset($exceptions_manager->exceptiontype_classnames[$exception_ob->exceptiontype])) {
                        // Create an instance
                        $exception = new $exceptions_manager->exceptiontype_classnames[$exception_ob->exceptiontype]($exception_ob->programid, $exception_ob);
                    } else {
                        // Else do nothing..
                        continue;
                    }
                    echo ".";
                    // Handle the exception. This will delete the exception if it is successfully
                    // handled and return true. If this exception does not have a handler for
                    // the specified action it will also return true.  Otherwise it will return false.
                    $success = $exception->handle(2);
                }
            }
        }
        $this->fix_program_sortorder();
        echo "\n" . get_string('progress_createprograms', 'shezar_generator', $size);
    }

    /**
     * Create a mock certification via the program generator + a few extra settings
     *
     * @param array $data Override default properties
     * @return int        Program->id
     */
    public function create_certification($data = array()) {
        global $CFG;

        require_once($CFG->dirroot . '/shezar/certification/lib.php');

        $activeperiod = isset($data['activeperiod']) ? $data['activeperiod'] : '1 year';
        $windowperiod = isset($data['windowperiod']) ? $data['windowperiod'] : '1 month';
        $recertifydatetype  = isset($data['recertifydatetype']) ? $data['recertifydatetype'] : CERTIFRECERT_EXPIRY;

        $program = $this->create_program($data);
        $this->create_certification_settings($program->id, $activeperiod, $windowperiod, $recertifydatetype);

        return $program->id;
    }

    /**
     * Create mock program
     *
     * @param array $data Override default properties
     * @return stdClass Program record
     */
    public function create_program($data = array()) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');
        require_once($CFG->dirroot . '/shezar/program/program_messages.class.php');

        $this->programcount++;
        $now = time();
        $sortorder = $DB->get_field('prog', 'MAX(sortorder) + 1', array());
        if (empty($data['category'])) {
            // Empty category values may come through when the intention was just to use the default.
            unset($data['category']);
        }
        $default = array(
            'fullname' => 'Program Fullname',
            'availablefrom' => 0,
            'availableuntil' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => 2,
            'shortname' => 'progshort',
            'idnumber' => '',
            'sortorder' => !empty($sortorder) ? $sortorder : 0,
            'icon' => 1,
            'exceptionssent' => 0,
            'visible' => 1,
            'summary' => '',
            'endnote' => '',
            'audiencevisible' => 2,
            'certifid' => null,
            'category' => $DB->get_field_select('course_categories', "MIN(id)", "parent=0")
        );
        $properties = array_merge($default, $data);

        $todb = (object)$properties;
        $program = program::create($todb);

        return $program;
    }

    /**
     * Get user assignment items
     *
     * @param int $assigntype Type of item - individual, cohort, position etc
     * @param int $size return random 1 to $size items of this type
     * @return array of item ids
     */
    public function get_assignment_items($assigntype, $size) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');

        $numitems = mt_rand(1, $size);
        $items = array();
        switch ($assigntype) {
            case ASSIGNTYPE_ORGANISATION:
                $table = 'org';
                break;
            case ASSIGNTYPE_POSITION:
                $table = 'pos';
                break;
            case ASSIGNTYPE_COHORT:
                $table = 'cohort';
                break;
            case ASSIGNTYPE_MANAGERJA:
                $like = $DB->sql_like('username', '?');
                $managers = $DB->get_fieldset_select('user', 'id', $like, array(shezar_generator_site_backend::MANAGER_TOOL_GENERATOR . '%'));
                $keys = array_rand($managers, $numitems);
                if (!is_array($keys)) { $keys = array($keys);}
                foreach ($keys as $key) {
                    if (isset($managers[$key])) {
                        $jobassignment = \shezar_job\job_assignment::get_first($managers[$key], false);
                        if (empty($jobassignment)) {
                            $jobassignment = \shezar_job\job_assignment::create_default($managers[$key]);
                        }
                        $items[] = $jobassignment->id;
                    }
                }
                return $items;
                break;
            case ASSIGNTYPE_INDIVIDUAL:
                $table = 'user';
                break;
        }
        $circuitbreaker =0;
        for ($x=0; $x< $numitems; $x++) {
            // Find one we have not already used...there may not be enough of desired item though.
            $unique = false;
            while (!$unique) {
                if ($circuitbreaker > 1000) {
                    break;
                }
                $itemid = shezar_generator_util::get_random_record_id($table);
                if (!in_array($itemid, $items)) {
                    $items[] = $itemid;
                    $unique = true;
                } else {
                    $circuitbreaker++;
                }
            }
        }
        return $items;
    }

    /**
     * Add courseset to program
     *
     * @param int $programid id Program id
     * @param int $coursesetnum number of courseset
     * @param int $numcourses add random number of courses between 1 and $numcourses
     */
    public function add_courseset_to_program($programid, $coursesetnum, $numcourses, $certifpath = CERTIFPATH_CERT) {
        global $CFG, $DB, $CERTIFPATHSUF;
        require_once($CFG->dirroot . '/shezar/program/lib.php');
        require_once($CFG->dirroot . '/shezar/certification/lib.php');

        // Do not assign the site course!
        $site = get_site();
        // Get all courses assigned in coursesets so we do not assign a course twice in different coursesets.
        $sql = "SELECT pcc.id, pcc.courseid
                FROM {prog_courseset_course} pcc
                INNER JOIN {prog_courseset} pc on pcc.coursesetid = pc.id
                INNER JOIN {prog} p on pc.programid = p.id
                WHERE pc.programid = p.id
                AND pc.contenttype = 1
                AND p.id = ?";
        $existingcourses = $DB->get_records_sql_menu($sql, array($programid));
        $existingcourses = array_values($existingcourses);
        $numcoursestoassign = mt_rand(1, $numcourses);
        $courseids = array();
        $coursesassigned = 0;
        while ($coursesassigned < $numcoursestoassign) {
            $courseid = shezar_generator_util::get_random_record_id('course');
            if ($courseid != $site->id && !in_array($courseid, $existingcourses)) {
                $courseids[] = $courseid;
                $coursesassigned++;
            }
        }
        $rawdata = new stdClass();
        $rawdata->id = $programid;
        $rawdata->contentchanged = 1;
        $rawdata->contenttype = 1;
        $rawdata->setprefixes = '999';
        $rawdata->{'999courses'} = implode(',', $courseids);
        $rawdata->{'999contenttype'} = 1;
        $rawdata->{'999id'} = 0;
        $rawdata->{'999label'} = "Course Set {$coursesetnum}";
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
            $rawdata->{'999certifpath'} = 2;
            $rawdata->contenttype_rc = 1;
        } else {
            // Certification path.
            $rawdata->setprefixes_rc = 999;
            $rawdata->certifpath_rc = CERTIFPATH_CERT;
            $rawdata->iscertif = 0;
            $rawdata->contenttype_rc = 1;
            $rawdata->{'999certifpath'} = 1;
            $rawdata->contenttype_rc = 1;
        }
        $program = new program($programid);
        $programcontent = $program->get_content();
        $programcontent->setup_content($rawdata);
        $programcontent->save_content();
    }

    private function complete_course($courseid, $userid) {
        $comp_man = new stdClass();
        $comp_man->id = $DB->get_field('course_completions', 'id',
            array('userid' => $this->user_man->id, 'course' => $this->course->id));
        $comp_man->userid = $this->user_man->id;
        $comp_man->course = $this->course->id;
        $comp_man->timeenrolled = $this->now;
        $comp_man->timestarted = $this->now;
        $comp_man->timecompleted = $this->now;
        $comp_man->status = COMPLETION_STATUS_COMPLETE;

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
     * Creates an individual assignment for a user.
     * TODO: Either fix or remove this function.
     *
     * @param array $data   The array should contain programid and userid
     * @return boolean      Success/failure
     */
    public function create_prog_assign($data) {
        global $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');

        $completiontime = isset($data['completiontime']) ? $data['completiontime'] : 0;

        // Create data.
        $assign_data = new stdClass();
        $assign_data->id = $data['programid'];
        $assign_data->item = array(ASSIGNTYPE_INDIVIDUAL => array($data['userid'] => 1));
        $assign_data->completiontime = array(ASSIGNTYPE_INDIVIDUAL => array($data['userid'] => $completiontime));
        $assign_data->completionevent = array(ASSIGNTYPE_INDIVIDUAL => array($data['userid'] => 0));
        $assign_data->completioninstance = array(ASSIGNTYPE_INDIVIDUAL => array($data['userid'] => null));
        $assign_data->includechildren = array (ASSIGNTYPE_INDIVIDUAL => array($data['userid'] => 0));

        // Assign item to program.
        $assignmenttoprog = prog_assignments::factory(ASSIGNTYPE_INDIVIDUAL);
        $assignmenttoprog->update_assignments($assign_data, false);

        $program = new program($data['programid']);
        return $program->update_learner_assignments(true);
    }

    /**
     * Add users to a mock program in bulk.
     * Note: This over rides any existing program assignments.
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
     * Assign users to a program with a random completion date, generating some exceptions.
     *
     * @param int $programid Program id
     * @param int $assignmenttype Assignment type
     * @param int $itemid item to be assigned to the program. e.g Audience, position, organization, individual
     * @param null $record
     * @return bool whether this assignment will generate exceptions.
     */
    public function assign_to_program($programid, $assignmenttype, $itemid, $record = null) {
        global $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');

        // Set completion values.
        $now = time();
        $past = date('d/m/Y', $now - (DAYSECS * 14));
        $future = date('d/m/Y', $now + (DAYSECS * 14));
        // We can add other completion options here in future. For now a past date, future date and relative to first login.
        $completionsettings = array(
            array($past,     0,   null, true),
            array($future,   0,   null, false),
            array('3 2', COMPLETION_EVENT_FIRST_LOGIN, null, false),
        );
        $randomcompletion = rand(0, count($completionsettings) - 1);
        list($completiontime, $completionevent, $completioninstance, $exceptions) = $completionsettings[$randomcompletion];

        // Create data.
        $data = new stdClass();
        $data->id = $programid;
        $data->item = array($assignmenttype => array($itemid => 1));
        $data->completiontime = array($assignmenttype => array($itemid => $completiontime));
        $data->completionevent = array($assignmenttype => array($itemid => $completionevent));
        $data->completioninstance = array($assignmenttype => array($itemid => $completioninstance));
        $data->includechildren = array ($assignmenttype => array($itemid => 0));

        // Assign item to program.
        $assignmenttoprog = prog_assignments::factory($assignmenttype);
        $assignmenttoprog->update_assignments($data, false);
        return $exceptions;
    }

    public function fix_program_sortorder($categoryid = 0) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/shezar/program/lib.php');

        if (empty($categoryid)) {
            $categoryid = $DB->get_field_select('course_categories', "MIN(id)", "parent=0");
        }

        // Call prog_fix_program_sortorder to ensure new program is displayed properly and the counts are updated.
        // Needs to be called at the very end!
        prog_fix_program_sortorder($categoryid);
    }

    /**
     * Create certification settings.
     *
     * @param int $programid Program id
     * @param string $activeperiod
     * @param string $windowperiod
     * @param int $recertifydatetype
     */
    public function create_certification_settings($programid, $activeperiod, $windowperiod, $recertifydatetype) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');

        $certification_todb = new stdClass;
        $certification_todb->learningcomptype = CERTIFTYPE_PROGRAM;
        $certification_todb->activeperiod = $activeperiod;
        $certification_todb->windowperiod = $windowperiod;
        $certification_todb->recertifydatetype = $recertifydatetype;
        $certification_todb->timemodified = time();
        $certifid = $DB->insert_record('certif', $certification_todb);
        if ($certifid) {
            $DB->set_field('prog', 'certifid', $certifid , array('id' => $programid));
        }
    }

    /**
     * Get random certification setting.
     */
    public function get_random_certification_setting() {
        global $CFG;
        require_once($CFG->dirroot . '/shezar/certification/lib.php');

        $certifsettings = array(
            array('3 day',   '3 day',   CERTIFRECERT_EXPIRY),
            array('3 day',   '2 day',   CERTIFRECERT_EXPIRY),
            array('5 day',   '2 day',   CERTIFRECERT_EXPIRY),
            array('1 week',  '3 day',   CERTIFRECERT_EXPIRY),
            array('1 year',  '2 month', CERTIFRECERT_EXPIRY),
            array('2 month', '1 week',  CERTIFRECERT_COMPLETION),
        );

        return $certifsettings[rand(0, count($certifsettings) - 1)];
    }

}
