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
 * @author Brendan Cox <brendan.cox@shezarlearning.com>
 * @package shezar_job
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/shezar/job/dialog/assign_manager.php');

class shezar_job_dialog_assign_manager_testcase extends advanced_testcase {

    /** @var  testing_data_generator */
    private $data_generator;

    private $max_users = 10;
    private $users = array();
    private $userids = array();
    private $userfullnames = array();

    public function setUp() {
        parent::setup();
        $this->resetAfterTest();

        $this->data_generator = $this->getDataGenerator();
        for($i = 0; $i < $this->max_users; $i++) {
            $user = $this->data_generator->create_user();
            $this->users[$i] = $user;
            $this->userids[$i] = $user->id;
            $this->userfullnames[$i] = fullname($user);
        }
    }

    private function execute_restricted_method($object, $methodname, $arguments = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $arguments);
    }

    private function get_restricted_property($object, $propertyname) {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyname);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public function test_load_managers() {
        $currentuser = $this->users[0];

        $dialog = new shezar_job_dialog_assign_manager($currentuser->id);
        $this->execute_restricted_method($dialog, 'load_managers');
        $dialogmanagers = $this->get_restricted_property($dialog, 'managers');

        // The count should equal max users, because although the current user won't be in the list,
        // the admin user should have been added.
        $this->assertEquals($this->max_users, count($dialogmanagers));

        $admin = get_admin();
        $guest = guest_user();

        $expecteduserids = $this->userids;
        $expecteduserids[] = $admin->id;
        foreach($dialogmanagers as $dialogmanager) {
            // Remove the 'mgr' prefix from the id.
            $dialogmanagerid = (int)substr($dialogmanager->id, 3);
            $this->assertNotEquals($guest->id, $dialogmanagerid);
            $this->assertNotEquals($currentuser->id, $dialogmanagerid);
            $this->assertContains($dialogmanagerid, $expecteduserids);
        }
    }

    public function test_load_job_assignments() {
        // $manager is who we'll test with for returning the correct data.
        $currentuser = $this->users[0];
        $manager = $this->users[1];
        $notmanager = $this->users[2];

        // Set admin user to pass permission checks for being allowed to create a user.
        $this->setAdminUser();

        $jobdata1 = array(
            'userid' => $manager->id,
            'idnumber' => 1
        );
        $newjobassignment1 = \shezar_job\job_assignment::create($jobdata1);
        $jobdata2 = array(
            'userid' => $manager->id,
            'idnumber' => 2,
            'fullname' => 'Job2 Fullname'
        );
        $newjobassignment2 = \shezar_job\job_assignment::create($jobdata2);
        $jobdata3 = array(
            'userid' => $notmanager->id,
            'idnumber' => 3,
            'fullname' => 'Not managers job'
        );
        $newjobassignment3 = \shezar_job\job_assignment::create($jobdata3);
        $jobdata4 = array(
            'userid' => $currentuser->id,
            'idnumber' => 4,
            'fullname' => 'Current users job'
        );
        $newjobassignment4 = \shezar_job\job_assignment::create($jobdata4);

        $prefixedmgrid = 'mgr' . $manager->id;
        $dialog = new shezar_job_dialog_assign_manager($currentuser->id, $prefixedmgrid);
        $this->execute_restricted_method($dialog, 'load_job_assignments');
        $jobassignments = $this->get_restricted_property($dialog, 'jobassignments');

        $expectednames = array(
            'Unnamed job assignment (ID: 1)',
            'Job2 Fullname',
            'Create empty job assignment'
        );
        // Should be 3 as there are 2 job assignments for $manager + the option to create a new one.
        $this->assertEquals(3, count($jobassignments));
        foreach($jobassignments as $jobassignment) {
            $this->assertContains($jobassignment->name, $expectednames);
            $this->assertNotEquals('Other users job', $jobassignment->name);
        }
    }

    public function test_get_managers_from_db() {
        $currentuser = $this->users[0];
        $manager = $this->users[1];

        $dialog = new shezar_job_dialog_assign_manager($currentuser->id);

        // Test without specifying a manager.
        $allmanagers = $this->execute_restricted_method($dialog, 'get_managers_from_db');
        $this->assertEquals($this->max_users, count($allmanagers));
        $admin = get_admin();
        $guest = guest_user();

        $expecteduserids = $this->userids;
        $expecteduserids[] = $admin->id;
        foreach($allmanagers as $manager) {
            $this->assertNotEquals($guest->id, $manager->id);
            $this->assertNotEquals($currentuser->id, $manager->id);
            $this->assertContains($manager->id, $expecteduserids);
            // Check sensitive fields are not being returned.
            $this->assertFalse(isset($manager->password));
        }

        // Now execute with a manager id.
        $returnedmanager = $this->execute_restricted_method($dialog, 'get_managers_from_db', array($manager->id));
        $this->assertEquals($manager->id, $returnedmanager->id);
        $this->assertEquals($manager->firstname, $returnedmanager->firstname);
        $this->assertEquals($manager->lastname, $returnedmanager->lastname);
        $this->assertFalse(isset($manager->password));
    }
}