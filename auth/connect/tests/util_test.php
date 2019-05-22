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
 * @package auth_connect
 */

use \auth_connect\util;
use \shezar_core\jsend;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests util class.
 */
class auth_connect_util_testcase extends advanced_testcase {
    public function test_create_unique_hash() {
        $hash = util::create_unique_hash('user', 'secret');
        $this->assertSame(40, strlen($hash));
        $this->assertNotSame($hash, util::create_unique_hash('user', 'secret'));
    }

    public function test_get_sep_url() {
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $record = array(
            'serverurl' => 'http://example.com/lms',
        );
        $server = $generator->create_server($record);
        $this->assertSame('http://example.com/lms/shezar/connect/sep.php', util::get_sep_url($server));
    }

    public function get_sso_request_url() {
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $record = array(
            'serverurl' => 'http://example.com/lms',
        );
        $server = $generator->create_server($record);
        $this->assertSame('http://example.com/lms/shezar/connect/sso_request.php', util::get_sso_request_url($server));
    }

    public function test_enable_registration() {
        $this->resetAfterTest();

        $prev = get_config('auth_connect', 'setupsecret');
        $this->assertFalse($prev);

        util::enable_registration();
        $result = get_config('auth_connect', 'setupsecret');
        $this->assertSame(40, strlen($result));
    }

    public function test_cancel_registration() {
        $this->resetAfterTest();

        util::enable_registration();
        $prev = get_config('auth_connect', 'setupsecret');
        $this->assertSame(40, strlen($prev));

        util::cancel_registration();
        $result = get_config('auth_connect', 'setupsecret');
        $this->assertFalse($result);
    }

    public function test_get_setup_secret() {
        $this->resetAfterTest();

        util::enable_registration();
        $prev = get_config('auth_connect', 'setupsecret');

        $result = util::get_setup_secret();
        $this->assertSame($prev, $result);
    }

    public function test_verify_setup_secret() {
        $this->resetAfterTest();

        util::enable_registration();
        $prev = get_config('auth_connect', 'setupsecret');

        // Auth not enabled.
        $result = util::verify_setup_secret($prev);
        $this->assertFalse($result);

        // Auth enabled.
        $this->set_auth_enabled(true);
        $result = util::verify_setup_secret($prev);
        $this->assertTrue($result);

        // Wrong secret.
        $result = util::verify_setup_secret($prev . 'xxx');
        $this->assertFalse($result);

        // No registration.
        util::cancel_registration();
        $result = util::verify_setup_secret($prev);
        $this->assertFalse($result);
        $result = util::verify_setup_secret('');
        $this->assertFalse($result);
        $result = util::verify_setup_secret(false);
        $this->assertFalse($result);
        $result = util::verify_setup_secret(null);
        $this->assertFalse($result);
    }

    public function test_select_api_version() {
        // Valid ranges - hardcoded to the only current supported value of 1.
        $this->assertSame(1, util::select_api_version(-1, 1));
        $this->assertSame(1, util::select_api_version(1, 1));
        $this->assertSame(1, util::select_api_version(0, 2));
        $this->assertSame(1, util::select_api_version(1, 2));

        // Now problems.
        $this->assertSame(0, util::select_api_version(2, 1));
        $this->assertSame(0, util::select_api_version(2, 2));
        $this->assertSame(0, util::select_api_version(0, 0));
        $this->assertSame(0, util::select_api_version(2, 3));
    }

    public function test_edit_server() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $data = new stdClass();
        $data->id = $server->id;
        $data->servercomment = 'lalalala';

        $this->setCurrentTimeStart();
        util::edit_server($data);

        $newserver = $DB->get_record('auth_connect_servers', array('id' => $server->id));
        $this->assertSame('lalalala', $newserver->servercomment);
        $this->assertTimeCurrent($newserver->timemodified);
    }

    public function test_delete_server() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        // Some extra stuff that should not be touched.
        $otheruser = $this->getDataGenerator()->create_user();
        $otherserver = $generator->create_server();
        $generator->migrate_server_user($otherserver, $otheruser, 111);

        // Delete everything including users.
        $server = $generator->create_server();
        $this->assertEquals(util::SERVER_STATUS_OK, $server->status);

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);
        $user = reset($users);
        delete_user($users[2]);

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercohorts[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();
        $servercourses[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));

        $collections = array('cohort' => $servercohorts, 'course' => $servercourses);
        util::update_local_user_collections($server, $collections);
        $cohorts1 = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $cohort1 = reset($cohorts1);
        $cohorts2 = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $cohort2 = reset($cohorts2);

        $data = new stdClass();
        $data->id = $server->id;
        $data->removeuser = 'delete';
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        util::delete_server($data);
        $this->assertFalse($DB->record_exists('auth_connect_servers', array('id' => $server->id)));
        $user = $DB->get_record('user', array('id' => $user->id));
        $this->assertSame('1', $user->deleted);
        $this->assertSame('0', $user->suspended);
        $this->assertSame(1, $DB->count_records('auth_connect_users', array()));
        $this->assertFalse($DB->record_exists('auth_connect_user_collections', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array()));
        $localcohort1 = $DB->get_record('cohort', array('id' => $cohort1->id), '*', MUST_EXIST);
        $this->assertSame('', $localcohort1->component);
        $localcohort2 = $DB->get_record('cohort', array('id' => $cohort2->id), '*', MUST_EXIST);
        $this->assertSame('', $localcohort2->component);

        // Delete everything, but suspend users only.
        $server = $generator->create_server();
        $this->assertEquals(util::SERVER_STATUS_OK, $server->status);
        $user = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user, 777);
        $userx = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $userx, 778);
        delete_user($userx);

        $data = new stdClass();
        $data->id = $server->id;
        $data->removeuser = 'suspend';
        $data->newauth    = 'manual';
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        util::delete_server($data);
        $this->assertFalse($DB->record_exists('auth_connect_servers', array('id' => $server->id)));
        $user = $DB->get_record('user', array('id' => $user->id));
        $this->assertSame('0', $user->deleted);
        $this->assertSame('1', $user->suspended);
        $this->assertSame('manual', $user->auth);
        $this->assertSame(1, $DB->count_records('auth_connect_users', array()));
        $this->assertFalse($DB->record_exists('auth_connect_user_collections', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array()));

        // Deleting on server fails.
        $server = $generator->create_server();
        $this->assertEquals(util::SERVER_STATUS_OK, $server->status);
        $user = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user, 666);

        $data = new stdClass();
        $data->id = $server->id;
        $data->removeuser = 'delete';
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'xxx')));
        util::delete_server($data);
        $server = $DB->get_record('auth_connect_servers', array('id' => $server->id));
        $this->assertEquals(util::SERVER_STATUS_DELETING, $server->status);
        $user = $DB->get_record('user', array('id' => $user->id));
        $this->assertSame('1', $user->deleted);
        $this->assertSame('0', $user->suspended);
        $this->assertSame(1, $DB->count_records('auth_connect_users', array()));
        $this->assertFalse($DB->record_exists('auth_connect_user_collections', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array()));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array()));
    }

    public function test_force_sso_logout() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $generator->create_server();
        $user1 = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user1, 111);
        $user2 = $this->getDataGenerator()->create_user();
        $generator->migrate_server_user($server, $user2, 222);

        $session1 = new stdClass();
        $session1->sid          = md5('xxx');
        $session1->ssotoken     = sha1('uuu');
        $session1->serverid     = $server->id;
        $session1->serveruserid = 111;
        $session1->userid       = $user1->id;
        $session1->timecreated  = time() - 600;
        $session1->id = $DB->insert_record('auth_connect_sso_sessions', $session1);
        $session1 = $DB->get_record('auth_connect_sso_sessions', array('id' => $session1->id));

        $session2 = new stdClass();
        $session2->sid          = md5('ytytr');
        $session2->ssotoken     = sha1('dsffdsfds');
        $session2->serverid     = $server->id;
        $session2->serveruserid = 222;
        $session2->userid       = $user2->id;
        $session2->timecreated  = time() - 10;
        $session2->id = $DB->insert_record('auth_connect_sso_sessions', $session2);
        $session2 = $DB->get_record('auth_connect_sso_sessions', array('id' => $session2->id));

        // Normal cleanup on server.
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        util::force_sso_logout($session1);
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $session1->id)));
        $this->assertTrue($DB->record_exists('auth_connect_sso_sessions', array('id' => $session2->id)));

        // Ignore server errors.
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'xxxx')));
        util::force_sso_logout($session2);
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $session1->id)));
        $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('id' => $session2->id)));
    }

    public function test_sync_users() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();

        $this->assertEquals(2, $DB->count_records('user', array()));
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('users' => $serverusers))));
        $resutl = util::sync_users($server);
        $this->assertTrue($resutl);
        $this->assertEquals(5, $DB->count_records('user', array()));

        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'some error')));
        $resutl = util::sync_users($server);
        $this->assertFalse($resutl);
        $this->assertEquals(5, $DB->count_records('user', array()));
    }

    public function test_update_local_users_basic() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();

        // Adding users.
        $this->assertEquals(2, $DB->count_records('user', array()));
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $users = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $this->assertSame('0', $user->deleted);
            $this->assertSame('0', $user->suspended);
            $this->assertSame('1', $user->confirmed);
            $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            $users[] = $user;
        }

        // Updating and deleting users deleted from server.
        $this->assertEquals(AUTH_REMOVEUSER_SUSPEND, get_config('auth_connect', 'removeuser'));
        $serverusers[0]['deleted'] = '1';
        $serverusers[1]['firstname'] = 'XxX';
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            if ($su->deleted == 0) {
                $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            }
            $newusers[] = $user;
        }

        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);
        $this->assertSame('XxX', $newusers[1]->firstname);

        // Suspending if server user missing.
        set_config('removeuser', AUTH_REMOVEUSER_SUSPEND, 'auth_connect');
        $smallerserverusers = array($serverusers[0], $serverusers[1]);
        util::update_local_users($server, $smallerserverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            if ($su->deleted == 0) {
                $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            } else {
                $this->assertNotSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            }
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('1', $newusers[2]->suspended);

        // Unsuspending when user reappears.
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            if ($su->deleted == 0) {
                $this->assertSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            } else {
                $this->assertNotSame('tc_' . $su->id . '_' . $server->serveridnumber, $user->username);
            }
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);

        // Deleting via delete when missing.
        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_connect');
        $smallerserverusers = array($serverusers[0], $serverusers[1]);
        util::update_local_users($server, $smallerserverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $newusers[] = $user;
        }
        $this->assertSame('1', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('1', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);

        // Bloody undelete on server and reappeared user.
        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_connect');
        $serverusers[0]['deleted'] = '0';
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        $newusers = array();
        foreach ($serverusers as $su) {
            $su = (object)$su;
            $sql = "SELECT u.*
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid =:serveruserid";
            $params = array('serverid' => $server->id, 'serveruserid' => $su->id);
            $user = $DB->get_record_sql($sql, $params, MUST_EXIST);
            $newusers[] = $user;
        }
        $this->assertSame('0', $newusers[0]->deleted);
        $this->assertSame('0', $newusers[0]->suspended);
        $this->assertSame('0', $newusers[1]->deleted);
        $this->assertSame('0', $newusers[1]->suspended);
        $this->assertSame('0', $newusers[2]->deleted);
        $this->assertSame('0', $newusers[2]->suspended);
    }

    public function test_update_local_users_deleting() {
        global $DB;
        $this->resetAfterTest();

        // Verify default settings.
        $this->assertEquals(AUTH_REMOVEUSER_SUSPEND, get_config('auth_connect', 'removeuser'));

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();

        $this->assertEquals(2, $DB->count_records('user', array()));
        util::update_local_users($server, $serverusers);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Keep one removed user and delete other.
        set_config('removeuser', AUTH_REMOVEUSER_KEEP, 'auth_connect');
        $serverusers['0']['deleted'] = '1';
        util::update_local_users($server, array($serverusers[0], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('1', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('0', $users[1]->deleted);
        $this->assertSame('0', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Suspend missing users.
        set_config('removeuser', AUTH_REMOVEUSER_SUSPEND, 'auth_connect');
        $serverusers['0']['deleted'] = '1';
        util::update_local_users($server, array($serverusers[0], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('1', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('0', $users[1]->deleted);
        $this->assertSame('1', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Delete missing users.
        set_config('removeuser', AUTH_REMOVEUSER_FULLDELETE, 'auth_connect');
        $serverusers['0']['deleted'] = '1';
        util::update_local_users($server, array($serverusers[0], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('1', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('1', $users[1]->deleted);
        $this->assertSame('1', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));

        // Undelete everything.
        $serverusers['0']['deleted'] = '0';
        util::update_local_users($server, array($serverusers[0], $serverusers[1], $serverusers[2]));

        $users = $this->fetch_local_server_users($server, $serverusers);
        $this->assertSame('0', $users[0]->deleted);
        $this->assertSame('0', $users[0]->suspended);
        $this->assertSame('0', $users[1]->deleted);
        $this->assertSame('0', $users[1]->suspended);
        $this->assertSame('0', $users[2]->deleted);
        $this->assertSame('0', $users[2]->suspended);
        $this->assertEquals(5, $DB->count_records('user', array()));
    }

    public function test_update_local_user() {
        global $DB;
        $this->resetAfterTest();

        set_config('syncpasswords', 0, 'shezar_connect');

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();
        $this->assertEquals(2, $DB->count_records('user', array()));

        // Add new user from server.
        $serveruser1 = $generator->get_fake_server_user(array('password' => 'testpw'));
        $this->assertNull($serveruser1['password']);
        $user1 = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame('connect', $user1->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1->username);
        $this->assertSame('not cached', $user1->password);
        $this->assertSame('0', $user1->deleted);
        $this->assertSame('0', $user1->suspended);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['email'], $user1->email);

        // Update existing user.
        $serveruser1['username']  = 'xxx';
        $serveruser1['firstname'] = 'XX';
        $serveruser1['lastname']  = 'ZZ';
        $serveruser1['email']     = 'xx@example.com';
        $user1b = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame($user1->id, $user1b->id);
        $this->assertSame('connect', $user1b->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1b->username);
        $this->assertSame('not cached', $user1b->password);
        $this->assertSame('0', $user1b->deleted);
        $this->assertSame('0', $user1b->suspended);
        $this->assertSame($serveruser1['firstname'], $user1b->firstname);
        $this->assertSame($serveruser1['lastname'], $user1b->lastname);
        $this->assertSame($serveruser1['email'], $user1b->email);
    }

    public function test_update_local_user_with_password() {
        global $DB;
        $this->resetAfterTest();

        set_config('syncpasswords', 1, 'shezar_connect');

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();
        $this->assertEquals(2, $DB->count_records('user', array()));

        // Add new user from server.
        $serveruser1 = $generator->get_fake_server_user(array('password' => 'testpw'));
        $this->assertNotEmpty($serveruser1['password']);
        $user1 = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame('connect', $user1->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1->username);
        $this->assertNotSame('not cached', $user1->password);
        $this->assertSame($serveruser1['password'], $user1->password);
        $this->assertSame('0', $user1->deleted);
        $this->assertSame('0', $user1->suspended);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['email'], $user1->email);

        // Update existing user.
        $serveruser1['username']  = 'xxx';
        $serveruser1['firstname'] = 'XX';
        $serveruser1['lastname']  = 'ZZ';
        $serveruser1['email']     = 'xx@example.com';
        $serveruser1['password']  = hash_internal_user_password('lalala');
        $user1b = util::update_local_user($server, $serveruser1);
        $this->assertEquals(3, $DB->count_records('user', array()));
        $this->assertTrue($DB->record_exists('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $serveruser1['id'])));
        $this->assertSame($user1->id, $user1b->id);
        $this->assertSame('connect', $user1b->auth);
        $this->assertSame('tc_' . $serveruser1['id'] . '_' . $server->serveridnumber, $user1b->username);
        $this->assertSame($serveruser1['password'], $user1b->password);
        $this->assertSame('0', $user1b->deleted);
        $this->assertSame('0', $user1b->suspended);
        $this->assertSame($serveruser1['firstname'], $user1b->firstname);
        $this->assertSame($serveruser1['lastname'], $user1b->lastname);
        $this->assertSame($serveruser1['email'], $user1b->email);
    }

    public function test_update_local_user_migration() {
        global $DB;
        $this->resetAfterTest();

        // Verify default settings.
        $this->assertSame('0', get_config('auth_connect', 'migrateusers'));
        $this->assertSame('username', get_config('auth_connect', 'migratemap'));

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $user1 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername1', 'email' => 'xemail1@example.com', 'idnumber' => 'xidnumber1')
        );
        $user2 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername2', 'email' => 'xemail2@example.com', 'idnumber' => 'xidnumber2')
        );
        $user3 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername3', 'email' => 'xemail3@example.com', 'idnumber' => 'xidnumber3')
        );
        $user4 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername4', 'email' => 'xemail4@example.com', 'idnumber' => 'xidnumber4')
        );
        $this->assertCount(6, $DB->get_records('user', array()));

        // No migration.
        set_config('migrateusers', '0', 'auth_connect');
        $serveruser1 = $generator->get_fake_server_user();
        $serveruser1['username'] = $user1->username;
        $serveruser1['email'] = $user1->email;
        $serveruser1['idnumber'] = $user1->idnumber;

        $newuser1 = util::update_local_user($server, $serveruser1);
        $this->assertInstanceOf('stdClass', $newuser1);
        $this->assertGreaterThan($user3->id, $newuser1->id);
        $this->assertStringStartsWith('tc_', $newuser1->username);
        $this->assertSame($user1->email, $newuser1->email);
        $this->assertSame('', $newuser1->idnumber);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via username.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'username', 'auth_connect');
        $serveruser2 = $generator->get_fake_server_user();
        $serveruser2['username'] = $user2->username;

        $newuser2 = util::update_local_user($server, $serveruser2);
        $this->assertSame($user2->id, $newuser2->id);
        $this->assertSame($user2->username, $newuser2->username); // Always keep username for existing accounts.
        $this->assertSame($serveruser2['email'], $newuser2->email);
        $this->assertSame($serveruser2['idnumber'], $newuser2->idnumber);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via email.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'email', 'auth_connect');
        $serveruser3 = $generator->get_fake_server_user();
        $serveruser3['email'] = $user3->email;

        $newuser3 = util::update_local_user($server, $serveruser3);
        $this->assertSame($user3->id, $newuser3->id);
        $this->assertSame($user3->email, $newuser3->email);
        $this->assertSame($user3->username, $newuser3->username); // Always keep username for existing accounts.
        $this->assertSame($serveruser3['idnumber'], $newuser3->idnumber);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via idnumber.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'idnumber', 'auth_connect');
        $serveruser4 = $generator->get_fake_server_user();
        $serveruser4['idnumber'] = $user4->idnumber;

        $newuser4 = util::update_local_user($server, $serveruser4);
        $this->assertSame($user4->id, $newuser4->id);
        $this->assertSame($user4->idnumber, $newuser4->idnumber);
        $this->assertSame($user4->username, $newuser4->username); // Always keep username for existing accounts.
        $this->assertSame($serveruser4['email'], $newuser4->email);
        $this->assertCount(7, $DB->get_records('user', array()));

        // Migrate via TC unique id.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'uniqueid', 'auth_connect');
        $serveruser5 = $generator->get_fake_server_user();
        $uniqueid = 'tc_' . $serveruser5['id'] . '_' . $server->serveridnumber;
        $user5 = $this->getDataGenerator()->create_user(array('username' => $uniqueid));

        $newuser5 = util::update_local_user($server, $serveruser5);
        $this->assertSame($user5->id, $newuser5->id);
        $this->assertSame($uniqueid, $newuser5->username);
        $this->assertSame($serveruser5['email'], $newuser5->email);
        $this->assertSame($serveruser5['idnumber'], $newuser5->idnumber);
        $this->assertCount(8, $DB->get_records('user', array()));

        // Do not migrate accounts from connect auth.
        set_config('migrateusers', '1', 'auth_connect');
        set_config('migratemap', 'username', 'auth_connect');
        $user6 = $this->getDataGenerator()->create_user(
            array('username' => 'xusername6', 'email' => 'xemail6@example.com', 'idnumber' => 'xidnumber6', 'auth' => 'connect')
        );
        $serveruser6 = $generator->get_fake_server_user();
        $serveruser6['username'] = $user6->username;

        $newuser6 = util::update_local_user($server, $serveruser6);
        $this->assertGreaterThan($user6->id, $newuser6->id);
        $this->assertStringStartsWith('tc_', $newuser6->username);
        $this->assertSame($serveruser6['email'], $newuser6->email);
        $this->assertSame($serveruser6['idnumber'], $newuser6->idnumber);
        $this->assertCount(10, $DB->get_records('user', array()));
    }

    public function test_sync_user_collections() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();
        $servercourses[] = $generator->get_fake_server_course();
        $collections = array('cohort' => $servercohorts, 'course' => $servercourses);

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $collections)));
        $result = util::sync_user_collections($server);
        $this->assertTrue($result);
        $this->assertCount(4, $DB->get_records('cohort', array()));

        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'xxx')));
        $result = util::sync_user_collections($server);
        $this->assertFalse($result);
        $this->assertCount(4, $DB->get_records('cohort', array()));
    }

    public function test_update_local_user_collections() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();
        $servercohorts[] = $generator->get_fake_server_cohort();

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();
        $servercourses[] = $generator->get_fake_server_course();

        // Adding.
        $collections = array('cohort' => $servercohorts, 'course' => $servercourses);
        $this->assertCount(0, $DB->get_records('cohort', array()));
        util::update_local_user_collections($server, $collections);
        $this->assertCount(4, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertNotNull($cohorts[0]);
        $this->assertNotNull($cohorts[1]);
        $courses = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertNotNull($courses[0]);
        $this->assertNotNull($courses[1]);

        // Deleting
        $collections = array('cohort' => array($servercohorts[0]), 'course' => array($servercourses[0]));
        util::update_local_user_collections($server, $collections);
        $this->assertCount(2, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertNotNull($cohorts[0]);
        $this->assertNull($cohorts[1]);
        $courses = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertNotNull($courses[0]);
        $this->assertNull($courses[1]);
    }

    public function test_update_local_user_collection_cohort_properties() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();

        // Test adding.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertSame($servercohorts[0]['name'], $cohorts[0]->name);
        $this->assertSame('tc_cohort_' . $servercohorts[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercohorts[0]['description'], $cohorts[0]->description);
        $this->assertSame($servercohorts[0]['descriptionformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);

        // Test updating.
        $servercohorts[0]['name'] = 'xxxx';
        $servercohorts[0]['description'] = 'aassasa';
        $servercohorts[0]['component'] = 'auth_ldap';
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertSame($servercohorts[0]['name'], $cohorts[0]->name);
        $this->assertSame('tc_cohort_' . $servercohorts[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercohorts[0]['description'], $cohorts[0]->description);
        $this->assertSame($servercohorts[0]['descriptionformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);
    }

    public function test_update_local_user_collection_cohort_membership() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);

        $servercohorts = array();
        $servercohorts[] = $generator->get_fake_server_cohort();

        // Test user membership sync.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        $this->assertCount(0, $DB->get_records('cohort_members', array()));

        $servercohorts[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(2, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[1]->id));

        $servercohorts[0]['members']= array(array('id' => $serverusers[0]['id']));
        util::update_local_user_collection($server, 'cohort', $servercohorts[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(1, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'cohort', $servercohorts);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertFalse(cohort_is_member($cohorts[0]->id, $users[1]->id));
    }

    public function test_update_local_user_collection_course_properties() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();

        // Test adding.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertSame($servercourses[0]['fullname'], $cohorts[0]->name);
        $this->assertSame('tc_course_' . $servercourses[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercourses[0]['summary'], $cohorts[0]->description);
        $this->assertSame($servercourses[0]['summaryformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);

        // Test updating.
        $servercourses[0]['fullname'] = 'xxxx';
        $servercourses[0]['summary'] = 'aassasa';
        $servercourses[0]['component'] = 'auth_ldap';
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertSame($servercourses[0]['fullname'], $cohorts[0]->name);
        $this->assertSame('tc_course_' . $servercourses[0]['id'] . '_' . $server->serveridnumber, $cohorts[0]->idnumber);
        $this->assertSame($servercourses[0]['summary'], $cohorts[0]->description);
        $this->assertSame($servercourses[0]['summaryformat'], $cohorts[0]->descriptionformat);
        $this->assertSame('auth_connect', $cohorts[0]->component);
    }

    public function test_update_local_user_collection_course_membership() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $serverusers = array();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        $serverusers[] = $generator->get_fake_server_user();
        util::update_local_users($server, $serverusers);
        $users = $this->fetch_local_server_users($server, $serverusers);

        $servercourses = array();
        $servercourses[] = $generator->get_fake_server_course();

        // Test user membership sync.
        $this->assertCount(0, $DB->get_records('cohort', array()));
        $this->assertCount(0, $DB->get_records('cohort_members', array()));

        $servercourses[0]['members'] = array(array('id' => $serverusers[0]['id']), array('id' => $serverusers[1]['id']));
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(2, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[1]->id));

        $servercourses[0]['members']= array(array('id' => $serverusers[0]['id']));
        util::update_local_user_collection($server, 'course', $servercourses[0]);
        $this->assertCount(1, $DB->get_records('cohort', array()));
        $this->assertCount(1, $DB->get_records('cohort_members', array()));

        $cohorts = $this->fetch_local_server_cohorts($server, 'course', $servercourses);
        $this->assertTrue(cohort_is_member($cohorts[0]->id, $users[0]->id));
        $this->assertFalse(cohort_is_member($cohorts[0]->id, $users[1]->id));
    }

    public function test_update_local_user_pictures() {
        global $DB;
        $this->resetAfterTest();

        set_config('syncpasswords', 0, 'shezar_connect');
        $fs = get_file_storage();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();
        $this->assertEquals(2, $DB->count_records('user', array()));

        // Add new user from server.
        $serveruser1 = $generator->get_fake_server_user(array('password' => 'testpw'));
        $serveruser1['picture'] = '100';
        $serveruser1['pictures'] = array(
            'f1.png' => base64_encode('xx'),
            'f2.png' => base64_encode('xxx'),
            'f3.png' => base64_encode('xxxx'),
        );
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));

        // Update pictures only if picture flag changes.
        $serveruser1['picture'] = '100';
        $serveruser1['pictures'] = array(
            'f1.jpg' => base64_encode('xx'),
            'f2.jpg' => base64_encode('xxx'),
            'f3.jpg' => base64_encode('xxxx'),
        );
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.jpg'));

        $serveruser1['picture'] = '101';
        $serveruser1['pictures'] = array(
            'f1.jpg' => base64_encode('xx'),
            'f2.jpg' => base64_encode('xxx'),
            'f3.jpg' => base64_encode('xxxx'),
        );
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.jpg'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.jpg'));
        $this->assertTrue($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.jpg'));

        // Delete pictures.
        $serveruser1['picture'] = '0';
        $serveruser1['pictures'] = array();
        $user1 = util::update_local_user($server, $serveruser1, true);
        $this->assertSame($serveruser1['firstname'], $user1->firstname);
        $this->assertSame($serveruser1['lastname'], $user1->lastname);
        $this->assertSame($serveruser1['picture'], $user1->picture);
        $this->assertObjectNotHasAttribute('pictures', $user1);
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.png'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f1.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f2.jpg'));
        $this->assertFalse($fs->file_exists(context_user::instance($user1->id)->id, 'user', 'icon', 0, '/', 'f3.jpg'));
    }

    public function test_finish_sso() {
        global $DB, $SESSION, $USER;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');

        $server = $generator->create_server();
        $serveruser = $generator->get_fake_server_user();
        util::update_local_user($server, $serveruser);

        $ssotoken = sha1('fogpigfd');
        $sid = md5('xxxzfzfdz');

        session_id($sid);
        $this->setUser(null);
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $serveruser)));

        $this->setCurrentTimeStart();
        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('redirect expected on successful sso');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $session = $DB->get_record('auth_connect_sso_sessions', array('ssotoken' => $ssotoken), '*', MUST_EXIST);
        $this->assertSame($server->id, $session->serverid);
        $this->assertSame($sid, $session->sid);
        $this->assertSame($sid, $session->sid);
        $this->assertSame($ssotoken, $session->ssotoken);
        $this->assertSame($server->id, $session->serverid);
        $this->assertSame($serveruser['id'], $session->serveruserid);
        $this->assertSame($USER->id, $session->userid);
        $this->assertTimeCurrent($session->timecreated);
        $expected = new stdClass();
        $this->assertEquals($expected, $SESSION);

        // Verify guest user may log in too.
        $DB->delete_records('auth_connect_sso_sessions', array());

        $ssotoken = sha1('aaaa');
        $sid = md5('rerereer');

        session_id($sid);
        $this->setGuestUser();
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $serveruser)));

        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('redirect expected on successful sso');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
        $session = $DB->get_record('auth_connect_sso_sessions', array('ssotoken' => $ssotoken), '*', MUST_EXIST);
        $expected = new stdClass();
        $this->assertEquals($expected, $SESSION);

        // Set session flag on failure.
        $ssotoken = sha1('wwww');
        $sid = md5('qqqqq');

        session_id($sid);
        $this->setUser(null);
        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'no way')));

        $this->setCurrentTimeStart();
        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('redirect expected on falied sso');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
            $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('ssotoken' => $ssotoken)));
            $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('ssotoken' => $ssotoken)));
            $expected = new stdClass();
            $expected->loginerrormsg = 'Single sign-on failed';
            $expected->authconnectssofailed = 1;
            $this->assertEquals($expected, $SESSION);
        }

        // Make sure logged in users cannot start SSO.
        $DB->delete_records('auth_connect_sso_sessions', array());

        $ssotoken = sha1('ppp');
        $sid = md5('yyyyy');

        session_id($sid);
        $admin = get_admin();
        \core\session\manager::set_user($admin); // Note setAdminUser() clears session now.
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => $serveruser)));

        try {
            @util::finish_sso($server, $ssotoken);
            $this->fail('coding exception expected whe nuser already logged in');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: user must not be logged in yet', $ex->getMessage());
            $this->assertFalse($DB->record_exists('auth_connect_sso_sessions', array('ssotoken' => $ssotoken)));
            $this->assertEquals($expected, $SESSION);
        }
    }

    public function test_create_sso_request() {
        global $DB;
        $this->resetAfterTest();

        /** @var auth_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_connect');
        $server = $generator->create_server();

        $sid1 = md5('xxx');
        $sid2 = md5('yyy');
        $sid3 = md5('zzz');

        $this->set_auth_enabled(true);
        session_id($sid1);
        $this->setUser(null);

        // Valid first.
        $this->setCurrentTimeStart();
        $result1 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result1);
        $request1 = $DB->get_record('auth_connect_sso_requests', array('sid' => $sid1));
        $this->assertTimeCurrent($request1->timecreated);
        $this->assertSame($server->id, $request1->serverid);
        $this->assertSame(40, strlen($request1->requesttoken));
        $this->assertCount(1, $DB->get_records('auth_connect_sso_requests'));

        // Allow repeated request.
        $result2 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result2);
        $this->assertEquals($result1, $result2);
        $this->assertCount(1, $DB->get_records('auth_connect_sso_requests'));

        $request1->timecreated = $request1->timecreated - util::REQUEST_LOGIN_TIMEOUT + 10;
        $DB->update_record('auth_connect_sso_requests', $request1);
        $result2b = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result2b);
        $this->assertEquals($result2, $result2b);
        $this->assertCount(1, $DB->get_records('auth_connect_sso_requests'));
        $this->assertTrue($DB->record_exists('auth_connect_sso_requests', array('id' => $request1->id)));

        // Next request as guest.
        $this->setGuestUser();
        session_id($sid2);
        $result3 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result3);
        $this->assertNotEquals($result1, $result3);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $request2 = $DB->get_record('auth_connect_sso_requests', array('sid' => $sid2));

        // Create new if expired.
        $request2->timecreated = $request2->timecreated - util::REQUEST_LOGIN_TIMEOUT - 1;
        $DB->update_record('auth_connect_sso_requests', $request2);
        $result4 = util::create_sso_request($server);
        $this->assertInstanceOf('moodle_url', $result3);
        $this->assertNotEquals($result3, $result4);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $this->assertFalse($DB->record_exists('auth_connect_sso_requests', array('id' => $request2->id)));

        // Not enabled.
        $this->set_auth_enabled(false);
        session_id($sid3);
        $this->setUser(null);
        $result = util::create_sso_request($server);
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $this->set_auth_enabled(true);

        // Is logged in.
        $this->setAdminUser();
        session_id($sid3);
        $result = util::create_sso_request($server);
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
        $this->setUser(null);

        // No session.
        session_id('');
        $result = util::create_sso_request($server);
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));

        // Deleting.
        session_id($sid3);
        $server->status = util::SERVER_STATUS_DELETING;
        $this->assertNull($result);
        $this->assertCount(2, $DB->get_records('auth_connect_sso_requests'));
    }

    public function test_validate_sso_possible() {
        $this->resetAfterTest();

        try {
            util::validate_sso_possible();
            $this->fail('exception expected when not auth enabled');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }

        $this->set_auth_enabled(true);
        util::validate_sso_possible();

        $this->setGuestUser();
        util::validate_sso_possible();

        $this->setAdminUser();
        try {
            util::validate_sso_possible();
            $this->fail('exception expected when user logged in');
        } catch (moodle_exception $ex) {
            $this->assertSame('Unsupported redirect detected, script execution terminated', $ex->getMessage());
        }
    }

    public function test_warn_if_not_https() {
        global $CFG;
        $this->resetAfterTest();

        // No warning expected on HTTPS sites.
        $CFG->wwwroot = 'https://example.com/lms';
        $this->assertSame('', util::warn_if_not_https());

        // Some warning expected on https.
        $CFG->wwwroot = 'http://example.com/lms';
        $this->assertSame("!! For security reasons all shezar Connect clients should be hosted via a secure protocol (https). !!\n", util::warn_if_not_https());
    }

    /**
     * Enable/disable auth_connect plugin.
     *
     * @param bool $enabled
     */
    protected function set_auth_enabled($enabled) {
        global $CFG;
        $authsenabled = explode(',', $CFG->auth);

        if ($enabled) {
            $authsenabled[] = 'connect';
            $authsenabled = array_unique($authsenabled);
            set_config('auth', implode(',', $authsenabled));
        } else {
            $key = array_search('connect', $authsenabled);
            if ($key !== false) {
                unset($authsenabled[$key]);
                set_config('auth', implode(',', $authsenabled));
            }
        }
    }

    /**
     * Gets list of local users linked to $serverusers, keep the same array order.
     * @param stdClass $server
     * @param array $serverusers
     * @return array of local users with keys matching the original array
     */
    protected function fetch_local_server_users($server, array $serverusers) {
        global $DB;

        $users = array();
        foreach ($serverusers as $k => $suser) {
            $mapping = $DB->get_record('auth_connect_users', array('serverid' => $server->id, 'serveruserid' => $suser['id']));
            if (!$mapping) {
                $users[$k] = null;
                continue;
            }
            $users[$k] = $DB->get_record('user', array('id' => $mapping->userid));
        }

        return $users;
    }

    /**
     * Gets list of local cohorts linked to $servercohorts, keep the same array order.
     * @param stdClass $server
     * @param string $type 'cohort' or 'course'
     * @param array $servercollections
     * @return array of local cohorts with keys matching the original array
     */
    protected function fetch_local_server_cohorts($server, $type, array $servercollections) {
        global $DB;

        $cohorts = array();
        foreach ($servercollections as $k => $col) {
            $mapping = $DB->get_record('auth_connect_user_collections', array('serverid' => $server->id, 'collectiontype' => $type, 'collectionid' => $col['id']));
            if (!$mapping) {
                $cohorts[$k] = null;
                continue;
            }
            $cohorts[$k] = $DB->get_record('cohort', array('id' => $mapping->cohortid));
        }

        return $cohorts;
    }
}
