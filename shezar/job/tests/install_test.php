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
 * @author Nathan Lewis <nathan.lewis@shezarlms.com>
 * @package shezar_job
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/core/db/upgradelib.php');
require_once($CFG->dirroot . '/shezar/core/utils.php'); // Needed so that shezar_get_lineage is defined.
require_once($CFG->libdir . '/formslib.php'); // Needed so that $TEXTAREA_OPTIONS is defined.
require_once($CFG->libdir . '/upgradelib.php'); // Needed so that upgrade_set_timeout is defined.
require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * This covers shezar_core_upgrade_multiple_jobs, which is triggered when installing a new site or when upgrading from an older one.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose shezar_job_install_testcase shezar/job/tests/install_test.php
 */
class shezar_job_install_testcase extends reportcache_advanced_testcase {

    /**
     * Set up some stuff that will be useful for most tests.
     */
    public function setUp() {
        parent::setup();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * On a new install, nothing should be executed, because the old pos_assignment table will not be detected.
     *
     * To ensure that the upgrade code doesn't run, we can insert a known record into job_assignment and check
     * that it is still there after running the install function. The first step during an upgrade is to delete
     * the job_assignment table, so if the record is still there afterwards then the upgrade code didn't run.
     */
    public function test_new_install() {
        $beforedata = array(
            'userid' => 12345,
            'fullname' => 'new install fullname',
            'shortname' => 'newinstallshortname',
            'idnumber' => 'newinstallidnumber'
        );
        $beforeja = \shezar_job\job_assignment::create($beforedata);

        shezar_core_upgrade_multiple_jobs();

        $afterja = \shezar_job\job_assignment::get_with_id($beforeja->id);
        $this->assertEquals($beforedata['userid'], $afterja->userid);
        $this->assertEquals($beforedata['fullname'], $afterja->fullname);
        $this->assertEquals($beforedata['shortname'], $afterja->shortname);
        $this->assertEquals($beforedata['idnumber'], $afterja->idnumber);
    }

    /**
     * Create tables as they were pre-9.0.
     */
    private function create_old_tables() {
        global $DB;

        $dbman = $DB->get_manager();

        // Define table pos_assignment to be created.
        $table = new xmldb_table('pos_assignment');

        // Adding fields to table pos_assignment.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fullname', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timevalidfrom', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timevalidto', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('organisationid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('positionid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('reportstoid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '18', null, null, null, '1');
        $table->add_field('managerid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('managerpath', XMLDB_TYPE_CHAR, '1024', null, null, null, null);

        // Adding keys to table pos_assignment.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('posassi_org_fk', XMLDB_KEY_FOREIGN, array('organisationid'), 'org', array('id'));
        $table->add_key('posassi_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('posassi_pos_fk', XMLDB_KEY_FOREIGN, array('positionid'), 'pos', array('id'));
        $table->add_key('posassi_rep_fk', XMLDB_KEY_FOREIGN, array('reportstoid'), 'role_assignments', array('id'));
        $table->add_key('posassi_man_fk', XMLDB_KEY_FOREIGN, array('managerid'), 'user', array('id'));
        $table->add_key('posassi_type_fk', XMLDB_KEY_FOREIGN, array('type'), 'pos_type', array('id'));

        // Conditionally launch create table for pos_assignment.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table pos_assignment_history to be created.
        $table = new xmldb_table('pos_assignment_history');

        // Adding fields to table pos_assignment_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fullname', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timevalidfrom', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timevalidto', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timefinished', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('organisationid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('positionid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('reportstoid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table pos_assignment_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('posassihist_org_fk', XMLDB_KEY_FOREIGN, array('organisationid'), 'org', array('id'));
        $table->add_key('posassihist_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('posassihist_pos_fk', XMLDB_KEY_FOREIGN, array('positionid'), 'pos', array('id'));
        $table->add_key('posassihist_rep_fk', XMLDB_KEY_FOREIGN, array('reportstoid'), 'role_assignments', array('id'));

        // Conditionally launch create table for pos_assignment_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table prog_pos_assignment to be created.
        $table = new xmldb_table('prog_pos_assignment');

        // Adding fields to table prog_pos_assignment.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('positionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timeassigned', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table prog_pos_assignment.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for prog_pos_assignment.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table temporary_manager to be created.
        $table = new xmldb_table('temporary_manager');

        // Adding fields to table temporary_manager.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tempmanagerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expirytime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table temporary_manager.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('tempmanagerid', XMLDB_KEY_FOREIGN, array('tempmanagerid'), 'user', array('id'));
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', array('id'));

        // Conditionally launch create table for temporary_manager.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Additionally, make some changes in appraisals and face to face, to make them look like the old versions.

        // Fields jobassignmentid and jobassignmentlastmodified need to be dropped from appraisal_user_assignment.
        $table = new xmldb_table('appraisal_user_assignment');
        $key = new xmldb_key('appruserassi_job_fk', XMLDB_KEY_FOREIGN, ['jobassignmentid'], 'job_assignment', ['id']);
        $dbman->drop_key($table, $key);
        $field = new xmldb_field('jobassignmentid');
        $dbman->drop_field($table, $field);
        $field = new xmldb_field('jobassignmentlastmodified');
        $dbman->drop_field($table, $field);

        // Field positiontype needs to be added to facetoface_signups.
        $table = new xmldb_table('facetoface_signups');
        $oldfield = new xmldb_field('jobassignmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->rename_field($table, $oldfield, 'positionassignmentid');
        $field = new xmldb_field('positiontype', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field);
        $field = new xmldb_field('positionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field);
        $table = new xmldb_table('facetoface');
        $oldfield = new xmldb_field('selectjobassignmentonsignup', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $dbman->rename_field($table, $oldfield, 'selectpositiononsignup');
        $oldfield = new xmldb_field('forceselectjobassignment', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $dbman->rename_field($table, $oldfield, 'forceselectposition');

        // TODO make sure program assignments are tested.
    }

    /**
     * When a site upgrades, the job module will be installed and will activate install.php, which will perform the
     * steps required to move the data from pos_assignment to job_assignment. To perform this test, we will create the
     * pos_assignment table (and others) and run the install function.
     */
    public function test_upgrade_install() {
        global $CFG, $DB;

        $dbman = $DB->get_manager();

        $this->create_old_tables();

        // Set up data in the old tables.
        $users = array();
        for ($i = 1; $i <= 40; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create pos_assignment records.
        $now = time();
        $admin = get_admin();
        $templateposassign = array(
            'fullname' => 'fullname',
            'type' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $admin->id,
        );
        $posassigndata = array(
            array('userid' => $users[2]->id, 'managerid' => $users[1]->id),
            array('userid' => $users[3]->id),
            array('userid' => $users[4]->id),
            array('userid' => $users[5]->id, 'managerid' => $users[2]->id),
            array('userid' => $users[6]->id),
            array('userid' => $users[7]->id, 'managerid' => $users[3]->id),
            array('userid' => $users[9]->id, 'managerid' => $users[8]->id),
            array('userid' => $users[11]->id),
            array('userid' => $users[12]->id, 'positionid' => 432),
            array('userid' => $users[13]->id, 'managerid' => $users[12]->id),
            array('userid' => $users[15]->id, 'managerid' => $users[14]->id, 'type' => 2),
            array('userid' => $users[16]->id, 'type' => 2),
            array('userid' => $users[17]->id, 'managerid' => $users[16]->id),
            array('userid' => $users[18]->id, 'type' => 2, 'organisationid' => 543),
            array('userid' => $users[19]->id, 'type' => 1, 'idnumber' => 'dupidnum'),
            array('userid' => $users[19]->id, 'type' => 2, 'idnumber' => 'dupidnum'),
        );
        foreach ($posassigndata as $posassign) {
            $DB->insert_record('pos_assignment', (object)array_merge($templateposassign, $posassign));
        }

        // Create temporary_manager records.
        $now = time();
        $admin = get_admin();
        $templatetempman = array(
            'timemodified' => $now,
            'usermodified' => $admin->id,
        );
        $tempmandata = array(
            array('userid' => $users[3]->id, 'tempmanagerid' => $users[1]->id, 'expirytime' => $now + DAYSECS * 1),
            array('userid' => $users[4]->id, 'tempmanagerid' => $users[2]->id, 'expirytime' => $now + DAYSECS * 2),
            array('userid' => $users[6]->id, 'tempmanagerid' => $users[3]->id, 'expirytime' => $now + DAYSECS * 3),
            array('userid' => $users[11]->id, 'tempmanagerid' => $users[10]->id, 'expirytime' => $now + DAYSECS * 10),
        );
        foreach ($tempmandata as $tempman) {
            $DB->insert_record('temporary_manager', (object)array_merge($templatetempman, $tempman));
        }

        // Create some dodgy data which should be cleaned up in install Part 1.
        $templateposassign = array(
            'fullname' => 'fullname',
            'type' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $admin->id,
        );
        $posassigndata = array(
            array('userid' => null, 'type' => null), // Both null.
            array('userid' => $users[40]->id, 'type' => null), // Type null.
            array('userid' => null), // Userid null.
            array('userid' => $users[39]->id),
            array('userid' => $users[39]->id), // Duplicates.
            array('userid' => $users[38]->id, 'managerid' => $users[37]->id), // Deleted manager (see a few lines below).
        );
        foreach ($posassigndata as $posassign) {
            $DB->insert_record('pos_assignment', (object)array_merge($templateposassign, $posassign));
        }
        $DB->set_field('user', 'deleted', 1, array('id' => $users[37]->id));

        // Run the function.
        shezar_core_upgrade_multiple_jobs();

        // Get the raw data which will be used to calculate what the manager paths should look like.
        $managerrelations = $DB->get_records_menu('job_assignment', array(), 'id', 'id, managerjaid');

        // Test the results in the new tables.
        $expectedresults = array(
            $users[1]->id .  '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[1]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[2]->id .  '_1' => array(
                'managerid' => $users[1]->id,
                'managerjapathuserids' => array($users[1]->id, $users[2]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[3]->id .  '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[3]->id),
                'tempmanagerid' => $users[1]->id,
                'tempmanagerexpirydate' => $now + DAYSECS * 1,
                'positionid' => null),
            $users[4]->id .  '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[4]->id),
                'tempmanagerid' => $users[2]->id,
                'tempmanagerexpirydate' => $now + DAYSECS * 2,
                'positionid' => null),
            $users[5]->id .  '_1' => array(
                'managerid' => $users[2]->id,
                'managerjapathuserids' => array($users[1]->id, $users[2]->id, $users[5]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[6]->id .  '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[6]->id),
                'tempmanagerid' => $users[3]->id,
                'tempmanagerexpirydate' => $now + DAYSECS * 3,
                'positionid' => null),
            $users[7]->id .  '_1' => array(
                'managerid' => $users[3]->id,
                'managerjapathuserids' => array($users[3]->id, $users[7]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[8]->id .  '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[8]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[9]->id .  '_1' => array(
                'managerid' => $users[8]->id,
                'managerjapathuserids' => array($users[8]->id, $users[9]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[10]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[10]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[11]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[11]->id),
                'tempmanagerid' => $users[10]->id,
                'tempmanagerexpirydate' => $now + DAYSECS * 10,
                'positionid' => null),
            $users[12]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[12]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => 432),
            $users[13]->id . '_1' => array(
                'managerid' => $users[12]->id,
                'managerjapathuserids' => array($users[12]->id, $users[13]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[14]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[14]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[15]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[15]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[15]->id . '_2' => array(
                'managerid' => $users[14]->id,
                'managerjapathuserids' => array($users[14]->id, $users[15]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[16]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[16]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[16]->id . '_2' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[16]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[17]->id . '_1' => array(
                'managerid' => $users[16]->id,
                'managerjapathuserids' => array($users[16]->id, $users[17]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null),
            $users[18]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[18]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null,
                'organisationid' => null),
            $users[18]->id . '_2' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[18]->id),
                'tempmanagerid' => null,
                'tempmanagerexpirydate' => null,
                'positionid' => null,
                'organisationid' => 543),
            $users[19]->id . '_1' => array(
                'idnumber' => 'dupidnum [1]'),
            $users[19]->id . '_2' => array(
                'idnumber' => 'dupidnum [2]'),
            $users[38]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[38]->id)),
            $users[39]->id . '_1' => array(
                'managerid' => null,
                'managerjapathuserids' => array($users[39]->id)),
        );

        $actualresultids = $DB->get_fieldset_select('job_assignment', 'id', '1=1');

        // Match each actual result to an expected result.
        foreach ($actualresultids as $actualresultid) {
            $actualja = \shezar_job\job_assignment::get_with_id($actualresultid);

            // Make sure the actual result is somewhere in the expected results.
            $expectedkey = $actualja->userid . '_' . $actualja->sortorder;
            $this->assertArrayHasKey($expectedkey, $expectedresults);
            $expectedresult = $expectedresults[$expectedkey];

            // Check each of the specified expected fields. Note that userid and sortorder are implicitly checked already.
            foreach ($expectedresult as $key => $value) {
                if ($key == 'managerjapathuserids') {
                    $path = '';
                    foreach ($value as $userid) {
                        if ($userid == $actualja->userid) {
                            $pathja = $actualja;
                        } else {
                            $pathja = \shezar_job\job_assignment::get_first($userid);
                        }
                        $path .= '/' . $pathja->id;
                    }
                    $this->assertEquals($path, $actualja->managerjapath);
                } else if (is_null($value)) {
                    $this->assertNull($actualja->{$key}, $expectedkey . ' ' . $key);
                } else {
                    $this->assertEquals($value, $actualja->{$key});
                }
            }
        }

        // By checking counts, and because userid+sortorder must be unique, we can be sure that nothing is missed.
        $this->assertEquals(count($expectedresults), count($actualresultids));

        // Conform that the job_assignment table matches the xml. Doesn't check indexes or keys, sore be careful there.
        $schema = new xmldb_structure('export');
        $schema->setVersion($CFG->version);
        $xmldb_file = new xmldb_file($CFG->dirroot . '/shezar/core/db/install.xml');
        $xmldb_file->loadXMLStructure();
        $structure = $xmldb_file->getStructure();
        $tables = $structure->getTables();
        foreach ($tables as $table) {
            $table->setPrevious(null);
            $table->setNext(null);
            $schema->addTable($table);
        }
        $result = $dbman->check_database_schema($schema, array('extratables' => false));
        $this->assertEmpty($result);

        // Check that these tables were removed.
        $posasignhisttable = new xmldb_table('pos_assignment_history');
        $this->assertFalse($dbman->table_exists($posasignhisttable));
        $progposassigntable = new xmldb_table('prog_pos_assignment');
        $this->assertFalse($dbman->table_exists($progposassigntable));
        $tempmantable = new xmldb_table('temporary_manager');
        $this->assertFalse($dbman->table_exists($tempmantable));
    }

    /**
     * Test Part 7 of the install - make sure appraisals point at the correct job assignments.
     */
    public function test_upgrade_install_appraisals() {
        global $DB;

        $this->create_old_tables();

        // Add data for appraisals test.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = new stdClass();
        $data->userid = $user1->id;
        $data->appraisalid = 123;
        $DB->insert_record('appraisal_user_assignment', $data);

        $data = new stdClass();
        $data->userid = $user2->id;
        $data->appraisalid = 123;
        $DB->insert_record('appraisal_user_assignment', $data);

        $timecreated = time() - DAYSECS * 200;
        $timemodified = time() - DAYSECS * 100;
        $data = new stdClass();
        $data->userid = $user2->id;
        $data->fullname = 'fullname';
        $data->timecreated = $timecreated;
        $data->timemodified = $timemodified;
        $data->usermodified = $user2->id;
        $user2posassignid = $DB->insert_record('pos_assignment', $data);

        // Run the function.
        $beforetime = time();
        shezar_core_upgrade_multiple_jobs();
        $aftertime = time();

        // Test results.
        $ja1 = \shezar_job\job_assignment::get_first($user1->id);
        $ja2 = \shezar_job\job_assignment::get_with_id($user2posassignid);
        $this->assertEquals('/' . $ja1->id, $ja1->managerjapath);
        $this->assertEquals(2, $DB->count_records('appraisal_user_assignment'));
        $aua1 = $DB->get_record('appraisal_user_assignment', array('userid' => $user1->id));
        $aua2 = $DB->get_record('appraisal_user_assignment', array('userid' => $user2->id));
        $this->assertEquals($ja1->id, $aua1->jobassignmentid);
        $this->assertEquals($ja2->id, $aua2->jobassignmentid);
        $this->assertGreaterThanOrEqual($beforetime, $aua1->jobassignmentlastmodified);
        $this->assertLessThanOrEqual($aftertime, $aua1->jobassignmentlastmodified);
        $this->assertEquals($timemodified, $aua2->jobassignmentlastmodified);
    }

    /**
     * Test Part 8 of the install - make sure f2f signups point at the correct job assignments.
     */
    public function test_upgrade_install_f2f_signups() {
        $this->create_old_tables();

        // Add data for f2f signups test.


        // Run the function.
        shezar_core_upgrade_multiple_jobs();

        // Test results.
    }

    /**
     * Copies assign_to_program from shezar_program_generator but allows us to still use
     * the ASSIGNTYPE_MANAGER constant. This is useful for adding prog_assignment records as if they
     * were done pre-9.0.
     *
     * @param $programid
     * @param $assignmenttype
     * @param $itemid
     * @param null $record
     * @return mixed
     * @throws Exception
     */
    public function assign_to_program_assigntype_manager($programid, $itemid) {
        global $CFG;
        require_once($CFG->dirroot . '/shezar/program/lib.php');

        $assignmenttype = ASSIGNTYPE_MANAGER;

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
        $assignmenttoprog = new managers_category();
        // The id will be set to the new constant, ASSIGNTYPE_MANAGERJA, overwrite it with the old one.
        $assignmenttoprog->id = $assignmenttype;
        $assignmenttoprog->update_assignments($data, false);
        return $exceptions;
    }

    /**
     * Test Part 9 of the install - make sure program assignments point at the correct job assignments.
     */
    public function test_upgrade_install_prog_assignments() {
        global $DB;
        $this->create_old_tables();

        // Add data for prog_assignment test.
        $users = array();
        for ($i = 1; $i <= 40; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user();
        }

        $now = time();
        $admin = get_admin();
        $templateposassign = array(
            'fullname' => 'fullname',
            'type' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $admin->id,
        );
        $posassigndata = array(
            array('userid' => $users[2]->id, 'managerid' => $users[1]->id),
            array('userid' => $users[3]->id),
            array('userid' => $users[4]->id),
            array('userid' => $users[5]->id, 'managerid' => $users[2]->id),
            array('userid' => $users[6]->id, 'managerid' => $users[2]->id),
            array('userid' => $users[7]->id, 'managerid' => $users[3]->id),
            array('userid' => $users[9]->id, 'managerid' => $users[8]->id),
            array('userid' => $users[11]->id),
            array('userid' => $users[12]->id, 'positionid' => 432),
            array('userid' => $users[13]->id, 'managerid' => $users[12]->id),
            array('userid' => $users[15]->id, 'managerid' => $users[14]->id, 'type' => 2),
            array('userid' => $users[16]->id, 'type' => 2),
            array('userid' => $users[17]->id, 'managerid' => $users[16]->id),
            array('userid' => $users[18]->id, 'type' => 2, 'organisationid' => 543),
            array('userid' => $users[19]->id, 'type' => 1, 'idnumber' => 'dupidnum'),
            array('userid' => $users[19]->id, 'type' => 2, 'idnumber' => 'dupidnum'),
        );
        foreach ($posassigndata as $posassign) {
            $DB->insert_record('pos_assignment', (object)array_merge($templateposassign, $posassign));
        }

        // Create audiences
        $data_generator = $this->getDataGenerator();
        $audience1 = $data_generator->create_cohort();
        cohort_add_member($audience1->id, $users[20]->id);
        cohort_add_member($audience1->id, $users[21]->id);
        cohort_add_member($audience1->id, $users[22]->id);

        $program1 = $data_generator->create_program();
        $program2 = $data_generator->create_program();

        /** @var shezar_program_generator $program_generator */
        $program_generator = $this->getDataGenerator()->get_plugin_generator('shezar_program');

        // We'll start with assigning the audience. This shouldn't change.
        $program_generator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);

        // Check that it's correct in the prog_assignment table.
        $audienceassignments = $DB->get_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_COHORT));
        $this->assertCount(1, $audienceassignments);
        /** @var array $pre_audienceassignments - this will hold assignment records with assignment ids as keys. */
        $pre_audienceassignments = array();
        foreach($audienceassignments as $assignment) {
            // The assignmenttype id should be one of the added audience ids.
            $this->assertContains($assignment->assignmenttypeid, array($audience1->id));
            // Now we can add it to the pre_ array.
            $pre_audienceassignments[$assignment->id] = $assignment;
        }

        // Now assign via manager hierarchy.
        $this->assign_to_program_assigntype_manager($program1->id, $users[1]->id);
        $this->assign_to_program_assigntype_manager($program1->id, $users[2]->id);
        $this->assign_to_program_assigntype_manager($program1->id, $users[3]->id);
        $this->assign_to_program_assigntype_manager($program2->id, $users[8]->id);


        // Assign individually.
        $program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $users[11]->id);
        $program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $users[2]->id);

        // Check that it's correct in the prog_assignment table.
        $individualassignments = $DB->get_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_INDIVIDUAL));
        $this->assertCount(2, $individualassignments);
        /** @var array $pre_individualassignments - this will hold assignment records with assignment ids as keys. */
        $pre_individualassignments = array();
        foreach($individualassignments as $assignment) {
            // The assignmenttype id should be one of the added individual ids.
            $this->assertContains($assignment->assignmenttypeid, array($users[11]->id, $users[2]->id));
            // Now we can add it to the pre_ array.
            $pre_individualassignments[$assignment->id] = $assignment;
        }

        // Assign more via manager hierarchy. We should end up with a table where the manager_hierarchy types
        // are scattered broken up by the individual types.
        $this->assign_to_program_assigntype_manager($program1->id, $users[12]->id);
        // Users[23] isn't a manager and shouldn't be in this table. But we still make sure erroneous data
        // such as this isn't breaking the upgrade.
        $this->assign_to_program_assigntype_manager($program1->id, $users[23]->id);
        // Let's give some manager hierarchies assignments to multiple programs.
        $this->assign_to_program_assigntype_manager($program2->id, $users[3]->id);
        $this->assign_to_program_assigntype_manager($program2->id, $users[12]->id);

        $managerassignmentids = array($users[1]->id, $users[2]->id, $users[3]->id, $users[8]->id, $users[12]->id, $users[23]->id);
        // We should see the managers userid in the prog_assignment table.
        $managerassignments = $DB->get_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_MANAGER));
        $this->assertCount(8, $managerassignments);
        /** @var array $pre_managerassignments - this will hold assignment records with assignment ids as keys. */
        $pre_managerassignments = array();
        foreach($managerassignments as $assignment) {
            // The assignmenttype id should be one of the added manager's user ids.
            $this->assertContains($assignment->assignmenttypeid, $managerassignmentids);
            // Now we can add it to the pre_ array.
            $pre_managerassignments[$assignment->id] = $assignment;
        }

        // Run the function.
        shezar_core_upgrade_multiple_jobs();

        // Test results.

        // Audience assignments should not have changed.
        $audienceassignments = $DB->get_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_COHORT));
        $this->assertCount(1, $audienceassignments);
        foreach($audienceassignments as $assignment) {
            $this->assertContains($assignment->assignmenttypeid, array($audience1->id));
            $this->assertEquals($pre_audienceassignments[$assignment->id]->assignmenttypeid, $assignment->assignmenttypeid);
        }

        // Individual assignments should not have changed.
        $individualassignments = $DB->get_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_INDIVIDUAL));
        $this->assertCount(2, $individualassignments);
        foreach($individualassignments as $assignment) {
            $this->assertContains($assignment->assignmenttypeid, array($users[11]->id, $users[2]->id));
            $this->assertEquals($pre_individualassignments[$assignment->id]->assignmenttypeid, $assignment->assignmenttypeid);
        }

        // Manager assignments should now be according to job assignment id not manager id.
        $manager_assignments = $DB->get_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_MANAGERJA));
        $this->assertCount(8, $manager_assignments);
        foreach($manager_assignments as $assignment) {
            $pre_assignment = $pre_managerassignments[$assignment->id];
            // This will get us the job assignment id of the user.
            $jobassignment = \shezar_job\job_assignment::get_first($pre_assignment->assignmenttypeid);
            // Check the job assignment id is what is now used instead of the user id.
            $this->assertEquals($jobassignment->id, $assignment->assignmenttypeid);
        }

        // There should be nothing left that uses the old ASSIGNTYPE_MANAGER constant.
        $manager_assignments = $DB->get_records('prog_assignment', array('assignmenttype' => ASSIGNTYPE_MANAGER));
        $this->assertCount(0, $manager_assignments);
    }

    /**
     * Test that shezar_hirarchy settings are moved to shezar_job during upgrade.
     */
    public function test_upgrade_hierarchy_settings() {
        $settingnames = array('allowsignupposition', 'allowsignuporganisation', 'allowsignupmanager');

        // Make sure the database looks like the old version and contains some identifiable values.
        foreach ($settingnames as $settingname) {
            set_config($settingname, 12345, 'shezar_hierarchy');
        }

        // Do the upgrade.
        shezar_core_upgrade_multiple_jobs();

        // Check the results are what we expect.
        foreach ($settingnames as $settingname) {
            $this->assertEquals(12345, get_config('shezar_job', $settingname));
            $this->assertFalse(get_config('shezar_hierarchy', $settingname));
        }
    }

    /**
     * Test that update_temporary_manager_task settings are moved from shezar_core to shezar_job.
     */
    public function test_upgrade_temp_manager_task() {
        global $DB;

        // Remove the new task if it already exists.
        $criteria = array(
            'component' => 'shezar_job',
            'classname' => '\shezar_job\task\update_temporary_managers_task'
        );
        $DB->delete_records('task_scheduled', $criteria);

        // Create the old record, and make it easily identifiable.
        $data = new stdClass();
        $data->component = 'shezar_core';
        $data->classname = '\shezar_core\task\update_temporary_managers_task';
        $data->lastruntime = 12345; // This is what we'll look for.
        $DB->insert_record('task_scheduled', $data);

        // Get all the data from the table before upgrade and make it look like what we expect afterwards.
        $expectedrecords = $DB->get_records('task_scheduled', array(), 'id');
        foreach ($expectedrecords as $record) {
            if ($record->component == 'shezar_core' && $record->classname == '\shezar_core\task\update_temporary_managers_task') {
                $record->component = 'shezar_job';
                $record->classname = '\shezar_job\task\update_temporary_managers_task';
                break;
            }
        }

        // Do the upgrade.
        shezar_core_upgrade_multiple_jobs();

        // Compare before and after.
        $resultrecords = $DB->get_records('task_scheduled', array(), 'id');
        $this->assertEquals($expectedrecords, $resultrecords);
    }
}