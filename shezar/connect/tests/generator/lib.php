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
 * @category test
 */

use \shezar_connect\util;

defined('MOODLE_INTERNAL') || die();

/**
 * shezar Connect generator.
 *
 * @package shezar_connect
 * @category test
 */
class shezar_connect_generator extends component_generator_base {
    protected $clientcount = 0;

    /**
     * To be called from data reset code only, do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
        $this->clientcount = 0;
    }

    /**
     * Creates TC clients.
     *
     * @param stdClass array $record
     * @return stdClass client record
     */
    public function create_client($record = null) {
        global $DB;

        $record = (object)(array)$record;

        $this->clientcount++;
        $i = $this->clientcount;

        $client = new \stdClass();
        $client->status         = util::CLIENT_STATUS_OK;
        $client->clientidnumber = util::create_unique_hash('shezar_connect_clients', 'clientidnumber');
        $client->clientsecret   = util::create_unique_hash('shezar_connect_clients', 'clientsecret');
        $client->clientname     = empty($record->clientname) ? 'Some client ' . $i : $record->clientname;
        $client->clienturl      = empty($record->clienturl) ? 'https://www.example.com/shezar' : rtrim($record->clienturl, '/');
        $client->clienttype     = !isset($record->clienttype) ? 'shezarlms' : $record->clienttype;
        $client->clientcomment  = empty($record->clientcomment) ? '' : $record->clientcomment;
        $client->cohortid       = empty($record->cohortid) ? null : $record->cohortid;
        $client->serversecret   = util::create_unique_hash('shezar_connect_clients', 'serversecret');
        $client->addnewcohorts  = !empty($record->addnewcohorts);
        $client->addnewcourses  = !empty($record->addnewcourses);
        $client->apiversion     = 1;
        $client->timecreated    = time();
        $client->timemodified   = $client->timecreated;
        $id = $DB->insert_record('shezar_connect_clients', $client);

        return $DB->get_record('shezar_connect_clients', array('id' => $id));
    }
}
