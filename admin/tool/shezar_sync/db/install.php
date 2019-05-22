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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage shezar_sync
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_tool_shezar_sync_install() {
    global $CFG, $DB;

    ///
    /// Add shezarsync flag to element tables
    ///
    $dbman = $DB->get_manager();

    // user
    $table = new xmldb_table('user');
    $field = new xmldb_field('shezarsync');
    $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field)) {
        // Launch add field shezarsync
        $dbman->add_field($table, $field);
    }
    $index = new xmldb_index('shezarsync');
    $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('shezarsync'));
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // org
    $table = new xmldb_table('org');
    $field = new xmldb_field('shezarsync');
    $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field)) {
        // Launch add field shezarsync
        $dbman->add_field($table, $field);
    }
    $index = new xmldb_index('shezarsync');
    $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('shezarsync'));
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // pos
    $table = new xmldb_table('pos');
    $field = new xmldb_field('shezarsync');
    $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field)) {
        // Launch add field shezarsync
        $dbman->add_field($table, $field);
    }
    $index = new xmldb_index('shezarsync');
    $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('shezarsync'));
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    return true;
}
