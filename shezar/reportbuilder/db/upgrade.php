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
 * @package shezar
 * @subpackage reportbuilder
 */

require_once($CFG->dirroot.'/shezar/core/db/utils.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
require_once($CFG->dirroot . '/shezar/reportbuilder/db/upgradelib.php');

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_shezar_reportbuilder_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2012071300) {
        // handle renaming of assignment field: description to intro
        foreach (array('columns', 'filters') as $table) {
            $sql = "SELECT rbt.id FROM {report_builder_{$table}} rbt
                JOIN {report_builder} rb
                ON rbt.reportid = rb.id
                WHERE
                (rbt.type = ? AND rbt.value = ? AND rb.source = ?) OR
                (rbt.type = ? AND rbt.value = ? AND rb.source = ?)";
            $items = $DB->get_fieldset_sql($sql, array(
                'assignment', 'description', 'assignment',
                'base', 'description', 'assignmentsummary'));

            if (!empty($items)) {
                list($insql, $inparams) = $DB->get_in_or_equal($items);
                $sql = "UPDATE {report_builder_{$table}} SET value = ? WHERE id {$insql}";
                $params = array_merge(array('intro'), $inparams);
                $DB->execute($sql, $params);
            }
        }
        shezar_upgrade_mod_savepoint(true, 2012071300, 'shezar_reportbuilder');
    }

    if ($oldversion < 2012071900) {
        require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');
        // rename the aggregated user columns/filters to avoid clashing with standard user fields
        reportbuilder_rename_data('columns', 'course_completion_by_org', 'user', 'fullname', 'user', 'allparticipants');
        reportbuilder_rename_data('filters', 'course_completion_by_org', 'user', 'fullname', 'user', 'allparticipants');
        shezar_upgrade_mod_savepoint(true, 2012071900, 'shezar_reportbuilder');
    }

    if ($oldversion < 2012073100) {
        // set global setting for financial year
        // default: July, 1
        set_config('financialyear', '0107', 'reportbuilder');
        shezar_upgrade_mod_savepoint(true, 2012073100, 'shezar_reportbuilder');
    }

    if ($oldversion < 2012081000) {
        // need to migrate saved search data from the database
        // to remove extraneous array that is no longer used
        $searches = $DB->get_recordset('report_builder_saved', null, '', 'id, search');
        foreach ($searches as $search) {
            $todb = new stdClass();
            $todb->id = $search->id;
            $currentfilters = unserialize($search->search);
            $newfilters = array();
            foreach ($currentfilters as $key => $filter) {
                // if the filter contains an array with only the [0] key set
                // assume it is no longer needed and remove it
                $newfilters[$key] = (isset($filter[0]) && count($filter) == 1) ? $filter[0] : $filter;
            }
            $todb->search = serialize($newfilters);
            $DB->update_record('report_builder_saved', $todb);
        }
        $searches->close();
        shezar_upgrade_mod_savepoint(true, 2012081000, 'shezar_reportbuilder');
    }

    if ($oldversion < 2012112300) {
        // Convert saved searches with status to the new status field
        $filter = 'course_completion-status';

        $like_sql = $DB->sql_like('rs.search', '?');

        $sql = "SELECT rs.id, rs.search
                FROM {report_builder_saved} AS rs
                JOIN {report_builder} AS rb ON rb.id = rs.reportid
                WHERE rb.source = ?
                AND {$like_sql}";

        $params = array('course_completion', '%' . $DB->sql_like_escape($filter) . '%');

        $searches = $DB->get_records_sql($sql, $params);

        require_once($CFG->dirroot . '/completion/completion_completion.php');

        foreach ($searches as $search) {
            $todb = new stdClass();
            $todb->id = $search->id;
            $data = unserialize($search->search);

            if (isset($data[$filter])) {
                $options = $data[$filter];
                if (isset($options['operator']) && isset($options['value']) && is_int($options['operator']) && is_string($options['value'])) {
                    $operator = $options['operator'];
                    $value = $options['value'];
                    if (($operator == 1 && $value == '0') || ($operator == 2 && $value == '1')) {
                        // Completion Status is equal to "Not completed" or
                        // Completion Status isn't equal to "Completed"
                        $options['value'] = array(
                            COMPLETION_STATUS_NOTYETSTARTED => "1",
                            COMPLETION_STATUS_INPROGRESS => "1",
                            COMPLETION_STATUS_COMPLETE => "0",
                            COMPLETION_STATUS_COMPLETEVIARPL => "0" );
                    } else if (($operator == 1 && $value == '1') || ($operator == 2 && $value == '0')) {
                        // Completion Status is equal to "Completed" or
                        // Completion Status isn't equal to "Not completed"
                        $options['value'] = array(
                            COMPLETION_STATUS_NOTYETSTARTED => "0",
                            COMPLETION_STATUS_INPROGRESS => "0",
                            COMPLETION_STATUS_COMPLETE => "1",
                            COMPLETION_STATUS_COMPLETEVIARPL => "1" );
                    } else {
                        // not the expected data so leave the data alone
                        continue;
                    }
                    // Set the operator to any of the following
                    $options['operator'] = 1;
                    $data[$filter] = $options;
                    $todb->search = serialize($data);
                    $DB->update_record('report_builder_saved', $todb);
                }
            }
        }
        shezar_upgrade_mod_savepoint(true, 2012112300, 'shezar_reportbuilder');
    }

    if ($oldversion < 2013021100) {
        $table = new xmldb_table('report_builder');
        $field1 = new xmldb_field('cache', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'hidden');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }

        $settingstable = new xmldb_table('report_builder_settings');
        $fieldcache = new xmldb_field('cachedvalue', XMLDB_TYPE_CHAR, '255', null, null, null, 0, 'value');
        if (!$dbman->field_exists($settingstable, $fieldcache)) {
            $dbman->add_field($settingstable, $fieldcache);
        }

        $tablecache = new xmldb_table('report_builder_cache');

        $tablecache->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tablecache->add_field('reportid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tablecache->add_field('cachetable', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $tablecache->add_field('frequency', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tablecache->add_field('schedule', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $tablecache->add_field('lastreport', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $tablecache->add_field('nextreport', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $tablecache->add_field('config', XMLDB_TYPE_TEXT, '', null, null, null, null);
        $tablecache->add_field('changed', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $tablecache->add_field('genstart', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $tablecache->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $tablecache->add_key('reportid', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', array('id'));

        $tablecache->add_index('nextreport', XMLDB_INDEX_NOTUNIQUE, array('nextreport'));

        if (!$dbman->table_exists('report_builder_cache')) {
            $dbman->create_table($tablecache);
        }

        shezar_upgrade_mod_savepoint(true, 2013021100, 'shezar_reportbuilder');
    }

    if ($oldversion < 2013032700) {
        //add new column to check for pre-filtering
        $table = new xmldb_table('report_builder');
        $field = new xmldb_field('initialdisplay');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0, 'embedded');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        shezar_upgrade_mod_savepoint(true, 2013032700, 'shezar_reportbuilder');
    }

    if ($oldversion < 2013061000) {
        $table = new xmldb_table('report_builder_schedule');
        $field = new xmldb_field('exporttofilesystem', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'frequency');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        set_config('exporttofilesystem', 0, 'reportbuilder');
        shezar_upgrade_mod_savepoint(true, 2013061000, 'shezar_reportbuilder');
    }

    if ($oldversion < 2013092400) {
        $table = new xmldb_table('report_builder_filters');
        $namefield = new xmldb_field('filtername', XMLDB_TYPE_CHAR, '255');
        $customnamefield = new xmldb_field('customname', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $namefield)) {
            $dbman->add_field($table, $namefield);
        }
        if (!$dbman->field_exists($table, $customnamefield)) {
            $dbman->add_field($table, $customnamefield);
        }
        // Add pdf to the default export options.
        $currentexportoptions = get_config('reportbuilder', 'exportoptions');
        $currentexportoptions = is_null($currentexportoptions) ? 0 : $currentexportoptions;
        $newexportoptions = ($currentexportoptions | 16 | 32); // PDF landscape and portrait.
        set_config('exportoptions', $newexportoptions, 'reportbuilder');
        shezar_upgrade_mod_savepoint(true, 2013092400, 'shezar_reportbuilder');
    }

    if ($oldversion < 2013103000) {
        // Adding foreign keys.
        $tables = array(
            'report_builder_columns' => array(
                new xmldb_key('repobuilcolu_rep_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', 'id')),

            'report_builder_filters' => array(
                new xmldb_key('repobuilfilt_rep_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', 'id')),

            'report_builder_settings' => array(
                new xmldb_key('repobuilsett_rep_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', 'id')),

            'report_builder_saved' => array(
                new xmldb_key('repobuilsave_rep_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', 'id'),
                new xmldb_key('repobuilsave_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id')),

            'report_builder_group_assign' => array(
                new xmldb_key('repobuilgrouassi_gro_fk', XMLDB_KEY_FOREIGN, array('groupid'), 'report_builder_group', 'id')),

            'report_builder_preproc_track' => array(
                new xmldb_key('repobuilpreptrac_gro_fk', XMLDB_KEY_FOREIGN, array('groupid'), 'report_builder_group', 'id')),

            'report_builder_schedule' => array(
                new xmldb_key('repobuilsche_rep_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', 'id'),
                new xmldb_key('repobuilsche_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id'),
                new xmldb_key('repobuilsche_sav_fk', XMLDB_KEY_FOREIGN, array('savedsearchid'), 'report_builder_saved', 'id')));

        foreach ($tables as $tablename => $keys) {
            $table = new xmldb_table($tablename);
            foreach ($keys as $key) {
                $dbman->add_key($table, $key);
            }
        }

        // Report builder savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2013103000, 'shezar_reportbuilder');
    }

    if ($oldversion < 2013121000) {
        require_once($CFG->dirroot . '/shezar/reportbuilder/lib.php');

        // Rename any existing records for the timecompleted column/filter in dp_certifications.
        reportbuilder_rename_data('columns', 'dp_certification', 'prog_completion', 'timecompleted', 'certif_completion', 'timecompleted');
        reportbuilder_rename_data('filters', 'dp_certification', 'prog_completion', 'timecompleted', 'certif_completion', 'timecompleted');

        // Report builder savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2013121000, 'shezar_reportbuilder');
    }

    if ($oldversion < 2014012400) {
        // Changing length of field from 255 to 1024 to match length of hierarchy custom field names.
        $table = new xmldb_table('report_builder_columns');
        $field = new xmldb_field('heading', XMLDB_TYPE_CHAR, '1024', null, null, null, null, 'value');
        // Launch change of type for field heading
        $dbman->change_field_precision($table, $field);
        // Changing length of field from 255 to 1024 to match length of hierarchy custom field names.
        $table = new xmldb_table('report_builder_filters');
        $field = new xmldb_field('filtername', XMLDB_TYPE_CHAR, '1024', null, null, null, null, 'advanced');
        // Launch change of type for field filtername
        $dbman->change_field_precision($table, $field);
        shezar_upgrade_mod_savepoint(true, 2014012400, 'shezar_reportbuilder');
    }

    if ($oldversion < 2014021100) {
        $tempconfig = get_config('reportbuilder', 'exportoptions');

        if (!is_null($tempconfig)) {
            $selected = array();
            $options = array(
                'xls'           => 1,
                'csv'           => 2,
                'ods'           => 4,
                'fusion'        => 8,
                'pdf_portrait'  => 16,
                'pdf_landscape' => 32,
            );

            foreach ($options as $option => $code) {
                // Bitwise operator to see if option bit is set.
                if (($tempconfig & $code) == $code) {
                    $selected[] = $code;
                }
            }
            $selected = implode(',', $selected);
            set_config('exportoptions', $selected, 'reportbuilder');
            unset($options);
            unset($option);
        }
        // Report builder savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2014021100, 'shezar_reportbuilder');
    }

    // Add table and fields for report builder search regions - standard, sidebar, toolbar.
    if ($oldversion < 2014030200) {

        // Define table report_builder_search_cols to be created.
        $table = new xmldb_table('report_builder_search_cols');

        // Adding fields to table report_builder_search_cols.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_builder_search_cols.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('repobuilsear_rep_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', array('id'));

        // Conditionally launch create table for report_builder_search_cols.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field region to be added to report_builder_filters.
        $table = new xmldb_table('report_builder_filters');
        $field = new xmldb_field('region', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'customname');

        // Conditionally launch add field region.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field toolbarsearch to be added to report_builder.
        $table = new xmldb_table('report_builder');
        $field = new xmldb_field('toolbarsearch', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'initialdisplay');

        // Conditionally launch add field toolbarsearch.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014030200, 'shezar', 'reportbuilder');
    }

    // For upgrade (not fresh install) version we leave default course/programs catalog.
    // For fresh installs - new course/programs catalog
    if ($oldversion < 2014030500) {
        set_config('enhancedcatalog', 0);
        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014030500, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014031400) {

        require_once($CFG->dirroot . '/shezar/reportbuilder/classes/rb_base_content.php');
        $changes = array(
            'own' => rb_user_content::USER_OWN,
            'reports' => rb_user_content::USER_DIRECT_REPORTS,
            'ownandreports' => (rb_user_content::USER_OWN | rb_user_content::USER_DIRECT_REPORTS)
        );
        // Update both 'value' and 'cachedvalue'.
        $sql = "UPDATE {report_builder_settings}
            SET value = :newval
            WHERE type = :type AND name = :name AND value = :oldval";
        $sql2 = "UPDATE {report_builder_settings}
            SET cachedvalue = :newval
            WHERE type = :type AND name = :name AND cachedvalue = :oldval";
        $params = array('type' => 'user_content', 'name' => 'who');

        $transaction = $DB->start_delegated_transaction();
        foreach ($changes as $oldval => $newval) {
            $params['oldval'] = $oldval;
            $params['newval'] = $newval;
            $DB->execute($sql, $params);
            $DB->execute($sql2, $params);
        }
        $transaction->allow_commit();

        // Report builder savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2014031400, 'shezar_reportbuilder');
    }

    if ($oldversion < 2014082200) {

        // Changing the default of field cachedvalue on table report_builder_settings to drop it.
        $table = new xmldb_table('report_builder_settings');
        $field = new xmldb_field('cachedvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'value');

        // Launch change of default for field cachedvalue.
        $dbman->change_field_default($table, $field);

        // Changing the default of field exporttofilesystem on table report_builder_schedule to drop it.
        $table = new xmldb_table('report_builder_schedule');
        $field = new xmldb_field('exporttofilesystem', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'frequency');

        // Launch change of default for field exporttofilesystem.
        $dbman->change_field_default($table, $field);

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014082200, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014082201) {

        // Changing precision of field cache on table report_builder to (4).
        $table = new xmldb_table('report_builder');
        $field = new xmldb_field('cache', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'hidden');

        // Launch change of precision for field cache.
        $dbman->change_field_precision($table, $field);

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014082201, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014090900) {
        // Fix renamed columns in existing reports.
        $DB->set_field_select('report_builder_columns', 'value', 'program_previous_completion',
                'type = ? AND value= ?',
                array('program_completion_history', 'program_completion_history_link'));

         $DB->set_field_select('report_builder_columns', 'value', 'course_completion_previous_completion',
                'type = ? AND value= ?',
                array('course_completion_history', 'course_completion_history_link'));
        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014090900, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014092400) {
        // Fix the column type.
        reportbuilder_rename_data('columns', 'dp_certification', 'prog_completion', 'timedue', 'certif_completion', 'timedue');
        // Fix the filter type.
        reportbuilder_rename_data('filters', 'dp_certification', 'prog_completion', 'timedue', 'certif_completion', 'timedue');
        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014092400, 'shezar', 'reportbuilder');
    }


    // This is the shezar 2.7 upgrade line.
    // All following versions need to be bumped up during merging from 2.6 until we have separate t2-release-27 branch!

    if ($oldversion < 2014100701) {

        // Define field transform to be added to report_builder_columns.
        $table = new xmldb_table('report_builder_columns');
        $field = new xmldb_field('transform', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'value');

        // Conditionally launch add field transform.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field aggregate to be added to report_builder_columns.
        $table = new xmldb_table('report_builder_columns');
        $field = new xmldb_field('aggregate', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'transform');

        // Conditionally launch add field aggregate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Invalidate all caches.
        $DB->set_field('report_builder_cache', 'changed', 1, array('changed' => 0));

        // Drop unused config field.
        $table = new xmldb_table('report_builder_cache');
        $field = new xmldb_field('config');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Delete all old cache tables.
        $rs = $DB->get_recordset_select('report_builder_cache', "cachetable IS NOT NULL AND cachetable <> ''", array(), 'id ASC',
                                        'id, cachetable');
        foreach ($rs as $cache) {
            $table = trim($cache->cachetable, '{}');
            if (!$table) {
                continue;
            }
            if ($dbman->table_exists($table)) {
                $dbman->drop_table(new xmldb_table($table));
            }
        }
        $rs->close();
        $DB->set_field('report_builder_cache', 'cachetable', null, array());

        // Add query hash field.
        $table = new xmldb_table('report_builder_cache');
        $field = new xmldb_field('queryhash', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'nextreport');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Drop unused config field.
        $table = new xmldb_table('report_builder_settings');
        $field = new xmldb_field('cachedvalue');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014100701, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014102300) {

        // Replace old category filter with the new one.
        $sql = "UPDATE {report_builder_filters}
        SET value = :newfiltervalue
        WHERE type = :filtertype
          AND value = :filtervalue
          AND region = :filterregion";
        $params = array(
            'newfiltervalue' => 'path',
            'filtertype' => 'course_category',
            'filtervalue' => 'id',
            'filterregion' => rb_filter_type::RB_FILTER_REGION_STANDARD
        );
        $DB->execute($sql, $params);

        // Replace old category filter in saved search.
        $sql = "SELECT rbs.*
        FROM {report_builder_saved} rbs
        INNER JOIN {report_builder_filters} rbf ON rbs.reportid = rbf.reportid
        WHERE rbf.type = :filtertype
          AND rbf.value = :filtervalue";
        $params = array('filtertype' => 'course_category', 'filtervalue' => 'path');
        $searches = $DB->get_records_sql($sql, $params);
        foreach ($searches as $search) {
            $todb = new stdClass();
            $todb->id = $search->id;
            $currentfilters = unserialize($search->search);
            $newfilters = array();
            foreach ($currentfilters as $key => $filter) {
                if ($key === 'course_category-id') {
                    // Change key and add recursive param to the filter.
                    $key = 'course_category-path';
                    $filter['recursive'] = 0;
                }
                $newfilters[$key] = $filter;
            }
            $todb->search = serialize($newfilters);
            $DB->update_record('report_builder_saved', $todb);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014102300, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014110400) {

        // Define field timemodified to be added to report_builder.
        $table = new xmldb_table('report_builder');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'toolbarsearch');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014110400, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014110401) {

        // Define field timemodified to be added to report_builder_saved.
        $table = new xmldb_table('report_builder_saved');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'ispublic');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014110401, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014110402) {

        // Define table report_builder_graph to be created.
        $table = new xmldb_table('report_builder_graph');

        // Adding fields to table report_builder_graph.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('stacked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('maxrecords', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '500');
        $table->add_field('category', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('legend', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('series', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('settings', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table report_builder_graph.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('repobuilcolu_rep_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'report_builder', array('id'));

        // Conditionally launch create table for report_builder_graph.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014110402, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014111300) {
        foreach(array('assignment', 'assignmentsummary') as $source) {
            // Get mod_assign reports.
            $reports = $DB->get_records('report_builder', array('source' => $source));

            // Delete them.
            // Do not use events because events require report source.
            foreach ($reports as $reportrec) {
                $id = $reportrec->id;
                $transaction = $DB->start_delegated_transaction();

                // Remove cached reports.
                reportbuilder_purge_cache($id, true);

                // Delete all records related to this report according current db scheme.
                $DB->delete_records('report_builder_schedule', array('reportid' => $id));
                $DB->delete_records('report_builder_columns', array('reportid' => $id));
                $DB->delete_records('report_builder_filters', array('reportid' => $id));
                $DB->delete_records('report_builder_settings', array('reportid' => $id));
                $DB->delete_records('report_builder_saved', array('reportid' => $id));
                $DB->delete_records('report_builder', array('id' => $id));

                $transaction->allow_commit();
            }
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014111300, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2014120400) {

        // Changing nullability of field name on table report_builder_saved to not null.
        $table = new xmldb_table('report_builder_saved');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'userid');

        // Update existing data to ''.
        $DB->execute("UPDATE {report_builder_saved} SET name = '' WHERE name IS NULL");

        // Launch change of nullability for field name.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field search on table report_builder_saved to null.
        $table = new xmldb_table('report_builder_saved');
        $field = new xmldb_field('search', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Launch change of nullability for field search.
        $dbman->change_field_notnull($table, $field);

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2014120400, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2015030201) {

        // Define table report_builder_schedule_email_audience.
        $table = new xmldb_table('report_builder_schedule_email_audience');

        // Adding fields to table report_builder_schedule_email_audience.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scheduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_builder_graph.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('repobuilscheaud_sch_fk', XMLDB_KEY_FOREIGN, array('scheduleid'), 'report_builder_schedule', array('id'));
        $table->add_key('repobuilscheaud_aud_fk', XMLDB_KEY_FOREIGN, array('cohortid'), 'cohort', array('id'));

        // Add index.
        $table->add_index('idx_schedule_aud', XMLDB_INDEX_UNIQUE, array('scheduleid', 'cohortid'));

        // Conditionally launch create table for report_builder_graph.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table report_builder_schedule_email_systemuser.
        $table = new xmldb_table('report_builder_schedule_email_systemuser');

        // Adding fields to table report_builder_schedule_email_audience.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scheduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_builder_graph.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('repobuilschesysuser_sch_fk', XMLDB_KEY_FOREIGN, array('scheduleid'), 'report_builder_schedule', array('id'));
        $table->add_key('repobuilschesysuser_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Add index.
        $table->add_index('idx_schedule_sysuser', XMLDB_INDEX_UNIQUE, array('scheduleid', 'userid'));

        // Conditionally launch create table for report_builder_graph.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table report_builder_schedule_email_external.
        $table = new xmldb_table('report_builder_schedule_email_external');

        // Adding fields to table report_builder_schedule_email_audience.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scheduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_builder_graph.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('repobuilscheexternaluser_sch_fk', XMLDB_KEY_FOREIGN, array('scheduleid'), 'report_builder_schedule', array('id'));

        // Add index.
        $table->add_index('idx_schedule_extuser', XMLDB_INDEX_UNIQUE, array('scheduleid', 'email'));

        // Conditionally launch create table for report_builder_graph.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2015030201, 'shezar', 'reportbuilder');
    }

    // T-14365 Convert daysbefore and daysafter filter from dates to days.
    if ($oldversion < 2015030203) {

        // This could take some time.
        upgrade_set_timeout(300);

        $select = $DB->sql_like('search', ':before') . " OR " . $DB->sql_like('search', ':after');
        $params = array('before' => '%' . $DB->sql_like_escape('daysbefore') . '%',
            'after' => '%' . $DB->sql_like_escape('daysafter') . '%');

        $total = $DB->count_records_select('report_builder_saved', $select, $params);

        if ($total > 0) {
            $i = 0;
            $pbar = new progress_bar('copytimeexpirestotimedue', 500, true);
            $pbar->update($i, $total, "Converting Report Builder date filters from dates to days - {$i}/{$total}.");

            $recordsrs = $DB->get_recordset_select('report_builder_saved', $select, $params, '', 'id, search, timemodified');
            // The interface actually enforces 9999 max which is about 27 years, but we allow 10x more.
            // This value is used to protect against converting a number that has already been converted.
            $maxdays = 100000;
            foreach ($recordsrs as $record) {
                $filters = unserialize($record->search);
                $needsupdate = false;
                foreach ($filters as $filtername => $filter) {
                    // Only upgrade this filter if it has the specific set of properties.
                    // This is the only way (short of loading the whole report object) to identify date filters.
                    if (!is_array($filter) ||
                        !isset($filter['after']) ||
                        !isset($filter['before']) ||
                        !isset($filter['daysafter']) ||
                        !isset($filter['daysbefore']) ||
                        count($filter) > 4) {
                        continue;
                    }
                    // Daysbefore and daysafter were originally a date, calculated using
                    // mktime(0, 0, 0, gmdate('n'), gmdate('j'), gmdate('Y'))
                    // so to convert to a number of days, we subtract the time modified (or reverse), divide by the length
                    // of a day and round up (or down).
                    if (isset($filter['daysafter']) && $filter['daysafter'] > $maxdays) {
                        $filters[$filtername]['daysafter'] = ceil(($filter['daysafter'] - $record->timemodified) / DAYSECS);
                        $needsupdate = true;
                    }
                    if (isset($filter['daysbefore']) && $filter['daysbefore'] > $maxdays) {
                        $filters[$filtername]['daysbefore'] = floor(($record->timemodified - $filter['daysbefore']) / DAYSECS);
                        $needsupdate = true;
                    }
                }
                $record->search = serialize($filters);
                if ($needsupdate) {
                    $DB->update_record('report_builder_saved', $record);
                }
                unset($record);

                $i++;
                $pbar->update($i, $total, "Converting Report Builder date filters from dates to days - {$i}/{$total}.");
            }
            $recordsrs->close();
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2015030203, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2015061503) {
        // Switch to new settings format - instead of constants use plugin names.
        // Change numbers to new plugin names.
        $formats = array(
            '1' => 'excel',
            '2' => 'csv',
            '4' => 'ods',
            '8' => 'fusion',
            '16' => 'pdfportrait',
            '32' => 'pdflandscape',
        );

        $exportoptions = get_config('reportbuilder', 'exportoptions');
        if ($exportoptions) {
            $exportoptions = explode(',', $exportoptions);
            foreach ($exportoptions as $k => $v) {
                if (isset($formats[$v])) {
                    $exportoptions[$k] = $formats[$v];
                }
            }
            $exportoptions = implode(',', $exportoptions);
            set_config('exportoptions', $exportoptions, 'reportbuilder');
        }

        // Changing type of field format on table report_builder_schedule to char.
        $table = new xmldb_table('report_builder_schedule');
        $field = new xmldb_field('format', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null, 'savedsearchid');

        // Launch change of type for field format.
        $dbman->change_field_type($table, $field);

        foreach ($formats as $old => $new) {
            $DB->set_field('report_builder_schedule', 'format', $new, array('format' => $old));
        }

        unset($exportoptions);
        unset($formats);

        // Migrate settings.
        $oldvalue = get_config('reportbuilder', 'pdffont');
        if ($oldvalue !== false) {
            set_config('pdffont', $oldvalue, 'tabexport_pdflandscape');
            set_config('pdffont', $oldvalue, 'tabexport_pdfportrait');
            unset_config('pdffont', 'reportbuilder');
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2015061503, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2015072800) {
        // Global report restrictions.

        $table = new xmldb_table('report_builder');
        $field = new xmldb_field('globalrestriction', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'toolbarsearch');

        // Conditionally launch add field globalrestriction.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Table report_builder_global_restriction.
        $table = new xmldb_table('report_builder_global_restriction');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('allrecords', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('allusers', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists('report_builder_global_restriction')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_records_grp_cohort.
        $table = new xmldb_table('reportbuilder_grp_cohort_record');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderrecordid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderrecordid', XMLDB_KEY_FOREIGN, array('reportbuilderrecordid'), 'report_builder_global_restriction', array('id'));
        $table->add_key('cohortid', XMLDB_KEY_FOREIGN, array('cohortid'), 'cohort', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_cohort_record')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_records_grp_org.
        $table = new xmldb_table('reportbuilder_grp_org_record');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderrecordid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('orgid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('includechildren', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderrecordid', XMLDB_KEY_FOREIGN, array('reportbuilderrecordid'), 'report_builder_global_restriction', array('id'));
        $table->add_key('orgid', XMLDB_KEY_FOREIGN, array('orgid'), 'org', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_org_record')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_records_grp_pos.
        $table = new xmldb_table('reportbuilder_grp_pos_record');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderrecordid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('posid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('includechildren', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderrecordid', XMLDB_KEY_FOREIGN, array('reportbuilderrecordid'), 'report_builder_global_restriction', array('id'));
        $table->add_key('posid', XMLDB_KEY_FOREIGN, array('posid'), 'pos', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_pos_record')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_records_grp_user.
        $table = new xmldb_table('reportbuilder_grp_user_record');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderrecordid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderrecordid', XMLDB_KEY_FOREIGN, array('reportbuilderrecordid'), 'reportbuilderrecordid', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_user_record')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_users_grp_cohort.
        $table = new xmldb_table('reportbuilder_grp_cohort_user');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderuserid', XMLDB_KEY_FOREIGN, array('reportbuilderuserid'), 'report_builder_global_restriction', array('id'));
        $table->add_key('cohortid', XMLDB_KEY_FOREIGN, array('cohortid'), 'cohort', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_cohort_user')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_users_grp_org.
        $table = new xmldb_table('reportbuilder_grp_org_user');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('orgid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('includechildren', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderuserid', XMLDB_KEY_FOREIGN, array('reportbuilderuserid'), 'report_builder_global_restriction', array('id'));
        $table->add_key('orgid', XMLDB_KEY_FOREIGN, array('orgid'), 'org', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_org_user')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_users_grp_pos.
        $table = new xmldb_table('reportbuilder_grp_pos_user');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('posid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('includechildren', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderuserid', XMLDB_KEY_FOREIGN, array('reportbuilderuserid'), 'report_builder_global_restriction', array('id'));
        $table->add_key('posid', XMLDB_KEY_FOREIGN, array('posid'), 'pos', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_pos_user')) {
            $dbman->create_table($table);
        }

        // Table rb_restricted_users_grp_user.
        $table = new xmldb_table('reportbuilder_grp_user_user');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportbuilderuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('reportbuilderuserid', XMLDB_KEY_FOREIGN, array('reportbuilderuserid'), 'report_builder_global_restriction', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        if (!$dbman->table_exists('reportbuilder_grp_user_user')) {
            $dbman->create_table($table);
        }

        shezar_upgrade_mod_savepoint(true, 2015072800, 'shezar_reportbuilder');
    }

    if ($oldversion < 2015100901) {
        // Set global setting for global restriction records per page.
        // Default: 40.
        set_config('globalrestrictionrecordsperpage', 40, 'reportbuilder');
        shezar_upgrade_mod_savepoint(true, 2015100901, 'shezar_reportbuilder');
    }

    if ($oldversion < 2015100902) {

        // Get the reports created by deleted user/s.
        $sql = "SELECT rbs.id
                  FROM {report_builder_schedule} rbs
                  JOIN {user} u ON u.id = rbs.userid
                 WHERE u.deleted = 1";
        $reports = $DB->get_records_sql($sql);
        // Delete all scheduled reports created by deleted user/s.
        foreach ($reports as $report) {
            $DB->delete_records('report_builder_schedule_email_audience',   array('scheduleid' => $report->id));
            $DB->delete_records('report_builder_schedule_email_systemuser', array('scheduleid' => $report->id));
            $DB->delete_records('report_builder_schedule_email_external',   array('scheduleid' => $report->id));
            $DB->delete_records('report_builder_schedule', array('id' => $report->id));
        }

        // Get deleted user/s.
        $sql = "SELECT DISTINCT rbses.userid
                  FROM {report_builder_schedule_email_systemuser} rbses
                  JOIN {user} u ON u.id = rbses.userid
                 WHERE u.deleted = 1";
        $reports = $DB->get_fieldset_sql($sql);
        if ($reports) {
            list($sqlin, $sqlparm) = $DB->get_in_or_equal($reports);
            // Remove deleted user/s from scheduled reports.
            $DB->execute("DELETE FROM {report_builder_schedule_email_systemuser} WHERE userid $sqlin", $sqlparm);
        }

        // Get deleted audience/s.
        $sql = "SELECT DISTINCT rbsea.cohortid
                  FROM {report_builder_schedule_email_audience} rbsea
                 WHERE NOT EXISTS (
                           SELECT 1 FROM {cohort} ch WHERE rbsea.cohortid = ch.id
               )";
        $cohorts = $DB->get_fieldset_sql($sql);
        if ($cohorts) {
            list($sqlin, $sqlparm) = $DB->get_in_or_equal($cohorts);
            // Remove deleted audience/s from scheduled reports.
            $DB->execute("DELETE FROM {report_builder_schedule_email_audience} WHERE cohortid $sqlin", $sqlparm);
        }

        // Reportbuilder savepoint reached.
        upgrade_plugin_savepoint(true, 2015100902, 'shezar', 'reportbuilder');
    }

    if ($oldversion < 2016080300) {
        // List of all the columns etc to move from user to job_assignment.
        $migrations = array(
            'user' => array(
                'title'                         => 'alltitlenames',
                'posstartdate'                  => 'allstartdates',
                'posenddate'                    => 'allenddates',
                'managername'                   => 'allmanagernames',
                'managerfirstname'              => 'allmanagerfirstnames',
                'managerlastname'               => 'allmanagerlastnames',
                'managerid'                     => 'allmanagerids',
                'manageridnumber'               => 'allmanageridnumbers',
                'manageremail'                  => 'allmanagerobsemails',
                'manageremailunobscured'        => 'allmanagerunobsemails',
                'position'                      => 'allpositionnames',
                'positionidnumber'              => 'allpositionidnumbers',
                'pos_type'                      => 'allpositiontypes',
                'positionframework'             => 'allposframenames',
                'positionframeworkid'           => 'allposframeids',
                'positionframeworkidnumber'     => 'allposframeidnumbers',
                'organisation'                  => 'allorganisationnames',
                'organisationidnumber'          => 'allorganisationidnumbers',
                'org_type'                      => 'allorganisationtypes',
                'organisationframework'         => 'allorgframenames',
                'organisationframeworkid'       => 'allorgframeids',
                'organisationframeworkidnumber' => 'allorgframeidnumbers',
            )
        );

        $removedcols = array(
            'user' => array(
                'positionframeworkdescription',
                'positionpath',
                'positionid',
                'positionid2',
                'pos_type_id',
                'organisationframeworkdescription',
                'organisationpath',
                'organisationid',
                'organisationid2',
                'org_type_id',
            ),
        );

        // Remove any unused or text area columns since we can't concatenate them.
        foreach ($removedcols as $type => $columns) {
            foreach ($columns as $column) {
                // Delete these columns.
                $DB->delete_records('report_builder_columns', array('type' => $type, 'value' => $column));
            }
        }

        // Add in the position custom field columns.
        $ptype_cols = array();
        $fields = $DB->get_records('pos_type_info_field', array());
        foreach ($fields as $field) {
            $oldname = "custom_field_{$field->id}";
            $newname = "pos_{$field->datatype}_{$field->shortname}";
            if ($field->datatype === 'textarea') {
                $DB->delete_records('report_builder_columns', array('type' => 'user', 'value' => $oldname));
            } else if ($field->datatype === 'multiselect') {
                $ptype_cols["{$oldname}_text"] = $newname;
                $ptype_cols["{$oldname}_icon"] = $newname;
            } else {
                $ptype_cols["{$oldname}"] = $newname;
            }
        }
        $migrations['pos_type'] = $ptype_cols;

        // Add in the organisation custom field columns.
        $otype_cols = array();
        $fields = $DB->get_records('org_type_info_field', array());
        foreach ($fields as $field) {
            $oldname = "custom_field_{$field->id}";
            $newname = "org_{$field->datatype}_{$field->shortname}";
            if ($field->datatype === 'textarea') {
                $DB->delete_records('report_builder_columns', array('type' => 'user', 'value' => $oldname));
            } else if ($field->datatype === 'multiselect') {
                $otype_cols["{$oldname}_text"] = $newname;
                $otype_cols["{$oldname}_icon"] = $newname;
            } else {
                $otype_cols["{$oldname}"] = $newname;
            }
        }
        $migrations['org_type'] = $otype_cols;

        // Migrate all of the columns across to their concatenated job assignment equivalents.
        foreach ($migrations as $oldtype => $columns) {
            // First map across the column names to the appropriate new values.
            shezar_reportbuilder_migrate_column_names($columns, $oldtype);
            // Then move all position assignment columns to job assignment columns.
            shezar_reportbuilder_migrate_column_types($columns, $oldtype, 'job_assignment');
            // Finally update any default sort columns to the new job assignment columns.
            shezar_reportbuilder_migrate_default_sort_columns($columns, $oldtype, 'job_assignment');
        }

        // Add migrations for the removed non-selectable columns that are used by filters.
        $migrations['user']['positionpath'] = 'allpositions';
        $migrations['user']['positionid'] = 'allpositions';
        $migrations['user']['positionid2'] = 'allpositions';
        $migrations['user']['pos_type_id'] = 'allpostypes';
        $migrations['user']['organisationpath'] = 'allorganisations';
        $migrations['user']['organisationid'] = 'allorganisations';
        $migrations['user']['organisationid2'] = 'allorganisations';
        $migrations['user']['org_type_id'] = 'allorgtypes';
        // NOTE : The description fields are purposfully removed.

        $removedfils = array(
            'user' => array(
                'positionframeworkdescription',
                'organisationframeworkdescription',
            ),
        );

        // Remove any text area filters since we can't concatenate them.
        foreach ($removedfils as $type => $filters) {
            foreach ($filters as $filter) {
                // Delete these columns.
                $DB->delete_records('report_builder_filters', array('type' => $type, 'value' => $column));
            }
        }

        // Migrate all of the filters across to their concatenated job assignment equivalents.
        foreach ($migrations as $oldtype => $filters) {
            // First map across the column names to the appropriate new values.
            shezar_reportbuilder_migrate_filter_names($filters, $oldtype);
            // Then move position assignment filters to job assignment filters.
            shezar_reportbuilder_migrate_filter_types($filters, $oldtype, 'job_assignment');
            // Update the filters in any saved searches to match updated filters.
            shezar_reportbuilder_migrate_saved_search_filters($filters, $oldtype, 'job_assignment');
        }

        // Reportbuilder savepoint reached.
        shezar_upgrade_mod_savepoint(true, 2016080300, 'shezar_reportbuilder');
    }

    return true;
}
