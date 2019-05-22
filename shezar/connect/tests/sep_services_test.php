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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_connect
 */

use \shezar_connect\sep_services;
use \shezar_connect\util;
use \shezar_core\jsend;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests sep services class.
 */
class shezar_connect_sep_services_testcase extends advanced_testcase {
    public function test_get_api_version() {
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        $result = sep_services::get_api_version($client, array('clienttype' => 'shezarlms'));
        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['data']['minapiversion']);
        $this->assertSame(1, $result['data']['maxapiversion']);

        $result = sep_services::get_api_version($client, array('clienttype' => 'shezarsocial'));
        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['data']['minapiversion']);
        $this->assertSame(1, $result['data']['maxapiversion']);

        $result = sep_services::get_api_version($client, array('clienttype' => 'abc'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);

        $result = sep_services::get_api_version($client, array());
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);
    }

    public function test_update_api_version() {
        global $DB;
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client1 = $generator->create_client(array('clienttype' => ''));
        $DB->set_field('shezar_connect_clients', 'timecreated', '11', array('id' => $client1->id));
        $DB->set_field('shezar_connect_clients', 'timemodified', '22', array('id' => $client1->id));
        $client1 = $DB->get_record('shezar_connect_clients', array('id' => $client1->id));

        $client2 = $generator->create_client(array('clienttype' => ''));

        $client3 = $generator->create_client(array('clienttype' => ''));
        $DB->set_field('shezar_connect_clients', 'timecreated', '666', array('id' => $client3->id));
        $DB->set_field('shezar_connect_clients', 'timemodified', '666', array('id' => $client3->id));
        $client3 = $DB->get_record('shezar_connect_clients', array('id' => $client3->id));

        $this->setCurrentTimeStart();
        $result = sep_services::update_api_version($client1, array('apiversion' => '1', 'clienttype' => 'shezarlms'));
        $this->assertSame('success', $result['status']);
        $c = $DB->get_record('shezar_connect_clients', array('id' => $client1->id));
        $this->assertSame('1', $c->apiversion);
        $this->assertSame('shezarlms', $c->clienttype);
        $this->assertTimeCurrent($c->timemodified);

        $this->setCurrentTimeStart();
        $result = sep_services::update_api_version($client2, array('apiversion' => '1', 'clienttype' => 'shezarsocial'));
        $this->assertSame('success', $result['status']);
        $c = $DB->get_record('shezar_connect_clients', array('id' => $client2->id));
        $this->assertSame('1', $c->apiversion);
        $this->assertSame('shezarsocial', $c->clienttype);
        $this->assertTimeCurrent($c->timemodified);

        // Now try all possible errors.

        $result = sep_services::update_api_version($client3, array('clienttype' => 'shezarlms'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing api version number', $result['data']['apiversion']);
        $this->assertSame($client3->timemodified, $DB->get_field('shezar_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('shezar_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('shezar_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => 'a', 'clienttype' => 'shezarlms'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing api version number', $result['data']['apiversion']);
        $this->assertSame($client3->timemodified, $DB->get_field('shezar_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('shezar_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('shezar_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => '100', 'clienttype' => 'shezarlms'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('unsupported api version number', $result['data']['apiversion']);
        $this->assertSame($client3->timemodified, $DB->get_field('shezar_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('shezar_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('shezar_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => '1', 'clienttype' => 'moodle'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);
        $this->assertSame($client3->timemodified, $DB->get_field('shezar_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('shezar_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('shezar_connect_clients', 'apiversion', array('id' => $client3->id)));

        $result = sep_services::update_api_version($client3, array('apiversion' => '1'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('incorrect or missing clienttype name', $result['data']['clienttype']);
        $this->assertSame($client3->timemodified, $DB->get_field('shezar_connect_clients', 'timemodified', array('id' => $client3->id)));
        $this->assertSame('', $DB->get_field('shezar_connect_clients', 'clienttype', array('id' => $client3->id)));
        $this->assertSame('1', $DB->get_field('shezar_connect_clients', 'apiversion', array('id' => $client3->id)));
    }

    public function test_get_users() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        delete_user($user4);

        $cohort = $this->getDataGenerator()->create_cohort();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client1 = $generator->create_client();

        $client2 = $generator->create_client(array('cohortid' => $cohort->id));
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        // Try client with all site users.

        $result = sep_services::get_users($client1, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(5, $result['data']['users']); // Normal 3 users + one deleted user + admin.

        $i = 0;
        foreach ($result['data']['users'] as $k => $u) {
            $this->assertSame($i++, $k);
            if ($u->id == $user4->id) {
                $this->assertSame('1', $u->deleted);
            } else {
                $this->assertSame('0', $u->deleted);
            }
            $this->assertNull($u->password);
            $this->assertObjectNotHasAttribute('secret', $u);
            $this->assertObjectNotHasAttribute('picture', $u);
            $this->assertObjectNotHasAttribute('pictures', $u);
            if ($u->deleted) {
                $this->assertNull($u->description);
                $this->assertNull($u->descriptionformat);
            } else {
                $this->assertObjectHasAttribute('description', $u);
                $this->assertObjectHasAttribute('descriptionformat', $u);
            }
        }

        // Try client with cohort restrictions and password sync.

        set_config('syncpasswords', '1', 'shezar_connect');
        $result = sep_services::get_users($client2, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']['users']); // Only 2 cohort members.
        $i = 0;
        foreach ($result['data']['users'] as $k => $u) {
            $this->assertSame($i++, $k);
            $this->assertTrue(cohort_is_member($cohort->id, $u->id));
            $this->assertNotNull($u->password);
            $this->assertObjectNotHasAttribute('secret', $u);
            $this->assertObjectNotHasAttribute('picture', $u);
            $this->assertObjectNotHasAttribute('pictures', $u);
            if ($u->deleted) {
                $this->assertNull($u->description);
                $this->assertNull($u->descriptionformat);
            } else {
                $this->assertObjectHasAttribute('description', $u);
                $this->assertObjectHasAttribute('descriptionformat', $u);
            }
        }
    }

    public function test_get_user_collections() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        delete_user($user4);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $cohort2 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user3->id);

        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);

        // Sortorders are busted and the generator uses APIs incorrectly.
        $course1 = $DB->get_record('course', array('id' => $course1->id));
        $course2 = $DB->get_record('course', array('id' => $course2->id));

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $client1 = $generator->create_client();
        util::add_client_cohort($client1, $cohort1->id);
        util::add_client_cohort($client1, $cohort2->id);
        util::add_client_course($client1, $course1->id);
        util::add_client_course($client1, $course2->id);

        $client2 = $generator->create_client(array('cohortid' => $cohort->id));
        util::add_client_cohort($client2, $cohort1->id);
        util::add_client_cohort($client2, $cohort2->id);
        util::add_client_course($client2, $course1->id);
        util::add_client_course($client2, $course2->id);

        $client3 = $generator->create_client();

        // Test unrestricted client with cohorts and courses.

        $result = sep_services::get_user_collections($client1, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']['cohort']);
        $this->assertCount(2, $result['data']['course']);

        $c1 = $result['data']['cohort'][0];
        $this->assertSame(array(array('id' => $user1->id), array('id' => $user2->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($cohort1, $c1);

        $c2 = $result['data']['cohort'][1];
        $this->assertSame(array(array('id' => $user1->id), array('id' => $user3->id)), $c2->members);
        unset($c2->members);
        $this->assertEquals($cohort2, $c2);

        $c1 = $result['data']['course'][0];
        $this->assertSame(array(array('id' => $user1->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($course1, $c1);

        $c2 = $result['data']['course'][1];
        $this->assertSame(array(array('id' => $user3->id)), $c2->members);
        unset($c2->members);
        $this->assertEquals($course2, $c2);

        // Test restricted client.

        $result = sep_services::get_user_collections($client2, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(2, $result['data']['cohort']);
        $this->assertCount(2, $result['data']['course']);

        $c1 = $result['data']['cohort'][0];
        $this->assertSame(array(array('id' => $user1->id), array('id' => $user2->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($cohort1, $c1);

        $c2 = $result['data']['cohort'][1];
        $this->assertSame(array(array('id' => $user1->id)), $c2->members);
        unset($c2->members);
        $this->assertEquals($cohort2, $c2);

        $c1 = $result['data']['course'][0];
        $this->assertSame(array(array('id' => $user1->id)), $c1->members);
        unset($c1->members);
        $this->assertEquals($course1, $c1);

        $c2 = $result['data']['course'][1];
        $this->assertSame(array(), $c2->members);
        unset($c2->members);
        $this->assertEquals($course2, $c2);

        // No cohort or course.
        $result = sep_services::get_user_collections($client3, array());
        $this->assertSame('success', $result['status']);
        $this->assertCount(0, $result['data']['cohort']);
        $this->assertCount(0, $result['data']['course']);
    }

    public function test_get_sso_user() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        // Some extra user session first.
        $this->setUser($user2);
        $session = util::create_sso_session($client);
        $session->sid = sha1('abc'); // Fake real session.
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        // The file handler is used by default, so let's fake the data somehow.
        $this->setUser($user1);
        $sid = md5('hokus');
        mkdir("$CFG->dataroot/sessions/", $CFG->directorypermissions, true);
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);

        // Try to find out if everything works.
        $this->assertTrue(\core\session\manager::session_exists($sid));
        $this->assertSame('0', $DB->get_field('shezar_connect_sso_sessions', 'active', array('id' => $session->id)));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $this->assertCount(54, (array)$result['data']);
        $this->assertSame('1', $DB->get_field('shezar_connect_sso_sessions', 'active', array('id' => $session->id)));

        $user = (object)$result['data'];
        $this->assertSame($user1->id, $user->id);
        $this->assertNull($user->password);
        $this->assertObjectNotHasAttribute('secret', $user);
        $this->assertSame('0', $user->picture);
        $this->assertSame(array(), $user->pictures);
        $this->assertSame(FORMAT_HTML, $user->descriptionformat);

        // Now with password.

        set_config('syncpasswords', '1', 'shezar_connect');

        $this->assertTrue(\core\session\manager::session_exists($sid));
        $DB->set_field('shezar_connect_sso_sessions', 'active', 0, array('id' => $session->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $this->assertCount(54, (array)$result['data']);
        $this->assertSame('1', $DB->get_field('shezar_connect_sso_sessions', 'active', array('id' => $session->id)));

        $user = (object)$result['data'];
        $this->assertSame($user1->id, $user->id);
        $this->assertNotNull($user->password);
        $this->assertObjectNotHasAttribute('secret', $user);
        $this->assertSame('0', $user->picture);
        $this->assertSame(array(), $user->pictures);

        // Test for all errors.

        $result = sep_services::get_sso_user($client, array());
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing sso token', $result['data']['ssotoken']);

        $result = sep_services::get_sso_user($client, array('ssotoken' => sha1('unknown')));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('invalid sso token', $result['data']['ssotoken']);

        // Reused ssotoken.

        $DB->set_field('shezar_connect_sso_sessions', 'active', 0, array('id' => $session->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('reused ssotoken', $result['message']);

        // Session timed out.

        unlink("$CFG->dataroot/sessions/sess_$sid");
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $this->assertTrue($DB->record_exists('shezar_connect_sso_sessions', array('id' => $session->id)));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('session expired', $result['message']);
        $this->assertFalse($DB->record_exists('shezar_connect_sso_sessions', array('id' => $session->id)));

        // Session messed up.

        $this->setUser($user1);
        $sid = md5('pokus');
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);
        $this->assertTrue(\core\session\manager::session_exists($sid));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);

        $DB->set_field('shezar_connect_sso_sessions', 'userid', $user2->id, array('id' => $session->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('invalid user session', $result['message']);
        $this->assertFalse($DB->record_exists('shezar_connect_sso_sessions', array('id' => $session->id)));

        // User incorrectly deleted.

        $this->setUser($user1);
        $sid = md5('roks');
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);
        $this->assertTrue(\core\session\manager::session_exists($sid));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);

        $DB->set_field('user', 'deleted', 1, array('id' => $user1->id));
        $result = sep_services::get_sso_user($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('error', $result['status']);
        $this->assertSame('invalid user session', $result['message']);
        $this->assertFalse($DB->record_exists('shezar_connect_sso_sessions', array('id' => $session->id)));
    }

    public function test_force_sso_logout() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        // Some extra user session first.
        $this->setUser($user2);
        $session = util::create_sso_session($client);
        $session->sid = sha1('abc'); // Fake real session.
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        // The file handler is used by default, so let's fake the data somehow.
        $this->setUser($user1);
        $sid = md5('hokus');
        mkdir("$CFG->dataroot/sessions/", $CFG->directorypermissions, true);
        touch("$CFG->dataroot/sessions/sess_$sid");
        $record = new \stdClass();
        $record->state        = 0;
        $record->sid          = $sid;
        $record->sessdata     = null;
        $record->userid       = $user1->id;
        $record->timecreated  = time() - 60*60;
        $record->timemodified = time() - 30;
        $record->firstip      = $record->lastip = '10.0.0.1';
        $record->id = $DB->insert_record('sessions', $record);
        $session = util::create_sso_session($client);
        $session->sid = $sid;
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));
        $this->setUser(null);
        $this->assertTrue(\core\session\manager::session_exists($sid));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);
        $this->assertFalse($DB->record_exists('shezar_connect_sso_sessions', array('id' => $session->id)));

        // Repeated execution not a problem.

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => $session->ssotoken));
        $this->assertSame('success', $result['status']);

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => sha1('xxzzxxz')));
        $this->assertSame('success', $result['status']);

        // Test errors.

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array());
        $this->assertSame('fail', $result['status']);
        $this->assertSame('missing sso token', $result['data']['ssotoken']);

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $result = sep_services::force_sso_logout($client, array('ssotoken' => 'xxxx'));
        $this->assertSame('fail', $result['status']);
        $this->assertSame('invalid sso token format', $result['data']['ssotoken']);
    }

    public function test_delete_client() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        delete_user($user4);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $cohort2 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort2->id, $user3->id);

        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $client1 = $generator->create_client();
        util::add_client_cohort($client1, $cohort1->id);
        util::add_client_cohort($client1, $cohort2->id);
        util::add_client_course($client1, $course1->id);
        util::add_client_course($client1, $course2->id);

        $client2 = $generator->create_client(array('cohortid' => $cohort->id));
        util::add_client_cohort($client2, $cohort1->id);
        util::add_client_cohort($client2, $cohort2->id);
        util::add_client_course($client2, $course1->id);
        util::add_client_course($client2, $course2->id);

        $client3 = $generator->create_client();

        $this->setUser($user1);
        util::create_sso_session($client1);
        util::create_sso_session($client2);

        $this->assertCount(3, $DB->get_records('shezar_connect_clients'));
        $this->assertCount(0, $DB->get_records('shezar_connect_clients', array('status' => util::CLIENT_STATUS_DELETED)));
        $this->assertCount(2, $DB->get_records('shezar_connect_sso_sessions'));
        $this->assertCount(4, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertCount(4, $DB->get_records('shezar_connect_client_courses'));
        $this->assertCount(1, $DB->get_records('shezar_connect_sso_sessions', array('clientid' => $client1->id)));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_cohorts', array('clientid' => $client1->id)));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_courses', array('clientid' => $client1->id)));

        $this->setCurrentTimeStart();
        $result = sep_services::delete_client($client1, array());
        $this->assertSame('success', $result['status']);
        $client = $DB->get_record('shezar_connect_clients', array('id' => $client1->id));
        $this->assertEquals(util::CLIENT_STATUS_DELETED, $client->status);
        $this->assertTimeCurrent($client->timemodified);

        $this->assertCount(3, $DB->get_records('shezar_connect_clients'));
        $this->assertCount(1, $DB->get_records('shezar_connect_clients', array('status' => util::CLIENT_STATUS_DELETED)));
        $this->assertCount(1, $DB->get_records('shezar_connect_sso_sessions'));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_courses'));
        $this->assertCount(0, $DB->get_records('shezar_connect_sso_sessions', array('clientid' => $client1->id)));
        $this->assertCount(0, $DB->get_records('shezar_connect_client_cohorts', array('clientid' => $client1->id)));
        $this->assertCount(0, $DB->get_records('shezar_connect_client_courses', array('clientid' => $client1->id)));

        // Run repeatedly.
        $result = sep_services::delete_client($client1, array());
        $this->assertSame('success', $result['status']);
    }
}
