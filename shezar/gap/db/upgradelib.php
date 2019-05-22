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

/**
 * Move all existing aspirational positions to aspirational_gap table
 */
function shezar_gap_install_aspirational_pos() {
    global $DB;
    $dbman = $DB->get_manager();

    // Define table gap_aspirational to be created.
    $table = new xmldb_table('gap_aspirational');

    // Adding fields to table gap_aspirational.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('positionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
    $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table gap_aspirational.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('gapasp_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
    $table->add_key('gapasp_pos_fk', XMLDB_KEY_FOREIGN, array('positionid'), 'pos', array('id'));

    // Conditionally launch create table for gap_aspirational.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Now add in pos_assignment data.
    $posassignmenttable = new xmldb_table('pos_assignment');
    if (!$dbman->table_exists($posassignmenttable)) {
        // Nothing to do here.
        return;
    }

    // Move the data from pos_assignment to gap_aspirational.
    // Magic 3 => POSITION_TYPE_ASPIRATIONAL (removed).
    $params = array('type' => 3);
    $sql = "INSERT INTO {gap_aspirational} (userid, positionid, usermodified, timecreated, timemodified)
                (SELECT pa.userid, pa.positionid, pa.usermodified, pa.timecreated, pa.timemodified
                   FROM {pos_assignment} pa
                  WHERE pa.type = :type AND pa.positionid IS NOT NULL)";
    $DB->execute($sql, $params);
    $sql = "DELETE FROM {pos_assignment}
                  WHERE type = :type";
    $DB->execute($sql, $params);

    // Now remove it from settings.
    $posstring = get_config('shezar_hierarchy', 'positionsenabled');
    if (!empty( $posstring )) {
        $enabledpositions = explode (',', $posstring);
        $key = array_search (3, $enabledpositions);
        if ($key !== false) {
            unset ( $enabledpositions [$key] );
            set_config('positionsenabled', implode(',', $enabledpositions), 'shezar_hierarchy');
        }
    }
}
