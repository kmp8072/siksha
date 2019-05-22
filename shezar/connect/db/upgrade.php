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

defined('MOODLE_INTERNAL') || die;

/**
 * shezar Connect server plugin upgrade.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_shezar_connect_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015100201) {
        // Fix incorrect password sync setting.
        $sync = get_config('shezarconnect_connect', 'syncpasswords');
        if ($sync !== false) {
            unset_config('syncpasswords', 'shezarconnect_connect');
            set_config('syncpasswords', $sync, 'shezar_connect');
        }

        upgrade_plugin_savepoint(true, 2015100201, 'shezar', 'connect');
    }

    if ($oldversion < 2015100202) {
        // Cleanup after wrong upgrade step.
        unset_config('version', 'connect_shezar');
        upgrade_plugin_savepoint(true, 2015100202, 'shezar', 'connect');
    }

    if ($oldversion < 2015100203) {

        // Define field active to be added to shezar_connect_sso_sessions.
        $table = new xmldb_table('shezar_connect_sso_sessions');
        $field = new xmldb_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'ssotoken');

        // Conditionally launch add field active.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2015100203, 'shezar', 'connect');
    }

    return true;
}
