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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests util class.
 */
class shezar_connect_generator_testcase extends advanced_testcase {
    public function test_create_client() {
        $this->resetAfterTest();

        /** @var shezar_connect_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('shezar_connect');

        $this->setCurrentTimeStart();
        $client = $generator->create_client();
        $this->assertEquals(util::CLIENT_STATUS_OK, $client->status);
        $this->assertSame(40, strlen($client->clientidnumber));
        $this->assertSame(40, strlen($client->clientsecret));
        $this->assertStringStartsWith('Some client ', $client->clientname);
        $this->assertSame('https://www.example.com/shezar', $client->clienturl);
        $this->assertSame('shezarlms', $client->clienttype);
        $this->assertSame('', $client->clientcomment);
        $this->assertSame(null, $client->cohortid);
        $this->assertSame(40, strlen($client->serversecret));
        $this->assertSame('0', $client->addnewcohorts);
        $this->assertSame('0', $client->addnewcourses);
        $this->assertSame('1', $client->apiversion);
        $this->assertTimeCurrent($client->timecreated);
        $this->assertSame($client->timecreated, $client->timemodified);

        $cohort = $this->getDataGenerator()->create_cohort();

        $record = array(
            'clientname' => 'My name',
            'clienturl' => 'http://example.net',
            'clienttype' => 'shezarsocial',
            'cohortid' => (string)$cohort->id,
            'addnewcohorts' => '1',
            'addnewcourses' => '1',
        );
        $client2 = $generator->create_client($record);
        foreach ($record as $k => $v) {
            $this->assertSame($v, $client2->$k);
        }

        $record = array(
            'clientname' => 'My name',
            'clienturl' => 'http://example.net',
            'clienttype' => '',
            'cohortid' => (string)$cohort->id,
            'addnewcohorts' => '1',
            'addnewcourses' => '1',
        );
        $client3 = $generator->create_client($record);
        foreach ($record as $k => $v) {
            $this->assertSame($v, $client3->$k);
        }
    }
}
