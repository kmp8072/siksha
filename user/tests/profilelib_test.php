<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for user/profile/lib.php.
 *
 * @package core_user
 * @copyright 2014 The Open University
 * @licensehttp://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for user/profile/lib.php.
 *
 * @package core_user
 * @copyright 2014 The Open University
 * @licensehttp://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_user_profilelib_testcase extends advanced_testcase {
    /**
     * Tests profile_get_custom_fields function and checks it is consistent
     * with profile_user_record.
     */
    public function test_get_custom_fields() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        // Add a custom field of textarea type.
        $id1 = $DB->insert_record('user_info_field', array(
                'shortname' => 'frogdesc', 'name' => 'Description of frog', 'categoryid' => 1,
                'datatype' => 'textarea'));

        // Check the field is returned.
        $result = profile_get_custom_fields();
        $this->assertArrayHasKey($id1, $result);
        $this->assertEquals('frogdesc', $result[$id1]->shortname);

        // Textarea types are not included in user data though, so if we
        // use the 'only in user data' parameter, there is still nothing.
        $this->assertArrayNotHasKey($id1, profile_get_custom_fields(true));

        // Check that profile_user_record returns same (no) fields.
        $this->assertObjectNotHasAttribute('frogdesc', profile_user_record($user->id));

        // Check that profile_user_record returns all the fields when requested.
        $this->assertObjectHasAttribute('frogdesc', profile_user_record($user->id, false));

        // Add another custom field, this time of normal text type.
        $id2 = $DB->insert_record('user_info_field', array(
                'shortname' => 'frogname', 'name' => 'Name of frog', 'categoryid' => 1,
                'datatype' => 'text'));

        // Check both are returned using normal option.
        $result = profile_get_custom_fields();
        $this->assertArrayHasKey($id2, $result);
        $this->assertEquals('frogname', $result[$id2]->shortname);

        // And check that only the one is returned the other way.
        $this->assertArrayHasKey($id2, profile_get_custom_fields(true));

        // Check profile_user_record returns same field.
        $this->assertObjectHasAttribute('frogname', profile_user_record($user->id));

        // Check that profile_user_record returns all the fields when requested.
        $this->assertObjectHasAttribute('frogname', profile_user_record($user->id, false));
    }

    /**
     * Make sure that all profile fields can be initialised without arguments.
     */
    public function test_default_constructor() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/definelib.php');
        $datatypes = profile_list_datatypes();
        foreach ($datatypes as $datatype => $datatypename) {
            require_once($CFG->dirroot . '/user/profile/field/' .
                $datatype . '/field.class.php');
            $newfield = 'profile_field_' . $datatype;
            $formfield = new $newfield();
            $this->assertNotNull($formfield);
        }
    }

    /**
     * Test profile_view function
     */
    public function test_profile_view() {
        global $USER;

        $this->resetAfterTest();

        // Course without sections.
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);

        $this->setUser($user);

        // Redirect events to the sink, so we can recover them later.
        $sink = $this->redirectEvents();

        profile_view($user, $context, $course);
        $events = $sink->get_events();
        $event = reset($events);

        // Check the event details are correct.
        $this->assertInstanceOf('\core\event\user_profile_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals($user->id, $event->relateduserid);
        $this->assertEquals($course->id, $event->other['courseid']);
        $this->assertEquals($course->shortname, $event->other['courseshortname']);
        $this->assertEquals($course->fullname, $event->other['coursefullname']);

        profile_view($user, $usercontext);
        $events = $sink->get_events();
        $event = array_pop($events);
        $sink->close();

        $this->assertInstanceOf('\core\event\user_profile_viewed', $event);
        $this->assertEquals($usercontext, $event->get_context());
        $this->assertEquals($user->id, $event->relateduserid);

    }

    /**
     * shezar : tests position_save_data in user/profile/lib.php.
     *
     * Sets something for multiple job assignment fields.
     */
    public function test_position_save_data_all() {
        $this->resetAfterTest();

        set_config('allowsignupposition', 1, 'shezar_job');
        set_config('allowsignuporganisation', 1, 'shezar_job');
        set_config('allowsignupmanager', 1, 'shezar_job');

        /** @var testing_data_generator $data_generator */
        $data_generator = $this->getDataGenerator();
        /** @var shezar_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $data_generator->get_plugin_generator('shezar_hierarchy');
        $posframe = $hierarchy_generator->create_pos_frame([]);
        $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $posframe->id]);

        $orgframe = $hierarchy_generator->create_org_frame([]);
        $org1 = $hierarchy_generator->create_org(['frameworkid' => $orgframe->id]);

        $manager = $data_generator->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager->id);

        $existinguser = $data_generator->create_user();
        $existinguserja = \shezar_job\job_assignment::create_default($existinguser->id, ['managerjaid' => $managerja->id]);

        $newuser = $data_generator->create_user();
        $newuser->positionid = $pos1->id;
        $newuser->organisationid = $org1->id;
        $newuser->managerjaid = $managerja->id;

        // Check the data isn't there before the function is run.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        position_save_data($newuser);

        $newuserja = \shezar_job\job_assignment::get_first($newuser->id);
        $this->assertEquals($pos1->id, $newuserja->positionid);
        $this->assertEquals($org1->id, $newuserja->organisationid);
        $this->assertEquals($managerja->id, $newuserja->managerjaid);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertContains($manager->id, $managerids);
        $this->assertCount(1, $managerids);
    }

    /**
     * shezar : tests position_save_data in user/profile/lib.php.
     *
     * Does not set any job assignment fields.
     */
    public function test_position_save_data_none() {
        $this->resetAfterTest();

        set_config('allowsignupposition', 1, 'shezar_job');
        set_config('allowsignuporganisation', 1, 'shezar_job');
        set_config('allowsignupmanager', 1, 'shezar_job');

        /** @var testing_data_generator $data_generator */
        $data_generator = $this->getDataGenerator();
        /** @var shezar_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $data_generator->get_plugin_generator('shezar_hierarchy');
        $posframe = $hierarchy_generator->create_pos_frame([]);
        $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $posframe->id]);

        $orgframe = $hierarchy_generator->create_org_frame([]);
        $org1 = $hierarchy_generator->create_org(['frameworkid' => $orgframe->id]);

        $manager = $data_generator->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager->id);

        $existinguser = $data_generator->create_user();
        $existinguserja = \shezar_job\job_assignment::create_default($existinguser->id, ['managerjaid' => $managerja->id]);

        $newuser = $data_generator->create_user();
        $newuser->positionid = null;
        $newuser->organisationid = null;
        $newuser->managerjaid = null;

        // Check the data isn't there before the function is run.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        position_save_data($newuser);

        // There still shouldn't be any job assignment or manager ids.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);
    }

    /**
     * shezar : tests position_save_data in user/profile/lib.php.
     *
     * Sets the postion field only.
     */
    public function test_position_save_data_pos_only() {
        $this->resetAfterTest();

        // Start with just position disabled.
        set_config('allowsignupposition', 0, 'shezar_job');
        set_config('allowsignuporganisation', 1, 'shezar_job');
        set_config('allowsignupmanager', 1, 'shezar_job');

        /** @var testing_data_generator $data_generator */
        $data_generator = $this->getDataGenerator();
        /** @var shezar_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $data_generator->get_plugin_generator('shezar_hierarchy');
        $posframe = $hierarchy_generator->create_pos_frame([]);
        $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $posframe->id]);

        $orgframe = $hierarchy_generator->create_org_frame([]);
        $org1 = $hierarchy_generator->create_org(['frameworkid' => $orgframe->id]);

        $manager = $data_generator->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager->id);

        $existinguser = $data_generator->create_user();
        $existinguserja = \shezar_job\job_assignment::create_default($existinguser->id, ['managerjaid' => $managerja->id]);

        $newuser = $data_generator->create_user();
        $newuser->positionid = $pos1->id;
        $newuser->organisationid = null;
        $newuser->managerjaid = null;

        // Check the data isn't there before the function is run.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        position_save_data($newuser);

        // There still shouldn't be any job assignment or manager ids.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        // Now update the config setting and try again.
        set_config('allowsignupposition', 1, 'shezar_job');
        position_save_data($newuser);

        $newuserja = \shezar_job\job_assignment::get_first($newuser->id);
        $this->assertEquals($pos1->id, $newuserja->positionid);
        $this->assertEmpty($newuserja->organisationid);
        $this->assertEmpty($newuserja->managerjaid);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);
    }

    /**
     * shezar : tests position_save_data in user/profile/lib.php.
     *
     * Sets the organisation field only.
     */
    public function test_position_save_data_org_only() {
        $this->resetAfterTest();

        set_config('allowsignupposition', 1, 'shezar_job');
        // Start with just organisation disabled.
        set_config('allowsignuporganisation', 0, 'shezar_job');
        set_config('allowsignupmanager', 1, 'shezar_job');

        /** @var testing_data_generator $data_generator */
        $data_generator = $this->getDataGenerator();
        /** @var shezar_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $data_generator->get_plugin_generator('shezar_hierarchy');
        $posframe = $hierarchy_generator->create_pos_frame([]);
        $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $posframe->id]);

        $orgframe = $hierarchy_generator->create_org_frame([]);
        $org1 = $hierarchy_generator->create_org(['frameworkid' => $orgframe->id]);

        $manager = $data_generator->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager->id);

        $existinguser = $data_generator->create_user();
        $existinguserja = \shezar_job\job_assignment::create_default($existinguser->id, ['managerjaid' => $managerja->id]);

        $newuser = $data_generator->create_user();
        $newuser->positionid = null;
        $newuser->organisationid = $org1->id;
        $newuser->managerjaid = null;

        // Check the data isn't there before the function is run.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        position_save_data($newuser);

        // There still shouldn't be any job assignment or manager ids.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        // Now update the config setting and try again.
        set_config('allowsignuporganisation', 1, 'shezar_job');
        position_save_data($newuser);

        $newuserja = \shezar_job\job_assignment::get_first($newuser->id);
        $this->assertEmpty($newuserja->positionid);
        $this->assertEquals($org1->id, $newuserja->organisationid);
        $this->assertEmpty($newuserja->managerjaid);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);
    }

    /**
     * shezar : tests position_save_data in user/profile/lib.php.
     *
     * Sets the manager field only.
     */
    public function test_position_save_data_manager_only() {
        $this->resetAfterTest();

        set_config('allowsignupposition', 1, 'shezar_job');
        set_config('allowsignuporganisation', 1, 'shezar_job');
        // Start with just manager disabled.
        set_config('allowsignupmanager', 0, 'shezar_job');

        /** @var testing_data_generator $data_generator */
        $data_generator = $this->getDataGenerator();
        /** @var shezar_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $data_generator->get_plugin_generator('shezar_hierarchy');
        $posframe = $hierarchy_generator->create_pos_frame([]);
        $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $posframe->id]);

        $orgframe = $hierarchy_generator->create_org_frame([]);
        $org1 = $hierarchy_generator->create_org(['frameworkid' => $orgframe->id]);

        $manager = $data_generator->create_user();
        $managerja = \shezar_job\job_assignment::create_default($manager->id);

        $existinguser = $data_generator->create_user();
        $existinguserja = \shezar_job\job_assignment::create_default($existinguser->id, ['managerjaid' => $managerja->id]);

        $newuser = $data_generator->create_user();
        $newuser->positionid = null;
        $newuser->organisationid = null;
        $newuser->managerjaid = $managerja->id;

        // Check the data isn't there before the function is run.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        position_save_data($newuser);

        // There still shouldn't be any job assignment or manager ids.
        $newuserja = \shezar_job\job_assignment::get_first($newuser->id, false);
        $this->assertEmpty($newuserja);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertEmpty($managerids);

        // Now update the config setting and try again.
        set_config('allowsignupmanager', 1, 'shezar_job');
        position_save_data($newuser);

        $newuserja = \shezar_job\job_assignment::get_first($newuser->id);
        $this->assertEmpty($newuserja->positionid);
        $this->assertEmpty($newuserja->organisationid);
        $this->assertEquals($managerja->id, $newuserja->managerjaid);
        $managerids = \shezar_job\job_assignment::get_all_manager_userids($newuser->id);
        $this->assertContains($manager->id, $managerids);
        $this->assertCount(1, $managerids);
    }
}
