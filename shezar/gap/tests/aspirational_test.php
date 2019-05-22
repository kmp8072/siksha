<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlearning.com>
 * @package shezar_gap
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot . '/shezar/gap/db/upgradelib.php');
require_once($CFG->dirroot . '/shezar/gap/lib.php');

class shezar_gap_aspirational_test extends advanced_testcase {
    /**
     * Test that upgrade/installation goes without data loss
     */
    public function test_shezar_gap_install_aspirational_pos() {
        global $DB;
        $this->resetAfterTest();
        $dbman = $DB->get_manager();

        // Table is removed, so we need to mock it to test upgrade process.
        // Only fields related to positions are recreated.
        $posassignmenttable = new xmldb_table('pos_assignment');
        $posassignmenttable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $posassignmenttable->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $posassignmenttable->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $posassignmenttable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $posassignmenttable->add_field('positionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $posassignmenttable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $posassignmenttable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $posassignmenttable->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table session_info_field.
        $posassignmenttable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for session_info_field.
        if (!$dbman->table_exists($posassignmenttable)) {
            $dbman->create_table($posassignmenttable);
        }

        // In an upgrade the gap_aspirational table would not exist and must be added by the function being tested.
        $gapaspirationaltable = new xmldb_table('gap_aspirational');
        if (!$dbman->table_exists($gapaspirationaltable)) {
            $dbman->drop_table($gapaspirationaltable);
        }

        // Add some data to transfer.
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(array());

        $pos1 = $hierarchy_generator->create_pos(array('frameworkid' => $fw->id));
        $pos2 = $hierarchy_generator->create_pos(array('frameworkid' => $fw->id));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $DB->insert_record('pos_assignment', (object)array('id' => 10, 'fullname' => 'a1', 'type' => 1, 'userid' => $user1->id, 
            'positionid' => $pos1->id, 'timecreated' => '1468468590', 'timemodified' => '1468468595', 'usermodified' => 2));
        $DB->insert_record('pos_assignment', (object)array('id' => 20, 'fullname' => 'a2', 'type' => 3, 'userid' => $user2->id, 
            'positionid' => $pos2->id, 'timecreated' => '1468468591', 'timemodified' => '1468468596', 'usermodified' => $user1->id));
        $DB->insert_record('pos_assignment', (object)array('id' => 30, 'fullname' => 'a3', 'type' => 2, 'userid' => $user2->id,
            'positionid' => $pos2->id, 'timecreated' => '1468468592', 'timemodified' => '1468468597', 'usermodified' => $user2->id));
        $DB->insert_record('pos_assignment', (object)array('id' => 30, 'fullname' => 'a4', 'type' => 3, 'userid' => $user3->id,
            'positionid' => null, 'timecreated' => '1468468592', 'timemodified' => '1468468598', 'usermodified' => $user3->id));

        shezar_gap_install_aspirational_pos();

        $gapasps = $DB->get_records('gap_aspirational');
        $this->assertCount(1, $gapasps);
        $gapasp = current($gapasps);
        $this->assertEquals($user2->id, $gapasp->userid);
        $this->assertEquals($pos2->id, $gapasp->positionid);
        $this->assertEquals('1468468591', $gapasp->timecreated);
        $this->assertEquals('1468468596', $gapasp->timemodified);
        $this->assertEquals($user1->id, $gapasp->usermodified);

        $posassigns = $DB->get_records('pos_assignment');
        $this->assertCount(2, $posassigns);
        foreach($posassigns as $posassign) {
            if ($posassign->fullname == 'a1') {
                $this->assertEquals($user1->id, $posassign->userid);
                $this->assertEquals(1, $posassign->type);
                $this->assertEquals($pos1->id, $posassign->positionid);
                $this->assertEquals('1468468590', $posassign->timecreated);
                $this->assertEquals('1468468595', $posassign->timemodified);
                $this->assertEquals(2, $posassign->usermodified);
            } else {
                $this->assertEquals('a3', $posassign->fullname);
                $this->assertEquals($user2->id, $posassign->userid);
                $this->assertEquals(2, $posassign->type);
                $this->assertEquals($pos2->id, $posassign->positionid);
                $this->assertEquals('1468468592', $posassign->timecreated);
                $this->assertEquals('1468468597', $posassign->timemodified);
                $this->assertEquals($user2->id, $posassign->usermodified);
            }
        }
    }

    /**
     * Test that permissions checked correctly
     */
    public function test_shezar_gap_can_edit_aspirational_position() {
        global $DB;
        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $this->setAdminUser();
        $this->assertTrue(shezar_gap_can_edit_aspirational_position($user1->id));

        $this->setUser($user1);
        $this->assertFalse(shezar_gap_can_edit_aspirational_position($user2->id));
        // Need capability to change own aspirational position.
        $this->assertFalse(shezar_gap_can_edit_aspirational_position($user1->id));

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        assign_capability('shezar/gap:assignaspirationalposition', CAP_ALLOW, $managerrole->id, context_user::instance($user1->id));
        assign_capability('shezar/gap:assignaspirationalposition', CAP_ALLOW, $teacherrole->id, context_system::instance());
        assign_capability('shezar/gap:assignselfaspirationalposition', CAP_ALLOW, $studentrole->id, context_system::instance());

        role_assign($studentrole->id, $user1->id, context_system::instance());
        role_assign($studentrole->id, $user2->id, context_system::instance());
        role_assign($managerrole->id, $manager->id, context_user::instance($user1->id));
        role_assign($managerrole->id, $teacher->id, context_system::instance());

        $this->setUser($user1);
        $this->assertTrue(shezar_gap_can_edit_aspirational_position($user1->id));
        $this->setUser($user2);
        $this->assertFalse(shezar_gap_can_edit_aspirational_position($user1->id));
        $this->setUser($teacher);
        $this->assertTrue(shezar_gap_can_edit_aspirational_position($user1->id));
        $this->assertTrue(shezar_gap_can_edit_aspirational_position($user2->id));
        $this->setUser($manager);
        $this->assertTrue(shezar_gap_can_edit_aspirational_position($user1->id));
        $this->assertFalse(shezar_gap_can_edit_aspirational_position($user2->id));
    }

    /**
     * Test aspirational position details fetch
     */
    public function test_shezar_gap_get_aspirational_position() {
        global $DB;
        $this->resetAfterTest();
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(array());

        $pos1 = $hierarchy_generator->create_pos(array('frameworkid' => $fw->id, 'fullname' => 'fw1p1'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $DB->insert_record('gap_aspirational', (object)array('userid' => $user1->id,
            'positionid' => $pos1->id, 'timecreated' => '1468468590', 'timemodified' => '1468468595', 'usermodified' => $user2->id));

        $gapasp = shezar_gap_get_aspirational_position($user1->id);
        $this->assertEquals($user1->id, $gapasp->userid);
        $this->assertEquals('fw1p1', $gapasp->fullname);
        $this->assertEquals($pos1->id, $gapasp->positionid);
        $this->assertEquals('1468468590', $gapasp->timecreated);
        $this->assertEquals('1468468595', $gapasp->timemodified);
        $this->assertEquals($user2->id, $gapasp->usermodified);

        $nopos = shezar_gap_get_aspirational_position($user2->id);
        $this->assertFalse($nopos);
    }

    /**
     * Test aspirational position assignment in profile
     */
    public function test_shezar_gap_assign_aspirational_position() {
        $this->resetAfterTest();
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('shezar_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(array());

        $pos1 = $hierarchy_generator->create_pos(array('frameworkid' => $fw->id, 'fullname' => 'fw1p1'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Assign.
        $time = time();
        $this->setUser($user2);
        shezar_gap_assign_aspirational_position($user1->id, $pos1->id);

        $gapasp = shezar_gap_get_aspirational_position($user1->id);
        $this->assertEquals($user1->id, $gapasp->userid);
        $this->assertEquals('fw1p1', $gapasp->fullname);
        $this->assertEquals($pos1->id, $gapasp->positionid);
        $this->assertLessThan(5, $gapasp->timecreated - $time);
        $this->assertLessThan(5, $gapasp->timemodified - $time);
        $this->assertEquals($user2->id, $gapasp->usermodified);

        // Unassign.
        shezar_gap_assign_aspirational_position($user1->id, 0);
        $this->assertFalse(shezar_gap_get_aspirational_position($user1->id));

        // Wrong user.
        try {
           shezar_gap_assign_aspirational_position(0, $pos1->id);
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
    }
}