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

use \shezar_connect\util;
use \shezar_core\jsend;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests util class.
 */
class shezar_connect_util_testcase extends advanced_testcase {

    public function test_create_unique_hash() {
        $hash = util::create_unique_hash('user', 'secret');
        $this->assertSame(40, strlen($hash));
        $this->assertNotSame($hash, util::create_unique_hash('user', 'secret'));
    }

    public function test_get_sep_setup_url() {
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $record = array(
            'clienttype' => 'shezarlms',
            'clienturl' => 'http://example.com/lms',
        );
        $client = $generator->create_client($record);
        $this->assertSame('http://example.com/lms/auth/connect/sep_setup.php', util::get_sep_setup_url($client));

        $record = array(
            'clienttype' => 'shezarsocial',
            'clienturl' => 'http://example.com/social',
        );
        $client = $generator->create_client($record);
        $this->assertSame('http://example.com/social/auth/connect/sep_setup.php', util::get_sep_setup_url($client));
    }

    public function test_get_sep_url() {
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $record = array(
            'clienttype' => 'shezarlms',
            'clienturl' => 'http://example.com/lms',
        );
        $client = $generator->create_client($record);
        $this->assertSame('http://example.com/lms/auth/connect/sep.php', util::get_sep_url($client));

        $record = array(
            'clienttype' => 'shezarsocial',
            'clienturl' => 'http://example.com/social',
        );
        $client = $generator->create_client($record);
        $this->assertSame('http://example.com/social/auth/connect/sep.php', util::get_sep_url($client));
    }

    public function test_get_sso_finish_url() {
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $record = array(
            'clienttype' => 'shezarlms',
            'clienturl' => 'http://example.com/lms',
        );
        $client = $generator->create_client($record);
        $this->assertSame('http://example.com/lms/auth/connect/sso_finish.php', util::get_sso_finish_url($client));

        $record = array(
            'clienttype' => 'shezarsocial',
            'clienturl' => 'http://example.com/social',
        );
        $client = $generator->create_client($record);
        $this->assertSame('http://example.com/social/auth/connect/sso_finish.php', util::get_sso_finish_url($client));
    }

    public function test_add_client() {
        global $DB;
        $this->resetAfterTest();

        $data = new \stdClass();
        $data->clientname = 'My test client';
        $data->clienturl = 'https://www.example.com/shezar';
        $data->setupsecret = sha1('xxzxxzzx');
        $data->clientcomment = 'Test client';
        $data->cohortid = null;
        $data->addnewcohorts = '';
        $data->addnewcourses = '';
        $data->cohorts = '';
        $data->courses = '';

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));

        $this->setCurrentTimeStart();
        $clientid = util::add_client($data);
        $this->assertGreaterThan(0, $clientid);
        $client = $DB->get_record('shezar_connect_clients', array('id' => $clientid));
        $this->assertCount(0, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertCount(0, $DB->get_records('shezar_connect_client_courses'));

        $this->assertEquals(util::CLIENT_STATUS_OK, $client->status);
        $this->assertSame(40, strlen($client->clientidnumber));
        $this->assertSame(40, strlen($client->clientsecret));
        $this->assertSame('My test client', $client->clientname);
        $this->assertSame('https://www.example.com/shezar', $client->clienturl);
        $this->assertSame('', $client->clienttype);
        $this->assertSame('Test client', $client->clientcomment);
        $this->assertSame(null, $client->cohortid);
        $this->assertSame(40, strlen($client->serversecret));
        $this->assertSame('0', $client->addnewcohorts);
        $this->assertSame('0', $client->addnewcourses);
        $this->assertSame('1', $client->apiversion);
        $this->assertTimeCurrent($client->timecreated);
        $this->assertSame($client->timecreated, $client->timemodified);

        $data = new \stdClass();
        $data->clientname = 'My test client';
        $data->clienturl = 'https://www.example.com/shezar';
        $data->setupsecret = sha1('xxzxxzzx');
        $data->clientcomment = 'Test client';
        $data->cohortid = null;
        $data->addnewcohorts = '';
        $data->addnewcourses = '';
        $data->cohorts = '';
        $data->courses = '';

        // Test all options

        $cohort = $this->getDataGenerator()->create_cohort();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $data = new \stdClass();
        $data->clientname = 'My test client 2';
        $data->clienturl = 'https://www.example.com/social';
        $data->setupsecret = sha1('abc');
        $data->clientcomment = 'Test other client';
        $data->cohortid = $cohort->id;
        $data->addnewcohorts = '1';
        $data->addnewcourses = '1';
        $data->cohorts = $cohort1->id . ',' . $cohort2->id;
        $data->courses = $course1->id . ',' . $course2->id;

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));

        $this->setCurrentTimeStart();
        $clientid = util::add_client($data);
        $this->assertGreaterThan(0, $clientid);
        $client = $DB->get_record('shezar_connect_clients', array('id' => $clientid));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohort1->id)));
        $this->assertTrue($DB->record_exists('shezar_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohort2->id)));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_courses'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_courses', array('clientid' => $client->id, 'courseid' => $course1->id)));
        $this->assertTrue($DB->record_exists('shezar_connect_client_courses', array('clientid' => $client->id, 'courseid' => $course2->id)));

        $this->assertEquals(util::CLIENT_STATUS_OK, $client->status);
        $this->assertSame(40, strlen($client->clientidnumber));
        $this->assertSame(40, strlen($client->clientsecret));
        $this->assertSame('My test client 2', $client->clientname);
        $this->assertSame('https://www.example.com/social', $client->clienturl);
        $this->assertSame('', $client->clienttype);
        $this->assertSame('Test other client', $client->clientcomment);
        $this->assertSame($cohort->id, $client->cohortid);
        $this->assertSame(40, strlen($client->serversecret));
        $this->assertSame('1', $client->addnewcohorts);
        $this->assertSame('1', $client->addnewcourses);
        $this->assertSame('1', $client->apiversion);
        $this->assertTimeCurrent($client->timecreated);
        $this->assertSame($client->timecreated, $client->timemodified);

        // Fail answer from client.

        $data = new \stdClass();
        $data->clientname = 'My test client 2';
        $data->clienturl = 'https://www.example.com/social';
        $data->setupsecret = sha1('abc');
        $data->clientcomment = 'Test other client';
        $data->cohortid = $cohort->id;
        $data->addnewcohorts = '1';
        $data->addnewcourses = '1';
        $data->cohorts = $cohort1->id . ',' . $cohort2->id;
        $data->courses = $course1->id . ',' . $course2->id;

        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'bad luck')));

        $clientid = util::add_client($data);
        $this->assertFalse($clientid);
        $this->assertCount(2, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_courses'));
    }

    public function test_edit_client() {
        global $DB;
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort();
        $cohortb = $this->getDataGenerator()->create_cohort();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        $data = new stdClass();
        $data->clientname = 'My test client';
        $data->clienturl = 'https://www.example.com/social';
        $data->setupsecret = sha1('abc');
        $data->clientcomment = 'Test other client';
        $data->cohortid = $cohort->id;
        $data->addnewcohorts = '1';
        $data->addnewcourses = '1';
        $data->cohorts = $cohort1->id . ',' . $cohort2->id;
        $data->courses = $course1->id . ',' . $course2->id;

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $clientid = util::add_client($data);
        $DB->set_field('shezar_connect_clients', 'clienttype', 'shezarsocial', array('id' => $clientid));
        $client = $DB->get_record('shezar_connect_clients', array('id' => $clientid));

        $data = new stdClass();
        $data->id = $client->id;
        $data->clientname = 'New test client';
        $data->clientcomment = 'Yes, changed.';
        $data->cohortid = $cohortb->id;
        $data->addnewcohorts = '0';
        $data->addnewcourses = '0';
        $data->cohorts = $cohort2->id . ',' . $cohort3->id;
        $data->courses = $course2->id . ',' . $course3->id;

        $this->setCurrentTimeStart();
        util::edit_client($data);
        $newclient = $DB->get_record('shezar_connect_clients', array('id' => $client->id));
        $this->assertTimeCurrent($newclient->timemodified);

        $this->assertEquals(util::CLIENT_STATUS_OK, $newclient->status);
        $this->assertSame($client->clientidnumber, $newclient->clientidnumber);
        $this->assertSame($client->clientsecret, $newclient->clientsecret);
        $this->assertSame('New test client', $newclient->clientname);
        $this->assertSame('https://www.example.com/social', $newclient->clienturl);
        $this->assertSame('shezarsocial', $newclient->clienttype);
        $this->assertSame('Yes, changed.', $newclient->clientcomment);
        $this->assertSame($cohortb->id, $newclient->cohortid);
        $this->assertSame($client->serversecret, $newclient->serversecret);
        $this->assertSame('0', $newclient->addnewcohorts);
        $this->assertSame('0', $newclient->addnewcourses);
        $this->assertSame('1', $newclient->apiversion);
        $this->assertCount(2, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohort2->id)));
        $this->assertTrue($DB->record_exists('shezar_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohort3->id)));
        $this->assertCount(2, $DB->get_records('shezar_connect_client_courses'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_courses', array('clientid' => $client->id, 'courseid' => $course2->id)));
        $this->assertTrue($DB->record_exists('shezar_connect_client_courses', array('clientid' => $client->id, 'courseid' => $course3->id)));
    }

    public function test_purge_deleted_client() {
        global $DB;
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $client1 = $generator->create_client();
        $client2 = $generator->create_client();

        util::add_client_cohort($client1, $cohort1->id);
        util::add_client_cohort($client1, $cohort2->id);
        util::add_client_cohort($client2, $cohort1->id);

        util::add_client_course($client1, $course1->id);
        util::add_client_course($client1, $course2->id);
        util::add_client_course($client2, $course1->id);

        $this->assertCount(2, $DB->get_records('shezar_connect_clients'));
        $this->assertCount(3, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertCount(3, $DB->get_records('shezar_connect_client_courses'));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_cohorts', array('clientid' => $client2->id)));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_courses', array('clientid' => $client2->id)));

        // Make sure active cannot be deleted.
        try {
            util::purge_deleted_client($client1);
            $this->fail('coding exception expected when purging active client.');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        // Make sure purge deletes all related data.
        $DB->set_field('shezar_connect_clients', 'status', util::CLIENT_STATUS_DELETED, array('id' => $client1->id));
        $client1->status = util::CLIENT_STATUS_DELETED;

        util::purge_deleted_client($client1);
        $this->assertCount(2, $DB->get_records('shezar_connect_clients'));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_courses'));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_cohorts', array('clientid' => $client2->id)));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_courses', array('clientid' => $client2->id)));
    }

    public function test_add_client_cohort() {
        global $DB;
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $client = $generator->create_client();
        $this->assertCount(0, $DB->get_records('shezar_connect_client_cohorts'));

        util::add_client_cohort($client, $cohort1->id);
        $this->assertCount(1, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohort1->id)));

        util::add_client_cohort($client, $cohort2->id);
        $this->assertCount(2, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohort1->id)));
        $this->assertTrue($DB->record_exists('shezar_connect_client_cohorts', array('clientid' => $client->id, 'cohortid' => $cohort2->id)));
    }

    public function test_remove_client_cohort() {
        global $DB;
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $client1 = $generator->create_client();
        $client2 = $generator->create_client();

        util::add_client_cohort($client1, $cohort1->id);
        util::add_client_cohort($client1, $cohort2->id);
        util::add_client_cohort($client2, $cohort2->id);

        $this->assertCount(3, $DB->get_records('shezar_connect_client_cohorts'));

        util::remove_client_cohort($client1, $cohort1->id);

        $this->assertCount(2, $DB->get_records('shezar_connect_client_cohorts'));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_cohorts', array('clientid' => $client1->id)));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_cohorts', array('clientid' => $client2->id)));
    }

    public function test_remove_client_course() {
        global $DB;
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $client1 = $generator->create_client();
        $client2 = $generator->create_client();

        util::add_client_course($client1, $course1->id);
        util::add_client_course($client1, $course2->id);
        util::add_client_course($client2, $course2->id);

        $this->assertCount(3, $DB->get_records('shezar_connect_client_courses'));

        util::remove_client_course($client1, $course1->id);

        $this->assertCount(2, $DB->get_records('shezar_connect_client_courses'));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_courses', array('clientid' => $client1->id)));
        $this->assertCount(1, $DB->get_records('shezar_connect_client_courses', array('clientid' => $client2->id)));
    }

    public function test_add_client_course() {
        global $DB;
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $client = $generator->create_client();
        $this->assertCount(0, $DB->get_records('shezar_connect_client_courses'));

        util::add_client_course($client, $course1->id);
        $this->assertCount(1, $DB->get_records('shezar_connect_client_courses'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_courses', array('clientid' => $client->id, 'courseid' => $course1->id)));

        util::add_client_course($client, $course2->id);
        $this->assertCount(2, $DB->get_records('shezar_connect_client_courses'));
        $this->assertTrue($DB->record_exists('shezar_connect_client_courses', array('clientid' => $client->id, 'courseid' => $course1->id)));
        $this->assertTrue($DB->record_exists('shezar_connect_client_courses', array('clientid' => $client->id, 'courseid' => $course2->id)));
    }

    public function test_validate_sso_request_token() {
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $this->assertTrue(util::validate_sso_request_token($client, sha1('xxxxx')));

        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'no way')));
        $this->assertFalse(util::validate_sso_request_token($client, sha1('yyyy')));
    }

    public function test_create_sso_session() {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        $this->setGuestUser();
        $session = util::create_sso_session($client);
        $this->assertNull($session);
        $this->assertCount(0, $DB->get_records('shezar_connect_sso_sessions'));

        $this->setCurrentTimeStart();
        $this->setUser($user);
        $session = util::create_sso_session($client);
        $this->assertCount(1, $DB->get_records('shezar_connect_sso_sessions'));
        $this->assertInstanceOf('stdClass', $session);
        $this->assertSame($client->id, $session->clientid);
        $this->assertSame($user->id, $session->userid);
        $this->assertSame('', $session->sid); // No session in CLI.
        $this->assertSame(40, strlen($session->ssotoken));
        $this->assertSame('0', $session->active);
        $this->assertTimeCurrent($session->timecreated);

        // User not member of the restriction cohort.
        $DB->delete_records('shezar_connect_sso_sessions');
        $DB->set_field('shezar_connect_clients', 'cohortid', $cohort->id, array('id' => $client->id));
        $client = $DB->get_record('shezar_connect_clients', array('id' => $client->id));

        $this->setUser($user);
        $session = util::create_sso_session($client);
        $this->assertNull($session);
        $this->assertCount(0, $DB->get_records('shezar_connect_sso_sessions'));

        // User is member of the restriction cohort.
        cohort_add_member($cohort->id, $user->id);
        $this->setUser($user);

        $this->setCurrentTimeStart();
        $session = util::create_sso_session($client);
        $this->assertInstanceOf('stdClass', $session);
        $this->assertSame($client->id, $session->clientid);
        $this->assertSame($user->id, $session->userid);
        $this->assertSame('', $session->sid); // No session in CLI.
        $this->assertSame(40, strlen($session->ssotoken));
        $this->assertTimeCurrent($session->timecreated);
        $this->assertCount(1, $DB->get_records('shezar_connect_sso_sessions'));
    }

    public function test_ignore_timeout_hook() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $CFG->enableconnectserver = '1';

        $user = $this->getDataGenerator()->create_user();
        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        $now = time();
        $this->setUser($user);
        $session = util::create_sso_session($client);
        $session->sid = sha1('grgrgrggr'); // Fake real session.
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('active' => '1'))));
        $result = util::ignore_timeout_hook($user, $session->sid, $now - 60 * 60 * 2, $now - 60 * 60);
        $this->assertTrue($result);

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('active' => '0'))));
        $result = util::ignore_timeout_hook($user, $session->sid, $now - 60 * 60 * 2, $now - 60 * 60);
        $this->assertFalse($result);

        jsend::set_phpunit_testdata(array(array('status' => 'error', 'message' => 'nononono')));
        $result = util::ignore_timeout_hook($user, $session->sid, $now - 60 * 60 * 2, $now - 60 * 60);
        $this->assertFalse($result);

        $CFG->enableconnectserver = '0';
        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array('active' => '1'))));
        $result = util::ignore_timeout_hook($user, $session->sid, $now - 60 * 60 * 2, $now - 60 * 60);
        $this->assertFalse($result);
    }

    public function test_terminate_sso_session() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $CFG->enableconnectserver = '1';

        $user = $this->getDataGenerator()->create_user();
        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        $this->setUser($user);
        $session = util::create_sso_session($client);
        $session->sid = sha1('grgrgrggr'); // Fake real session.
        $DB->set_field('shezar_connect_sso_sessions', 'sid', $session->sid, array('id' => $session->id));

        jsend::set_phpunit_testdata(array(array('status' => 'success', 'data' => array())));
        $this->assertTrue($DB->record_exists('shezar_connect_sso_sessions', array('id' => $session->id)));
        util::terminate_sso_session($client, $session);
        $this->assertFalse($DB->record_exists('shezar_connect_sso_sessions', array('id' => $session->id)));
    }

    public function test_login_page_info() {
        global $CFG, $SESSION;

        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');
        $client = $generator->create_client();

        $this->assertNull(util::login_page_info());

        $CFG->enableconnectserver = '1';
        $this->assertNull(util::login_page_info());

        $requesttoken = sha1('dssddsds');
        $SESSION->shezarconnectssostarted = array('clientidnumber' => $client->clientidnumber, 'requesttoken' => $requesttoken);
        $result = util::login_page_info();
        $this->assertContains('<div class="singlebutton">', $result);
        $this->assertContains('<form method="post" action="http://www.example.com/moodle/shezar/connect/sso_request.php">', $result);
    }

    public function test_warn_if_not_https() {
        global $CFG;
        $this->resetAfterTest();

        // No warning expected on HTTPS sites.
        $CFG->wwwroot = 'https://example.com/lms';
        $this->assertSame('', util::warn_if_not_https());

        // Some warning expected on https.
        $CFG->wwwroot = 'http://example.com/lms';
        $this->assertSame("!! For security reasons shezar Connect servers should be hosted via a secure protocol (https). !!\n", util::warn_if_not_https());
    }

    public function test_prepare_user_for_client() {
        global $CFG, $DB;

        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user(array('description' => 'test user'));
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $result = clone($user1);
        util::prepare_user_for_client($result);

        $this->assertObjectNotHasAttribute('secret', $result);
        $this->assertObjectNotHasAttribute('picture', $result);
        $this->assertObjectNotHasAttribute('pictures', $result);
        $this->assertNull($result->password);

        foreach ((array)$result as $k => $v) {
            if ($k === 'password') {
                continue;
            }
            if (property_exists($user1, $k)) {
                $this->assertSame($v, $user1->$k, "sync user property $k is not the same");
            }
        }

        // Test extra SSO data (such as user picture).
        $result = clone($user1);
        util::prepare_user_for_client($result, true);

        $this->assertObjectNotHasAttribute('secret', $result);
        $this->assertObjectHasAttribute('picture', $result);
        $this->assertSame(array(), $result->pictures);
        unset($result->pictures);
        $this->assertNull($result->password);

        foreach ((array)$result as $k => $v) {
            if ($k === 'password') {
                continue;
            }
            if (property_exists($user1, $k)) {
                $this->assertSame($v, $user1->$k, "sync user property $k is not the same");
            }
        }

        // Try password sync.

        set_config('syncpasswords', '1', 'shezar_connect');

        $result = clone($user1);
        util::prepare_user_for_client($result);

        $this->assertObjectNotHasAttribute('secret', $result);
        $this->assertSame($user1->password, $result->password);

        // Deleted user.
        delete_user($user1);
        $user1 = $DB->get_record('user', array('id' => $user1->id));

        $result = clone($user1);
        util::prepare_user_for_client($result);

        $this->assertObjectNotHasAttribute('secret', $result);
        $this->assertNull($result->password);
        $this->assertNull($result->description);
        $this->assertNull($result->descriptionformat);

        foreach ((array)$result as $k => $v) {
            if ($k === 'password' or $k === 'description' or $k === 'descriptionformat') {
                continue;
            }
            if (property_exists($user1, $k)) {
                $this->assertSame($v, $user1->$k, "sync user property $k is not the same");
            }
        }
    }

    public function test_prepare_user_for_client_icons() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/lib/gdlib.php");
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(array('description' => 'test user'));
        $usercontext = context_user::instance($user->id);
        $iconfile = "$CFG->dirroot/pix/u/user100.png";
        $this->assertFileExists($iconfile);
        $iid = process_new_icon($usercontext, 'user', 'icon', 0, $iconfile, true);
        $DB->set_field('user', 'picture', $iid, array('id' => $user->id));

        $user = $DB->get_record('user', array('id' => $user->id));
        util::prepare_user_for_client($user, true);
        $this->assertGreaterThan(0, (int)$user->picture);
        $this->assertCount(3, $user->pictures);
        $this->assertArrayHasKey('f1.png', $user->pictures);
        $this->assertArrayHasKey('f2.png', $user->pictures);
        $this->assertArrayHasKey('f3.png', $user->pictures);

        $user = $DB->get_record('user', array('id' => $user->id));
        util::prepare_user_for_client($user, false);
        $this->assertObjectNotHasAttribute('picture', $user);
        $this->assertObjectNotHasAttribute('pictures', $user);
    }
}
