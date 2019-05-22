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
 * @author Ciaran Irvine <ciaran.irvine@shezarlms.com>
 * @package shezar_core
 */

/*
 * This file is executed before any upgrade of shezar site.
 * This file is not executed during initial installation or upgrade from vanilla Moodle.
 *
 * Note that shezar 1.x and 2.2.x testes are in lib/setup.php, we can get here only from higher versions.
 */

defined('MOODLE_INTERNAL') || die();
global $OUTPUT, $DB, $CFG, $shezar;

require_once ("$CFG->dirroot/shezar/core/db/utils.php");

$dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.
$success = get_string('success');

// Check unique idnumbers in shezar tables before we start upgrade.
// Do not upgrade lang packs yet so that they can go back to previous version!
if ($CFG->version < 2013051402.00) { // Upgrade from 2.4.x or earlier.
    $duplicates = shezar_get_nonunique_idnumbers();
    if (!empty($duplicates)) {
        $duplicatestr = '';
        foreach ($duplicates as $duplicate) {
            $duplicatestr .= get_string('idnumberduplicates', 'shezar_core', $duplicate) . '<br/>';
        }
        throw new moodle_exception('shezaruniqueidnumbercheckfail', 'shezar_core', '', $duplicatestr);
    }
    echo $OUTPUT->notification(get_string('shezarupgradecheckduplicateidnumbers', 'shezar_core'), 'notifysuccess');
}

// Always update all language packs if we can, because they are used in shezar upgrade/install scripts.
shezar_upgrade_installed_languages();

// Migrate badge capabilities to Moodle core.
if ($CFG->version < 2013051402.00) { // Upgrade from 2.4.x or earlier.
    $DB->set_field_select('capabilities', 'component', 'moodle', "component = 'shezar_core' AND name LIKE 'moodle/badges:%'");
}

// Add custom shezar completion field to prevent fatal problems during upgrade.
if ($CFG->version < 2013111802.00) { // Upgrade from shezar 2.5.x or earlier.
    $table = new xmldb_table('course_completions');
    $field = new xmldb_field('invalidatecache', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'reaggregate');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}
