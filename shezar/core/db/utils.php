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
 * @author Simon Coggins <simon.coggins@shezarlms.com>
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @author Ciaran Irvine <ciaran.irvine@shezarlms.com>
 * @package shezar
 * @subpackage shezar_core
 */

/**
* shezar Module upgrade savepoint, marks end of shezar module upgrade blocks
* It stores module version, resets upgrade timeout
*
* @param bool $result false if upgrade step failed, true if completed
* @param string|float $version plugin version
* @param string $modname full component name of module
* @return void
*/
function shezar_upgrade_mod_savepoint($result, $version, $modname) {
    list($type, $plugin) = explode('_', $modname, 2);
    upgrade_plugin_savepoint($result, $version, $type, $plugin);
}

/**
* shezar_set_charfield_nullable, for fixing 1.9 char fields where old definition was ISNULL=true and DEFAULT=""
*
* @global object $DB
* @param string $table the table name
* @param string $field the field name
* @param string $previous the field immediately previous to the char field in the table definition
* @param string $length length of the char field NB: pass as a string, not an int! 'small','medium','big' for TEXT fields
* @param bool or null $notnull null or false if CAN be null, XMLDB_NOTNULL if CANNOT be null
* * @param string or null $default either remove the default (null) or force a sane non-empty default
* @param array $indexes array of xmldb_index objects for all indexes on tables that contain the char field
* @return void
*/
function shezar_fix_nullable_charfield($table, $field, $previous, $length='255', $notnull=null, $default=null, $indexes=array(), $fieldtype=XMLDB_TYPE_CHAR) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    //sanity check
    if ($notnull == XMLDB_NOTNULL && empty($default)) {
        throw new upgrade_exception("$table $field set as NOT NULL with an empty default value!", '1.1 to 2.2 upgrade');
    }
    $xtable = new xmldb_table($table);
    if (count($indexes) > 0) {
        foreach ($indexes as $index) {
            $dbman->drop_index($xtable, $index);
        }
    }

    $xfield = new xmldb_field($field);
    $xfield->set_attributes($fieldtype, $length, null, $notnull, null, $default, $previous);
    $dbman->change_field_notnull($xtable, $xfield);
    $dbman->change_field_default($xtable, $xfield);

    if (count($indexes) > 0) {
        foreach ($indexes as $index) {
            $dbman->add_index($xtable, $index);
        }
    }
}
/**
 * Function for fixing records in the database caused by a bug that
 * introduced duplicates
 *
 * @param string $tablename Name of the table to fix (without prefix)
 * @param string $where_sql SQL snippet restricting which records are fixed
 *
 * @return boolean True if the operation completed successfully or there
 *                 was nothing to do
 */
function shezar_data_object_duplicate_fix($tablename, $where_sql) {
    global $DB, $OUTPUT;

    // Check for duplicates
    $count_sql = "
        SELECT
            COUNT(*)
        FROM
            {$tablename}
        WHERE
            id NOT IN
            (
                {$where_sql}
            )
    ";

    // If any duplicates, keep correct version of record
    if (!$count = $DB->count_records_sql($count_sql)) {
        return true;
    }

    $a = new stdClass();
    $a->count = $count;
    $a->tablename = $tablename;
    echo $OUTPUT->notification(get_string('error:duplicaterecordsfound', 'shezar_core', $a));

    $select_sql = "
        SELECT
            *
        FROM
            {$tablename}
        WHERE
            id NOT IN
            (
                {$where_sql}
            )
    ";

    // Select rows to be deleted, and dump their contents to the error log
    $duplicates = $DB->get_records_sql($select_sql);
    $ids = array();
    foreach ($duplicates as $dup) {
        error_log(get_string('error:duplicaterecordsdeleted', 'shezar_core', $tablename) . var_export((array)$dup, true));
        $ids[] = $dup->id;
    }

    // Delete duplicate rows
    list($usql, $params) = $DB->get_in_or_equal($ids);
    $delete_sql = "
        DELETE FROM
            {$tablename}
        WHERE
            WHERE id $usql";

    if (!$DB->execute($delete_sql, $params)) {
        return false;
    }

    return true;
}

/**
 * Re-add changes to course completion for shezar
 *
 * Although these exist in lib/db/upgrade.php, anyone upgrading from Moodle 2.2.2 or above
 * would already have a higher version number so we need to apply them again:
 *
 * 1. when shezar first is installed (to fix for anyone upgrading from 2.2.2+)
 * 2. in a shezar core upgrade (to fix for anyone who has already upgraded from 2.2.2+)
 *
 * These changes will only be applied if they haven't been run previously so it's okay
 * to call this function multiple times
 */
function shezar_readd_course_completion_changes() {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    // Define index useridcourse (unique) to be added to course_completions
    $table = new xmldb_table('course_completions');
    $index = new xmldb_index('useridcourse', XMLDB_INDEX_UNIQUE, array('userid', 'course'));

    // Conditionally launch add index useridcourse
    if (!$dbman->index_exists($table, $index)) {
        // Clean up all instances of duplicate records
        // Add indexes to prevent new duplicates
        upgrade_course_completion_remove_duplicates(
            'course_completions',
            array('userid', 'course'),
            array('timecompleted', 'timestarted', 'timeenrolled')
        );

        $dbman->add_index($table, $index);
    }

    // Define index useridcoursecriteraid (unique) to be added to course_completion_crit_compl
    $table = new xmldb_table('course_completion_crit_compl');
    $index = new xmldb_index('useridcoursecriteraid', XMLDB_INDEX_UNIQUE, array('userid', 'course', 'criteriaid'));

    // Conditionally launch add index useridcoursecriteraid
    if (!$dbman->index_exists($table, $index)) {
        upgrade_course_completion_remove_duplicates(
            'course_completion_crit_compl',
            array('userid', 'course', 'criteriaid'),
            array('timecompleted')
        );

        $dbman->add_index($table, $index);
    }

    // Define index coursecriteratype (unique) to be added to course_completion_aggr_methd
    $table = new xmldb_table('course_completion_aggr_methd');
    $index = new xmldb_index('coursecriteriatype', XMLDB_INDEX_UNIQUE, array('course', 'criteriatype'));

    // Conditionally launch add index coursecriteratype
    if (!$dbman->index_exists($table, $index)) {
        upgrade_course_completion_remove_duplicates(
            'course_completion_aggr_methd',
            array('course', 'criteriatype')
        );

        $dbman->add_index($table, $index);
    }

    require_once("{$CFG->dirroot}/completion/completion_completion.php");

    /// Define field status to be added to course_completions
    $table = new xmldb_table('course_completions');
    $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0, 'reaggregate');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);

        // Get all records
        $rs = $DB->get_recordset_sql('SELECT * FROM {course_completions}');
        foreach ($rs as $record) {
            // Update status column
            $status = completion_completion::get_status($record);
            if ($status) {
                $status = constant('COMPLETION_STATUS_'.strtoupper($status));
            }

            $record->status = $status;

            if (!$DB->update_record('course_completions', $record)) {
                break;
            }
        }
        $rs->close();
    }

}

/**
 * Get tables and records which have non-unique idnumbers.
 * Used in upgrade script.
 *
 * @return object|false Array (table => list of idnumbers) or false if no duplicates found
 */
function shezar_get_nonunique_idnumbers() {
    global $DB;

    $tables = array('user', 'comp', 'comp_framework', 'comp_scale_values', 'comp_type', 'org', 'org_framework', 'org_type',
                    'dp_priority_scale_value', 'dp_objective_scale_value', 'pos', 'pos_framework', 'pos_type', 'prog');

    $records = array();
    foreach ($tables as $table) {
        if ($DB->get_manager()->table_exists($table)) {
            $sql = "SELECT
                        idnumber
                    FROM
                        {{$table}}
                    WHERE " . $DB->sql_isnotempty($table, 'idnumber', true, false) . "
                    GROUP BY
                        idnumber
                    HAVING
                        COUNT(*) > 1";
            if ($fields = $DB->get_fieldset_sql($sql)) {
                $record = new stdClass();
                $record->table = $table;
                $record->idnumbers = implode(', ', $fields);
                $records[] = $record;
            }
        }
    }

    if (!empty($records)) {
        return $records;
    }
    return false;
}

/**
 * Get cohort rules that were broken by expansion of options in shezar 2.4.8.
 *
 * @return  array List of rules.
 */
function shezar_get_text_broken_rules() {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/shezar/cohort/lib.php');

    $userrulenames = "('idnumber', 'username', 'email', 'firstname', 'lastname', 'city', 'institution', 'department')";

    // Create sql snippet for rules based on users.
    $sqluserrules = "cr.ruletype = 'user' AND cr.name IN $userrulenames";

    // Create sql snippet for rules based on positions.
    $sqlposrules = "cr.ruletype = 'pos' AND cr.name IN ('idnumber', 'name')";

    // Create sql snippet for rules based on organisations.
    $sqlorgrules = "cr.ruletype = 'org' AND cr.name = 'idnumber'";

    // Create sql snippet for rules based on customfields.
    $sqlcustomrules = "cr.ruletype = :usercustomfield";

    // Find all active and draft rules in dynamic cohorts that could be affected by the expansion of rule options change.
    $sql = "SELECT crp.id, crp.ruleid, crp.name, crp.value, crp.timecreated, crp.timemodified, cr.ruletype, cr.name as rulename,
                   c.id as cohortid, c.name as cohortname, c.activecollectionid, crc.id as rulecollectionid
            FROM {cohort} c
            INNER JOIN {cohort_rule_collections} crc ON crc.id IN (c.activecollectionid, c.draftcollectionid)
            INNER JOIN {cohort_rulesets} crs ON crs.rulecollectionid = crc.id
            INNER JOIN {cohort_rules} cr ON cr.rulesetid = crs.id
            INNER JOIN {cohort_rule_params} crp ON cr.id = crp.ruleid
            WHERE c.cohorttype = :cohorttype
              AND crp.name = :equal
              AND ($sqluserrules
               OR $sqlposrules
               OR $sqlorgrules
               OR $sqlcustomrules)
            ORDER BY c.id, crp.id";

    $params = array('cohorttype' => cohort::TYPE_DYNAMIC, 'equal' => 'equal', 'usercustomfield' => 'usercustomfields');

    $rules = $DB->get_records_sql($sql, $params);

    // We might still have some rules that don't need to be fixed, check each rule is a text rule
    // before including it.
    $brokenrules = array();
    foreach ($rules as $rule) {
        // This type of rule doesn't need to be fixed (only text rules are affected).
        $ruledef = cohort_rules_get_rule_definition($rule->ruletype, $rule->rulename);
        if (get_class($ruledef->ui) === 'cohort_rule_ui_text') {
            $brokenrules[] = $rule;
        }
    }

    return $brokenrules;
}

/**
 * Remap the old rules to the new ones (expansion of option in 2.4.8)
 *
 * @param array $rulestofix Array of rules to fix as generated by {@link shezar_get_text_broken_rules()}.
 *
 * @return  void
 */
function shezar_cohort_remap_rules($rulestofix) {
    global $DB, $USER;

    foreach ($rulestofix as $ruleparam) {
        if ($ruleparam->value == COHORT_RULES_OP_IN_NOTEQUAL) {
            $ruleparam->value = COHORT_RULES_OP_IN_NOTEQUALTO;
        } else if ($ruleparam->value == COHORT_RULES_OP_IN_EQUAL) {
            $ruleparam->value = COHORT_RULES_OP_IN_ISEQUALTO;
        }

        // Remap rules.
        $todb = new stdClass();
        $todb->id = $ruleparam->id;
        $todb->value = $ruleparam->value;
        $todb->timemodified = time();
        $todb->modifierid = $USER->id;
        $DB->update_record('cohort_rule_params', $todb);
    }
}

/**
 * Determines if a broken cohort rule can be fixed automatically.
 *
 * @param object $brokenrule Rule as returned by {@link shezar_get_text_broken_rules()}.
 * @return bool True if the rule can be fixed automatically, false otherwise.
 */
function shezar_cohort_is_rule_fixable($brokenrule) {

    // They upgraded from a version before 2.4.8, we know the rules are wrong so we can fix them.
    $previousversion = get_config('shezar_core', 'previous_version');
    if (!empty($previousversion) && version_compare('2.4.8', $previousversion, '>')) {
        return true;
    }

    // This timestamp is just before 2.4.8 release. Any rules where lastmodified is before this date *must*
    // be fixed.
    $bugreleasedate = 1377046800;
    if ($brokenrule->timemodified < $bugreleasedate) {
        return true;
    }

    // Otherwise we can't be sure if this rule needs fixing or not (since we don't know when they upgraded).
    return false;
}

/**
 * Function call before upgrade.
 *
 * This is executed before each main upgrade and before plugins start upgrading.
 *
 * @return void.
 */
function shezar_preupgrade() {
    global $CFG, $DB, $OUTPUT; // These are used from the pre upgrade scripts, do not remove!

    static $executed = false;
    if ($executed) {
        return;
    }
    $executed = true;

    if (during_initial_install()) {
        // This is a fresh new shezar install running right now, nothing to do here!
        return;
    }

    $shezarinfo = shezar_version_info();

    print_upgrade_part_start('', false, true);

    if (empty($CFG->shezar_release)) {
        // This is a migration from vanilla Moodle site to shezar.
        $upgradefile = "{$CFG->dirroot}/shezar/core/db/pre_moodle_upgrade.php";
        core_php_time_limit::raise(0);
        require($upgradefile);

    } else {
        // This is an upgrade of existing shezar site.
        $upgradefile = "{$CFG->dirroot}/shezar/core/db/pre_shezar_upgrade.php";
        core_php_time_limit::raise(0);
        require($upgradefile);

        // NOTE: 'previous_version' is a dangerous hack - watch out for interrupted upgrades and + in version numbers,
        //       always use $oldversion in shezar/core/upgrade.php instead if possible!!!
        set_config('previous_version', $shezarinfo->existingshezarversion, 'shezar_core');
    }

    print_upgrade_part_end('', false, true);
}

/**
 * Function to remove duplicates records.
 * Use ONLY in upgrades.
 *
 * @param string $table Table where the duplicate records exist
 * @param array $uniques Array of fields that need to be unique
 * @param string $sql_order (Optional) Sort order for the query to determine the record to keep
 * @param string $callback (Optional) Function that will manage what to do with the duplicate records
 */
function shezar_upgrade_delete_duplicate_records($table, array $uniques, $sql_order = '', $callback = '') {
    global $DB;

    // Check that the uniques array is not empty.
    if (empty($uniques)) {
        return;
    }

    // Find duplicates.
    $sql_cols  = implode(', ', $uniques);

    $select = "SELECT {$sql_cols}";
    $conditions = "FROM {{$table}} GROUP BY {$sql_cols} HAVING (count(id) > 1)";
    $duplicates = $DB->get_recordset_sql("{$select} {$conditions}", array());

    // Get total of duplicates.
    $total = $DB->get_field_sql("SELECT COALESCE(SUM(dup.total), 0) FROM (SELECT COUNT(*) -1 AS total {$conditions}) dup");

    // There are not duplicates.
    if ($total == 0) {
        return;
    }

    // Create progress bar to notify users.
    $pbar = new progress_bar('Deleting duplicate records in table' . $table, 500, true);

    $transaction = $DB->start_delegated_transaction();

    // Loop through duplicates.
    $i = 0;
    foreach ($duplicates as $duplicate) {
        $pointer = 0;
        // Generate SQL for finding records with these duplicate uniques.
        $sql_select = implode(' = ? AND ', $uniques) . ' = ?'; // Builds "fieldname = ? AND fieldname = ?".
        $uniq_values = array();
        foreach ($uniques as $u) {
            $uniq_values[] = $duplicate->$u;
        }

        // Get records with these duplicate unique values.
        $records = $DB->get_records_select(
            $table,
            $sql_select,
            $uniq_values,
            $sql_order
        );

        // Loop through every duplicate record.
        foreach ($records as $record) {
            $pointer++;
            if ($pointer !== 1) { // We keep 1st record but delete all others.
                $i++;
                // If there is a callback, then call function.
                if (!empty($callback)) {
                    call_user_func($callback, $record);
                }
                $DB->delete_records($table, array('id' => $record->id));
                $pbar->update($i, $total, "Deleting duplicate records in table $table - $i/$total.");
            }
        }
    }
    $pbar->update($total, $total, "Deleting duplicate records in table $table - done!");
    $transaction->allow_commit();
}
