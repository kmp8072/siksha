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
 * @package    mod_feedback
 * @author     Petr Skoda <petr.skoda@shezarlms.com>
 */

/**
 * Function for shezar specific DB changes to core Moodle plugins.
 *
 * Put code here rather than in db/upgrade.php if you need to change core
 * Moodle database schema for shezar-specific changes.
 *
 * This is executed during EVERY upgrade. Make sure your code can be
 * re-executed EVERY upgrade without problems.
 *
 * You need to increment the upstream plugin version by .01 to get
 * this code executed!
 *
 * Do not use savepoints in this code!
 *
 * @param string $version the plugin version
 */
function xmldb_feedback_shezar_postupgrade($version) {
    global $DB;

    $dbman = $DB->get_manager();

    // Define table feedback_completed_history to be created.
    $table = new xmldb_table('feedback_completed_history');

    // Adding fields to table feedback_completed_history
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('feedback', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('random_response', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('anonymous_response', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('timearchived', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('idarchived', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table feedback_completed_history.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('feedback', XMLDB_KEY_FOREIGN, array('feedback'), 'feedback', array('id'));
    $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('user'));

    // Conditionally launch create table for feedback_completed_history.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // Define table feedback_value_history to be created.
    $table = new xmldb_table('feedback_value_history');

    // Adding fields to table feedback_value_history.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('item', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('completed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('tmp_completed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
    $table->add_field('timearchived', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('idarchived', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

    // Adding keys to table feedback_value_history.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('item', XMLDB_KEY_FOREIGN, array('item'), 'feedback_item', array('id'));
    $table->add_key('completed', XMLDB_KEY_FOREIGN, array('completed'), 'feedback_completed_history', array('id'));

    // Conditionally launch create table for feedback_value_history.
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    /// Undo shezar grade support in feedback from T-9604, not used any more.
    $table = new xmldb_table('feedback');
    $field = new xmldb_field('grade');
    $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, null);
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
}
