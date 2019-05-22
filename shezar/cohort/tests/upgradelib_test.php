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
 * @author David Curry <david.curry@shezarlearning.com>
 * @package shezar_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/shezar/cohort/lib.php');
require_once($CFG->dirroot . '/shezar/cohort/db/upgradelib.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

/**
 * Test position rules.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit shezar_cohort_position_rules_testcase
 *
 */
class shezar_cohort_upgradelib_testcase extends advanced_testcase {

    private $man1 = null;
    private $man2 = null;
    private $pos1 = null;
    private $pos2 = null;
    private $pos3 = null;
    private $org1 = null;
    private $org2 = null;
    private $org3 = null;
    private $posfw = null;
    private $ptype1 = null;
    private $ptype2 = null;
    private $pcust1 = null;
    private $orgfw = null;
    private $otype1 = null;
    private $otype2 = null;
    private $ocust1 = null;
    private $cohort = null;
    private $ruleset = 0;
    private $userspos1 = array();
    private $userspos2 = array();
    private $userspos3 = array();
    /** @var shezar_cohort_generator $cohort_generator */
    private $cohort_generator = null;
    /** @var shezar_hierarchy_generator $hierarchy_generator */
    private $hierarchy_generator = null;
    private $dateformat = '';
    const TEST_JOB_COUNT_MEMBERS = 23;

    /**
     * Users per position/organisation:
     *
     * pos/org 1 ----> user3, user6, user9, user12, user15, user18, user21.
     *
     * pos/org 2 ----> user2, user4, user8, user10, user14, user16, user20, user22.
     *
     * pos/org 3 ----> user1, user5, user7, user11, user13, user17, user19, user23.
     */
    public function setUp() {
        global $DB;

        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->users = array();
        $this->dateformat = get_string('datepickerlongyearparseformat', 'shezar_core');

        // Set shezar_cohort generator.
        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('shezar_cohort');

        // Set shezar_hierarchy generator.
        $this->hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');

        // Create position framework.
        $name = shezar_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_POSITION;
        $name .= ' ' . shezar_generator_util::get_next_record_number('pos_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        $this->posfw = $this->hierarchy_generator->create_framework('position', $data);

        // Create position types.
        $this->ptype1 = $this->hierarchy_generator->create_pos_type(array('idnumber' => 'ptype1', 'fullname' => 'ptype1'));
        $this->ptype2 = $this->hierarchy_generator->create_pos_type(array('idnumber' => 'ptype2', 'fullname' => 'ptype2'));

        // Create checkbox customfield for position type.
        $defaultdata = 0; // Unchecked.
        $shortname   = 'checkbox' . $this->ptype1;
        $data = array('hierarchy' => 'position', 'typeidnumber' => 'ptype1', 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->pcust1 = $DB->get_record('pos_type_info_field', array('shortname' => $shortname));

        // Create organisation framework.
        $name = shezar_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_ORGANISATION;
        $name .= ' ' . shezar_generator_util::get_next_record_number('org_framework', 'fullname', $name);
        $data = array('fullname' => $name);
        $this->orgfw = $this->hierarchy_generator->create_framework('organisation', $data);

        // Create organisation types.
        $this->otype1 = $this->hierarchy_generator->create_org_type(array('idnumber' => 'otype1', 'fullname' => 'otype1'));
        $this->otype2 = $this->hierarchy_generator->create_org_type(array('idnumber' => 'otype2', 'fullname' => 'otype2'));

        // Create checkbox customfield for organisation type.
        $defaultdata = 0; // Unchecked.
        $shortname   = 'checkbox' . $this->otype1;
        $data = array('hierarchy' => 'organisation', 'typeidnumber' => 'otype1', 'value' => $defaultdata);
        $this->hierarchy_generator->create_hierarchy_type_checkbox($data);
        $this->ocust1 = $DB->get_record('org_type_info_field', array('shortname' => $shortname));

        // Create positions and organisation hierarchies.
        $this->assertEquals(0, $DB->count_records('pos'));
        $this->pos1 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position',
            array('idnumber' => 'pos1', 'fullname' => 'posname1', 'typeid' => 0));
        $this->pos2 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position',
            array('idnumber' => 'pos2', 'fullname' => 'posname2', 'typeid' => $this->ptype1));
        $this->pos3 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position',
            array('idnumber' => 'pos3', 'fullname' => 'posname3', 'typeid' => $this->ptype2));
        $this->assertEquals(3, $DB->count_records('pos'));

        // Create organisations and organisation hierarchies.
        $this->assertEquals(0, $DB->count_records('org'));
        $this->org1 = $this->hierarchy_generator->create_hierarchy($this->orgfw->id, 'organisation',
            array('idnumber' => 'org1', 'fullname' => 'orgname1', 'typeid' => 0));
        $this->org2 = $this->hierarchy_generator->create_hierarchy($this->orgfw->id, 'organisation',
            array('idnumber' => 'org2', 'fullname' => 'orgname2', 'typeid' => $this->otype1));
        $this->org3 = $this->hierarchy_generator->create_hierarchy($this->orgfw->id, 'organisation',
            array('idnumber' => 'org3', 'fullname' => 'orgname3', 'typeid' => $this->otype2));
        $this->assertEquals(3, $DB->count_records('org'));

        // Set some custom field data.
        $cfname = 'customfield_' . $this->ocust1->shortname;
        $item = new \stdClass();
        $item->id = $this->org2->id;
        $item->typeid = $this->otype1;
        $item->{$cfname} = 1; // Checked for org2.
        customfield_save_data($item, 'organisation', 'org_type');

        // Set the custom field data.
        $cfname = 'customfield_' . $this->pcust1->shortname;
        $item = new \stdClass();
        $item->id = $this->pos2->id;
        $item->typeid = $this->ptype1;
        $item->{$cfname} = 1; // Checked for pos2.
        customfield_save_data($item, 'position', 'pos_type');


        // Create some managers.
        $this->man1 = $this->getDataGenerator()->create_user();
        $man1ja = \shezar_job\job_assignment::create_default($this->man1->id);

        $this->man2 = $this->getDataGenerator()->create_user();
        $man2ja = \shezar_job\job_assignment::create_default($this->man2->id);

        // Create some test users and assign them to a position.
        $this->assertEquals(4, $DB->count_records('user'));
        $now = time();
        for ($i = 1; $i <= self::TEST_JOB_COUNT_MEMBERS; $i++) {
            $this->{'user'.$i} = $this->getDataGenerator()->create_user();
            if ($i%3 === 0) {
                $man = $man1ja->id;
                $orgid = $this->org1->id; // 7 users.
                $org = 'org1';
                $posid = $this->pos1->id; // 7 users.
                $pos = 'pos1';
                $jobassignstartdate = date($this->dateformat, $now);
                $jobassignenddate = date($this->dateformat, $now + (20 * DAYSECS));
            } else if ($i%2 === 0){
                $man = $man2ja->id;
                $orgid = $this->org2->id; // 8 users.
                $org = 'org2';
                $posid = $this->pos2->id; // 8 users.
                $pos = 'pos2';
                $jobassignstartdate = date($this->dateformat, $now - DAYSECS);
                $jobassignenddate = date($this->dateformat, $now + (50 * DAYSECS));
            } else {
                $man = null;
                $orgid = $this->org3->id; // 8 users.
                $org = 'org3';
                $posid = $this->pos3->id; // 8 users.
                $pos = 'pos3';
                $jobassignstartdate = date($this->dateformat, $now + (2 * DAYSECS));
                $jobassignenddate = date($this->dateformat, $now + (70 * DAYSECS));
            }
            $jobassignstartdate = shezar_date_parse_from_format($this->dateformat, $jobassignstartdate);
            $jobassignenddate = shezar_date_parse_from_format($this->dateformat, $jobassignenddate);
            $data = array('managerjaid' => $man, 'positionid' => $posid, 'organisationid' => $orgid, 'startdate' => $jobassignstartdate, 'enddate' => $jobassignenddate);
            \shezar_job\job_assignment::create_default($this->{'user'.$i}->id, $data);
            array_push($this->{'users'.$pos}, $this->{'user'.$i}->id);
        }

        $this->userspos1 = array_flip($this->userspos1);
        $this->userspos2 = array_flip($this->userspos2);
        $this->userspos3 = array_flip($this->userspos3);

        // Check the users were created. It should match $this->countmembers + 2 users(admin + guest) + 2 Managers.
        $this->assertEquals(self::TEST_JOB_COUNT_MEMBERS + 4, $DB->count_records('user'));

        // Check positions were assigned correctly.
        $this->assertEquals(7, $DB->count_records('job_assignment', array('positionid' => $this->pos1->id)));
        $this->assertEquals(8, $DB->count_records('job_assignment', array('positionid' => $this->pos2->id)));
        $this->assertEquals(8, $DB->count_records('job_assignment', array('positionid' => $this->pos3->id)));

        // Creating dynamic cohort.
        $cohortdata = array('name' => 'test cohort', 'cohorttype' => cohort::TYPE_DYNAMIC);
        $this->cohort = $this->cohort_generator->create_cohort($cohortdata);
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->cohort->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Create ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);

        $DB->execute('UPDATE {job_assignment} SET positionassignmentdate = startdate WHERE positionid > 0');
    }

    /**
     * Test the migration of a position rule.
     */
    public function test_position_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'id',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO, 'includechildren' => 0), array($this->pos1->id, false));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(7, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(7, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos1));
    }

    /**
     * Test the migration of a position name rule.
     */
    public function test_position_name_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'name',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('posname1'));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(7, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(7, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos1));
    }

    /**
     * Test the migration of a position idnumber rule.
     */
    public function test_position_idnumber_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'idnumber',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('pos1'));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(7, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(7, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos1));
    }

    /**
     * Test the migration of a position assignment date rule.
     */
    public function test_position_assignment_date_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $time = time() + DAYSECS;

        $params = array();
        $params['operator'] = COHORT_RULE_DATE_OP_AFTER_FIXED_DATE;
        $params['date'] = shezar_date_parse_from_format($this->dateformat, date($this->dateformat, $time));

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'startdate', $params, array());
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos3));
    }

    /**
     * Test the migration of a position assignment date rule.
     */
    public function test_position_start_date_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $time = time() + DAYSECS;

        $params = array();
        $params['operator'] = COHORT_RULE_DATE_OP_AFTER_FIXED_DATE;
        $params['date'] = shezar_date_parse_from_format($this->dateformat, date($this->dateformat, $time));

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'timevalidfrom', $params, array());
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos3));
    }

    /**
     * Test the migration of a position assignment date rule.
     */
    public function test_position_end_date_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $time = time() + DAYSECS;

        $params = array();
        $params['operator'] = COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION;
        $params['date'] = 60;

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'timevalidto', $params, array());
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos3));
    }

    /**
     * Test the migration of an organisation rule.
     */
    public function test_organisation_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'org', 'id',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO, 'includechildren' => 0), array($this->org1->id, false));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(7, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(7, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos1));
    }

    /**
     * Test the migration of an organisation idnumber rule.
     */
    public function test_organisation_idnumber_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'org', 'idnumber',
            array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('org1'));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(7, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(7, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos1));
    }

    /**
     * Test the migration of an position type rule.
     */
    public function test_position_type_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'postype',
            array('equal' => COHORT_RULES_OP_IN_EQUAL), array($this->ptype1));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos2));
    }

    /**
     * Test the migration of a position type custom field rule.
     */
    public function test_position_type_customfield_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Make an extra position in the same type to make sure they aren't included.
        $pos4 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position',
            array('idnumber' => 'pos4', 'fullname' => 'posname4', 'typeid' => $this->ptype1));

        // Create some test users and assign them to a position.
        $now = time();
        $expected = array();
        for ($i = 1; $i <= 5; $i++) {
            $custuser = $this->getDataGenerator()->create_user();
            $data = array('positionid' => $pos4->id);
            \shezar_job\job_assignment::create_default($custuser->id, $data);
        }

        // Create an old style rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'customfield' . $this->pcust1->id,
            array('equal' => COHORT_RULES_OP_IN_EQUAL), array(0)); // 0 => Checked for some reason.
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function.
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));

        foreach (array_keys($this->userspos2) as $userid) {
            $this->assertTrue(in_array($userid, $members));
        }
    }

    /**
     * Test the migration of an organisation type rule.
     */
    public function test_organisation_type_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'org', 'orgtype',
            array('equal' => COHORT_RULES_OP_IN_EQUAL), array($this->otype1));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos2));
    }

    /**
     * Test the migration of an organisation type custom field rule.
     */
    public function test_organisation_type_customfield_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Todo: find a better way to deal with static variable in cohort_rules_list.
        // The static $rules variable in cohort_rules_list may still be set from earlier tests.
        // This function is used within functions that are run by this test and random failures can result.
        // For now passing in true as an argument will reset it.
        cohort_rules_list(true);

        // Make an extra position in the same type to make sure they aren't included.
        $org4 = $this->hierarchy_generator->create_hierarchy($this->orgfw->id, 'organisation',
            array('idnumber' => 'org4', 'fullname' => 'orgname4', 'typeid' => $this->otype1));

        // Create some test users and assign them to a position.
        $now = time();
        $expected = array();
        for ($i = 1; $i <= 5; $i++) {
            $custuser = $this->getDataGenerator()->create_user();
            $data = array('organisationid' => $org4->id);
            \shezar_job\job_assignment::create_default($custuser->id, $data);
        }

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'org', 'customfield' . $this->ocust1->id,
            array('equal' => COHORT_RULES_OP_IN_EQUAL), array(0)); // 0 => Checked for some reason.
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));

        foreach (array_keys($this->userspos2) as $userid) {
            $this->assertTrue(in_array($userid, $members));
        }
    }

    /**
     * Test the migration of a has direct staff rule.
     */
    public function test_has_direct_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'hasdirectreports',
            array('equal' => COHORT_RULES_OP_IN_EQUAL), array(1));
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(2, count($members));
        $this->assertTrue($DB->record_exists('cohort_members', array('cohortid' => $this->cohort->id, 'userid' => $this->man1->id)));
        $this->assertTrue($DB->record_exists('cohort_members', array('cohortid' => $this->cohort->id, 'userid' => $this->man2->id)));
    }

    /**
     * Test the migration of a manager rule.
     */
    public function test_manager_rule_migration() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an old style rule
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'pos', 'reportsto',
            array('isdirectreport' => 1, 'equal' => COHORT_RULES_OP_IN_EQUAL), array($this->man2->id), 'managerid');
        cohort_rules_approve_changes($this->cohort);

        // Check the $members dont meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertNotEquals(8, count($members));

        // Run the upgradelib function
        shezar_cohort_migrate_position_rules();
        shezar_cohort_update_dynamic_cohort_members($this->cohort->id, 0, true);

        // Check the $members now meet the expected amount.
        $members = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', array($this->cohort->id));
        $this->assertEquals(8, count($members));
        $this->assertEmpty(array_diff_key(array_flip($members), $this->userspos2));
    }
}
