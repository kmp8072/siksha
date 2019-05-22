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
 * @package shezar
 * @subpackage cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/shezar/cohort/lib.php');
require_once($CFG->dirroot . '/shezar/core/db/utils.php');

/**
 * Test functions used in program upgrades.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit shezar_program_upgrade_testcase
 *
 */
class shezar_program_upgrade_testcase extends reportcache_advanced_testcase {

    private $tables = array();

    protected $prog_compl_data = array(
        array('id' => 1, 'programid' => 1, 'userid' => 1, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 0),
        array('id' => 2, 'programid' => 1, 'userid' => 1, 'coursesetid' => 0, 'status' => 0, 'timecompleted' => 14156782),
        array('id' => 3, 'programid' => 1, 'userid' => 1, 'coursesetid' => 1, 'status' => 3, 'timecompleted' => 14178234),
        array('id' => 4, 'programid' => 1, 'userid' => 1, 'coursesetid' => 1, 'status' => 2, 'timecompleted' => 14168234),
        array('id' => 5,  'programid' => 1, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14156782),
        array('id' => 6,  'programid' => 1, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14146782),
        array('id' => 7,  'programid' => 1, 'userid' => 5, 'coursesetid' => 1, 'status' => 2, 'timecompleted' => 0),
        array('id' => 8,  'programid' => 1, 'userid' => 5, 'coursesetid' => 1, 'status' => 3, 'timecompleted' => 14567890),
        array('id' => 9,  'programid' => 1, 'userid' => 5, 'coursesetid' => 1, 'status' => 2, 'timecompleted' => 0),
        array('id' => 10, 'programid' => 1, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14176782),
        array('id' => 11, 'programid' => 1, 'userid' => 7, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14176782),
        array('id' => 12, 'programid' => 2, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14186782),
        array('id' => 13, 'programid' => 1, 'userid' => 8, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14176782),
        array('id' => 14, 'programid' => 2, 'userid' => 2, 'coursesetid' => 0, 'status' => 0, 'timestarted' => 14196782,
            'timedue' => 14176784, 'timecompleted' => 0),
        array('id' => 15, 'programid' => 2, 'userid' => 4, 'coursesetid' => 0, 'status' => 0, 'timestarted' => 0,
            'timedue' => 14176782, 'timecompleted' => 0),
        array('id' => 16, 'programid' => 2, 'userid' => 5, 'coursesetid' => 0, 'status' => 1, 'timestarted' => 0,
            'timedue' => 14176782, 'timecompleted' => 0),
        array('id' => 17, 'programid' => 2, 'userid' => 2, 'coursesetid' => 0, 'status' => 0, 'timestarted' => 14186782,
            'timedue' => 14176782, 'timecompleted' => 0),
    );

    protected $prog_user_assign_data = array(
        array('id' => 1, 'programid' => 1, 'userid' => 1, 'assignmentid' => 3, 'timeassigned' => 14186782, 'exceptionstatus' => 0),
        array('id' => 2, 'programid' => 2, 'userid' => 3, 'assignmentid' => 1, 'timeassigned' => 14186782, 'exceptionstatus' => 1),
        array('id' => 3, 'programid' => 2, 'userid' => 1, 'assignmentid' => 3, 'timeassigned' => 14186786, 'exceptionstatus' => 0),
        array('id' => 4, 'programid' => 2, 'userid' => 3, 'assignmentid' => 1, 'timeassigned' => 14186780, 'exceptionstatus' => 0),
        array('id' => 5, 'programid' => 1, 'userid' => 1, 'assignmentid' => 3, 'timeassigned' => 14186787, 'exceptionstatus' => 3),
        array('id' => 6, 'programid' => 3, 'userid' => 4, 'assignmentid' => 5, 'timeassigned' => 14186782, 'exceptionstatus' => 0),
        array('id' => 7, 'programid' => 2, 'userid' => 2, 'assignmentid' => 3, 'timeassigned' => 14186782, 'exceptionstatus' => 0),
        array('id' => 8, 'programid' => 3, 'userid' => 4, 'assignmentid' => 5, 'timeassigned' => 14186790, 'exceptionstatus' => 3),
        array('id' => 9, 'programid' => 2, 'userid' => 3, 'assignmentid' => 5, 'timeassigned' => 14186790, 'exceptionstatus' => 0),
        array('id' => 10, 'programid' => 3, 'userid' => 3, 'assignmentid' => 2, 'timeassigned' => 14186795, 'exceptionstatus' => 3),
        array('id' => 11, 'programid' => 3, 'userid' => 3, 'assignmentid' => 2, 'timeassigned' => 14186790, 'exceptionstatus' => 0),
        array('id' => 12, 'programid' => 5, 'userid' => 3, 'assignmentid' => 5, 'timeassigned' => 14186790, 'exceptionstatus' => 0),
    );

    /**
     * Setup.
     */
    public function setUp() {
        global $DB;
        parent::setUp();

        $dbman = $DB->get_manager(); // Loads DDL libs.

        $table = new xmldb_table('test_prog_compl');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('coursesetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, '0');
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timedue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('organisationid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('positionid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('progcomp_pro_ix', false, array('programid'));
        $table->add_index('progcomp_use_ix', false, array('userid'));
        $table->add_index('progcomp_cou_ix', false, array('coursesetid'));
        $table->setComment("Copy of prog_completion only for this test");

        $this->tables[$table->getName()] = $table;

        $table = new xmldb_table('test_prog_user_assig');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('assignmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('timeassigned', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('exceptionstatus', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('proguserassi_pro_ix', false, array('programid'));
        $table->add_index('proguserassi_use_ix', false, array('userid'));
        $table->add_index('proguserassi_ass_ix', false, array('assignmentid'));
        $table->add_index('proguserassi_exc_ix', false, array('exceptionstatus'));
        $table->setComment("Copy of prog_user_assignment only for this test");

        $this->tables[$table->getName()] = $table;

        // Create tables and check they exist.
        $tableprogcompl = $this->tables['test_prog_compl'];
        $dbman->create_table($tableprogcompl);
        $this->assertTrue($dbman->table_exists('test_prog_compl'));

        $tableproguserass = $this->tables['test_prog_user_assig'];
        $dbman->create_table($tableproguserass);
        $this->assertTrue($dbman->table_exists('test_prog_user_assig'));

        // Load data for prog_completion.
        $this->loadDataSet($this->createArrayDataSet(array(
            'test_prog_compl' => $this->prog_compl_data,
            'test_prog_user_assig' => $this->prog_user_assign_data
        )));

        // Check the data was loaded.
        $this->assertEquals(17, $DB->count_records('test_prog_compl'));
        $this->assertEquals(12, $DB->count_records('test_prog_user_assig'));
    }

    /**
     * Test shezar_upgrade_delete_duplicate_records.
     */
    public function test_delete_prog_completion_duplicates() {
        global $DB;
        $this->resetAfterTest(true);

        // Call the delete duplicates function. It will delete duplicate records based on programid, userid and status.
        // It will also order the query by status DESC and take the first record as the one to keep. Also, it will
        // compare all dates (3rd param) to update those fields if an oldest datetime is found.
        shezar_upgrade_delete_duplicate_records(
            'test_prog_compl',
            array('programid', 'userid', 'coursesetid'),
            'status DESC, timedue DESC',
            'shezar_prog_completion_to_history'
        );

        // Checking expected results.
        $this->assertEquals(10, $DB->count_records('test_prog_compl'));
        $this->assertTrue($DB->record_exists('test_prog_compl',
            array('id' => 1, 'programid' => 1, 'userid' => 1, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 0)));
        $this->assertTrue($DB->record_exists('test_prog_compl',
            array('id' => 3, 'programid' => 1, 'userid' => 1, 'coursesetid' => 1, 'status' => 3, 'timecompleted' => 14178234)));
        $this->assertTrue($DB->record_exists('test_prog_compl',
            array('id' => 5,  'programid' => 1, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14156782)));
        $this->assertTrue($DB->record_exists('test_prog_compl',
            array('id' => 8,  'programid' => 1, 'userid' => 5, 'coursesetid' => 1, 'status' => 3, 'timecompleted' => 14567890)));
        $this->assertTrue($DB->record_exists('test_prog_compl',
            array('id' => 11, 'programid' => 1, 'userid' => 7, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14176782)));
        $this->assertTrue($DB->record_exists('test_prog_compl',
            array('id' => 12, 'programid' => 2, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14186782)));
        $this->assertTrue($DB->record_exists('test_prog_compl',
            array('id' => 13, 'programid' => 1, 'userid' => 8, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14176782)));
        $this->assertTrue($DB->record_exists('test_prog_compl', array('id' => 14, 'programid' => 2, 'userid' => 2,
            'coursesetid' => 0, 'status' => 0, 'timestarted' => 14196782, 'timedue' => 14176784, 'timecompleted' => 0)));
        $this->assertTrue($DB->record_exists('test_prog_compl', array('id' => 15, 'programid' => 2, 'userid' => 4,
            'coursesetid' => 0, 'status' => 0, 'timestarted' => 0, 'timedue' => 14176782, 'timecompleted' => 0)));
        $this->assertTrue($DB->record_exists('test_prog_compl', array('id' => 16, 'programid' => 2, 'userid' => 5,
            'coursesetid' => 0, 'status' => 1, 'timestarted' => 0, 'timedue' => 14176782, 'timecompleted' => 0)));

        // Check that the rest of the records went to the history table.
        $this->assertEquals(7, $DB->count_records('prog_completion_history'));
        $this->assertTrue($DB->record_exists('prog_completion_history',
            array('programid' => 1, 'userid' => 1, 'coursesetid' => 0, 'status' => 0, 'timecompleted' => 14156782)));
        $this->assertTrue($DB->record_exists('prog_completion_history',
            array('programid' => 1, 'userid' => 1, 'coursesetid' => 1, 'status' => 2, 'timecompleted' => 14168234)));
        $this->assertTrue($DB->record_exists('prog_completion_history',
            array('programid' => 1, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14146782)));
        $this->assertTrue($DB->record_exists('prog_completion_history',
            array('programid' => 1, 'userid' => 5, 'coursesetid' => 1, 'status' => 2, 'timecompleted' => 0)));
        $this->assertTrue($DB->record_exists('prog_completion_history',
            array('programid' => 1, 'userid' => 5, 'coursesetid' => 1, 'status' => 2, 'timecompleted' => 0)));
        $this->assertTrue($DB->record_exists('prog_completion_history',
            array('programid' => 1, 'userid' => 3, 'coursesetid' => 0, 'status' => 1, 'timecompleted' => 14176782)));
        $this->assertTrue($DB->record_exists('prog_completion_history',
            array('programid' => 2, 'userid' => 2, 'coursesetid' => 0, 'status' => 0, 'timestarted' => 14186782,
                'timedue' => 14176782, 'timecompleted' => 0)));
    }

    /**
     * Test shezar_upgrade_delete_duplicate_records.
     */
    public function test_delete_prog_user_assignment() {
        global $DB;
        $this->resetAfterTest(true);

        // Call the delete duplicates function. It will delete duplicate records based on programid, userid and assignmentid.
        shezar_upgrade_delete_duplicate_records(
            'test_prog_user_assig',
            array('programid', 'userid', 'assignmentid'),
            'timeassigned DESC'
        );

        // Checking expected results.
        $this->assertEquals(8, $DB->count_records('test_prog_user_assig'));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 1, 'userid' => 1, 'assignmentid' => 3, 'timeassigned' => 14186787, 'exceptionstatus' => 3)));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 2, 'userid' => 3, 'assignmentid' => 1, 'timeassigned' => 14186782, 'exceptionstatus' => 1)));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 2, 'userid' => 1, 'assignmentid' => 3, 'timeassigned' => 14186786, 'exceptionstatus' => 0)));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 3, 'userid' => 4, 'assignmentid' => 5, 'timeassigned' => 14186790, 'exceptionstatus' => 3)));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 2, 'userid' => 2, 'assignmentid' => 3, 'timeassigned' => 14186782, 'exceptionstatus' => 0)));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 2, 'userid' => 3, 'assignmentid' => 5, 'timeassigned' => 14186790, 'exceptionstatus' => 0)));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 3, 'userid' => 3, 'assignmentid' => 2, 'timeassigned' => 14186795, 'exceptionstatus' => 3)));
        $this->assertTrue($DB->record_exists('test_prog_user_assig',
            array('programid' => 5, 'userid' => 3, 'assignmentid' => 5, 'timeassigned' => 14186790, 'exceptionstatus' => 0)));
    }
}
