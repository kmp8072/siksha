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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar_core
 */

defined('MOODLE_INTERNAL') || die();

class shezar_core_ddl_changetype_testcase extends advanced_testcase {
    public function test_ddl_change_type() {
        global $DB;

        $dbman = $DB->get_manager();
        $this->resetAfterTest(true);
        $this->preventResetByRollback();

        // Create the dummy table.
        $table = new xmldb_table('changetype');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('changeme', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        // Insert some dummy data.
        $todb = new stdClass();
        $todb->changeme = "1234567890";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = 1237894560;
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "0";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "   0321654987   ";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "-1";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "-123456789";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "123abc789";
        $DB->insert_record('changetype', $todb);
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "abcdefghij";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "abcdefghijklmnopqrstuvwxyz";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "1234567891234567891234569789";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "123 456 789 0";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "";
        $DB->insert_record('changetype', $todb);
        $todb->changeme = "    ";

        $this->assertEquals(13, $DB->count_records('changetype'));

        // Run the checks from the upgrade.
        $records = $DB->get_recordset('changetype');
        foreach ($records as $record) {
            if (!preg_match('/^[0-9]{1,10}$/', $record->changeme)) {
                $DB->delete_records('changetype', array('id' => $record->id));
                continue;
            }
        }
        $records->close();

        // Only the first three should pass the checks.
        $this->assertEquals(3, $DB->count_records('changetype'));

        // Run the field change.
        $field_integer = new xmldb_field('changeme', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        $dbman->change_field_type($table, $field_integer);

        // Check the results.
        $this->assertEquals(3, $DB->count_records('changetype'));
        $records = $DB->get_records('changetype');
        $this->assertEquals(1234567890, $records[1]->changeme);
        $this->assertEquals(1237894560, $records[2]->changeme);
        $this->assertEquals(0, $records[3]->changeme);
    }

}
