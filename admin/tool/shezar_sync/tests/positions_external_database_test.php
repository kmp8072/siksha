<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @author Simon Player <simon.player@shezarlms.com>
 * @package shezar
 * @subpackage shezar_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/shezar_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/shezar_sync/sources/databaselib.php');

class tool_shezar_sync_user_database_testcase extends advanced_testcase {

    private $configdb = array();
    private $config = array();
    private $configexists = false;

    private $ext_dbconnection = null;

    // Database variable for connection.
    private $dbtype = '';
    private $dbhost = '';
    private $dbport = '';
    private $dbname = '';
    private $dbuser = '';
    private $dbpass = '';
    private $dbtable = '';

    protected $pos_framework_data = array(
        'id' => 1, 'fullname' => 'Postion Framework 1', 'shortname' => 'PFW1', 'idnumber' => '1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $pos_data = array(
        'id' => 1, 'fullname' => 'Data Analyst', 'shortname' => 'Data Analyst', 'idnumber' => '1', 'description' => '', 'frameworkid' => 1,
        'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2,
    );

    public function setUp() {
        global $CFG;

        if (defined('TEST_SYNC_DB_TYPE') ||
            defined('TEST_SYNC_DB_HOST') ||
            defined('TEST_SYNC_DB_PORT') ||
            defined('TEST_SYNC_DB_NAME') ||
            defined('TEST_SYNC_DB_USER') ||
            defined('TEST_SYNC_DB_PASS') ||
            defined('TEST_SYNC_DB_TABLE')) {
            $this->dbtype = defined('TEST_SYNC_DB_TYPE') ? TEST_SYNC_DB_TYPE : '';
            $this->dbhost = defined('TEST_SYNC_DB_HOST') ? TEST_SYNC_DB_HOST : '';
            $this->dbport = defined('TEST_SYNC_DB_PORT') ? TEST_SYNC_DB_PORT : '';
            $this->dbname = defined('TEST_SYNC_DB_NAME') ? TEST_SYNC_DB_NAME : '';
            $this->dbuser = defined('TEST_SYNC_DB_USER') ? TEST_SYNC_DB_USER : '';
            $this->dbpass = defined('TEST_SYNC_DB_PASS') ? TEST_SYNC_DB_PASS : '';
            $this->dbtable = defined('TEST_SYNC_DB_TABLE') ? TEST_SYNC_DB_TABLE : '';
        } else {
            $this->dbtype = $CFG->dbtype;
            $this->dbhost = $CFG->dbhost;
            $this->dbport = !empty($CFG->dboptions['dbport']) ? $CFG->dboptions['dbport'] : '';
            $this->dbname = $CFG->dbname;
            $this->dbuser = $CFG->dbuser;
            $this->dbpass = !empty($CFG->dbpass) ? $CFG->dbpass : '';
            $this->dbtable = $CFG->phpunit_prefix . 'shezar_sync_user_source';
        }

        if (!empty($this->dbtype) &&
            !empty($this->dbhost) &&
            !empty($this->dbname) &&
            !empty($this->dbuser) &&
            !empty($this->dbtable)) {
            // All necessary config variables are set.
            $this->configexists = true;
            $this->ext_dbconnection = setup_sync_DB($this->dbtype, $this->dbhost, $this->dbname, $this->dbuser, $this->dbpass, array('dbport' => $this->dbport));
        } else {
            $this->assertTrue(false, 'HR Import database test configuration was only partially provided');
        }

        parent::setup();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('element_user_enabled', 1, 'shezar_sync');
        set_config('source_user', 'shezar_sync_source_user_database', 'shezar_sync');

        $this->configdb = array(
            'database_dbtype' => $this->dbtype,
            'database_dbhost' => $this->dbhost,
            'database_dbname' => $this->dbname,
            'database_dbuser' => $this->dbuser,
            'database_dbpass' => $this->dbpass,
            'database_dbport' => $this->dbport,
            'database_dbtable' => $this->dbtable,
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'fieldmapping_address' => '',
            'fieldmapping_alternatename' => '',
            'fieldmapping_appraiseridnumber' => '',
            'fieldmapping_auth' => '',
            'fieldmapping_city' => '',
            'fieldmapping_country' => '',
            'fieldmapping_deleted' => '',
            'fieldmapping_department' => '',
            'fieldmapping_description' => '',
            'fieldmapping_email' => '',
            'fieldmapping_emailstop' => '',
            'fieldmapping_firstname' => '',
            'fieldmapping_firstnamephonetic' => '',
            'fieldmapping_idnumber' => '',
            'fieldmapping_institution' => '',
            'fieldmapping_jobassignmentenddate' => '',
            'fieldmapping_jobassignmentfullname' => '',
            'fieldmapping_jobassignmentidnumber' => '',
            'fieldmapping_jobassignmentstartdate' => '',
            'fieldmapping_lang' => '',
            'fieldmapping_lastname' => '',
            'fieldmapping_lastnamephonetic' => '',
            'fieldmapping_manageridnumber' => '',
            'fieldmapping_middlename' => '',
            'fieldmapping_orgidnumber' => '',
            'fieldmapping_password' => '',
            'fieldmapping_phone1' => '',
            'fieldmapping_phone2' => '',
            'fieldmapping_posidnumber' => '',
            'fieldmapping_suspended' => '',
            'fieldmapping_timemodified' => '',
            'fieldmapping_timezone' => '',
            'fieldmapping_url' => '',
            'fieldmapping_username' => '',
            'import_address' => '0',
            'import_alternatename' => '0',
            'import_appraiseridnumber' => '0',
            'import_auth' => '0',
            'import_city' => '0',
            'import_country' => '0',
            'import_deleted' => '1',
            'import_department' => '0',
            'import_description' => '0',
            'import_email' => '1',
            'import_emailstop' => '0',
            'import_firstname' => '1',
            'import_firstnamephonetic' => '0',
            'import_idnumber' => '1',
            'import_institution' => '0',
            'import_jobassignmentenddate' => '1',
            'import_jobassignmentfullname' => '0',
            'import_jobassignmentidnumber' => '0',
            'import_jobassignmentstartdate' => '1',
            'import_lang' => '0',
            'import_lastname' => '1',
            'import_lastnamephonetic' => '0',
            'import_manageridnumber' => '0',
            'import_middlename' => '0',
            'import_orgidnumber' => '0',
            'import_password' => '0',
            'import_phone1' => '0',
            'import_phone2' => '0',
            'import_posidnumber' => '1',
            'import_suspended' => '0',
            'import_timemodified' => '1',
            'import_timezone' => '0',
            'import_url' => '0',
            'import_username' => '1',
        );
        $this->config = array(
            'allow_create' => '1',
            'allow_delete' => '0',
            'allow_update' => '1',
            'allowduplicatedemails' => '0',
            'defaultsyncemail' => '',
            'forcepwchange' => '0',
            'ignoreexistingpass' => '0',
            'sourceallrecords' => '0',
        );

        // Set the config
        set_config('timezone', 'Europe/London');
        set_config('database_dateformat', 'Y-m-d', 'shezar_sync_source_user_database');
        foreach ($this->configdb as $k => $v) {
            set_config($k, $v, 'shezar_sync_source_user_database');
        }

        foreach ($this->config as $k => $v) {
            set_config($k, $v, 'shezar_sync_element_user');
        }

        // Create a Position and framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'pos_framework' => array($this->pos_framework_data),
            'pos' => array($this->pos_data)
        )));
    }

    public function tearDown() {
        if ($this->configexists) {
            // Drop sync table.
            $dbman = $this->ext_dbconnection->get_manager();
            $table = new xmldb_table($this->dbtable);
            if ($dbman->table_exists($this->dbtable)) {
                $dbman->drop_table($table, $this->dbtable);
            }
        }
    }

    public function create_external_user_table() {
        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table, $this->dbtable);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('email', XMLDB_TYPE_CHAR, '100');
        $table->add_field('password', XMLDB_TYPE_CHAR, '32');
        $table->add_field('posidnumber', XMLDB_TYPE_CHAR, '100');
        $table->add_field('jobassignmentstartdate', XMLDB_TYPE_CHAR, '255');
        $table->add_field('jobassignmentenddate', XMLDB_TYPE_CHAR, '255');

        /// Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Add indexes
        $table->add_index('username', XMLDB_INDEX_NOTUNIQUE, array('username'));
        $table->add_index('idnumber', XMLDB_INDEX_NOTUNIQUE, array('idnumber'));
        $table->add_index('posidnumber', XMLDB_INDEX_NOTUNIQUE, array('posidnumber'));

        /// Create the table
        $dbman->create_table($table, false, false);
    }

    public function populate_external_user_table($newdata) {
        // First, lets create user table.
        $this->create_external_user_table();

        $defaultdata = array(
            "idnumber" => 1,
            "timemodified" => 0,
            "username" => "user1",
            "deleted" => 0,
            "firstname" => "user1",
            "lastname" => "user1",
            "email" => "user1@local.com",
            "password" => "password",
            "posidnumber" => 0,
            "jobassignmentstartdate" => "",
            "jobassignmentenddate" => ""
        );

        $data = array_merge($defaultdata, $newdata);
        $this->ext_dbconnection->insert_record($this->dbtable, $data, true);
    }

    public function run_sync() {
        $elements = shezar_sync_get_elements(true);
        $element = $elements['user'];
        $result = $element->sync();
        sleep(1);
        return $result;
    }

    public function test_sync_database_connect() {
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->assertInstanceOf('moodle_database', $this->ext_dbconnection);
    }

    public function test_job_assignment_dates_with_timestamp() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->resetAfterTest();

        // Populate the external db table.
        $this->populate_external_user_table(array("posidnumber" => 1, "jobassignmentstartdate" => 1445731200, "jobassignmentenddate" => 1477353600 ));

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check dates synced correctly.
        $this->assertTrue($DB->record_exists('job_assignment', array("positionid" => 1, "startdate" => 1445731200, "enddate" => 1477353600)));
    }

    public function test_job_assignment_dates_with_date_string() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->resetAfterTest();

        // Populate the external db table.
        $this->populate_external_user_table(array("posidnumber" => 1, "jobassignmentstartdate" => "2015-10-25", "jobassignmentenddate" => "2016-10-25" ));

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check dates synced correctly.
        $record = $DB->get_record("job_assignment", array("positionid" => 1));
        $this->assertEquals("2015-10-25", date('Y-m-d', $record->startdate));
        $this->assertEquals("2016-10-25", date('Y-m-d', $record->enddate));
    }

    public function test_job_assignment_dates_with_empty_data() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->resetAfterTest();

        // Populate the external db table.
        $this->populate_external_user_table(array("posidnumber" => 1, "jobassignmentstartdate" => "", "jobassignmentenddate" => "" ));

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check dates synced correctly.
        $this->assertTrue($DB->record_exists('job_assignment', array("positionid" => 1, "startdate" => null, "enddate" => null)));
    }

    public function test_job_assignment_dates_with_zero_data() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->resetAfterTest();

        // Populate the external db table.
        $this->populate_external_user_table(array("posidnumber" => 1, "jobassignmentstartdate" => 0, "jobassignmentenddate" => 0 ));

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check dates synced correctly.
        $this->assertTrue($DB->record_exists('job_assignment', array("positionid" => 1, "startdate" => null, "enddate" => null)));
    }

    public function test_job_assignment_dates_with_null_data() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->resetAfterTest();

        // Populate the external db table.
        $this->populate_external_user_table(array("posidnumber" => 1, "jobassignmentstartdate" => null, "jobassignmentenddate" => null));

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check dates synced correctly.
        $this->assertTrue($DB->record_exists('job_assignment', array("positionid" => 1, "startdate" => null, "enddate" => null)));
    }

    public function test_job_assignment_dates_resync_with_empty_data() {
        global $DB;
        if (!$this->configexists) {
            $this->markTestSkipped();
        }
        $this->resetAfterTest();

        // Populate the external db table.
        $this->populate_external_user_table(array("posidnumber" => 1, "jobassignmentstartdate" => '', "jobassignmentenddate" => ''));

        // Create a user position assignment.
        $DB->insert_record("job_assignment", (object)array(
            "fullname" => "job1",
            "timecreated" => 0,
            "timemodified" => 0,
            "usermodified" => 0,
            "userid" => 3,
            "positionid" => 1,
            "startdate" => 1445731200,
            "enddate" => 1477353600,
            "positionassignmentdate" => 1445731200,
            "sortorder" => 1
        ));

        // Check record inserted.
        $this->assertTrue($DB->record_exists('job_assignment', array("userid" => 3, "positionid" => 1, "startdate" => 1445731200, "enddate" => 1477353600)));

        // Run and test the sync.
        $this->assertTrue($this->run_sync());

        // Check dates synced correctly.
        $this->assertTrue($DB->record_exists('job_assignment', array("positionid" => 1, "startdate" => null, "enddate" => null)));
    }
}
