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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @package shezar_customfield
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_shezar_customfield_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015021300) {
        xmldb_shezar_customfield_upgrade_clean_removed();

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2015021300, 'shezar_customfield');
    }

    // Delete all param records where the records were deleted from {prefix}_info_data tables
    if ($oldversion < 2016060800) {

        $tableprefixes = array('comp_type', 'course', 'dp_plan_evidence', 'facetoface_asset', 'facetoface_cancellation',
            'facetoface_room', 'facetoface_session', 'facetoface_sessioncancel', 'facetoface_signup', 'goal_type',
            'goal_user', 'org_type', 'pos_type', 'prog');
        foreach ($tableprefixes as $prefix) {
            $tblnamedata = $prefix . '_info_data';
            $tblnamedataparam = $prefix . '_info_data_param';
            if ($dbman->table_exists($tblnamedata) && $dbman->table_exists($tblnamedataparam)) {
                $sql = "DELETE FROM {{$tblnamedataparam}} WHERE dataid NOT IN (SELECT id FROM {{$tblnamedata}})";
                $DB->execute($sql);
            }
        }

        // Main savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016060800, 'shezar_customfield');
    }

    return true;
}

/**
 * Clean customfields data from removed courses and programs.
 * Made as additional function for testability.
 */
function xmldb_shezar_customfield_upgrade_clean_removed() {
    global $DB;
    $dbman = $DB->get_manager();
    if ($dbman->table_exists('course_info_data')) {
        // Remove customfields data for removed courses.
        $sql = "DELETE FROM {course_info_data} WHERE courseid NOT IN (SELECT id FROM {course})";
        $DB->execute($sql);

        $sqlparam = "DELETE FROM {course_info_data_param} WHERE dataid NOT IN (SELECT id FROM {course_info_data})";
        $DB->execute($sqlparam);
    }

    if ($dbman->table_exists('prog_info_data')) {
        // Remove customfields data for removed programs and certs.
        $sql = "DELETE FROM {prog_info_data} WHERE programid NOT IN (SELECT id FROM {prog})";
        $DB->execute($sql);

        $sqlparam = "DELETE FROM {prog_info_data_param} WHERE dataid NOT IN (SELECT id FROM {prog_info_data})";
        $DB->execute($sqlparam);
    }
}