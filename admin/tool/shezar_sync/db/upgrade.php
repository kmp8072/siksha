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
 * @subpackage cohort
 */

/**
 * DB upgrades for shezar Sync
 */

function xmldb_tool_shezar_sync_upgrade($oldversion) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // shezar 2.2+ upgrade

    if ($oldversion < 2012101100) {
        // Rename to deleted
        $sql = "UPDATE {config_plugins}
            SET name = 'fieldmapping_deleted'
            WHERE plugin = 'shezar_sync_source_user_csv'
            AND name = 'fieldmapping_delete' ";
        $DB->execute($sql);

        // Rename to deleted
        $sql = "UPDATE {config_plugins}
            SET name = 'import_deleted'
            WHERE plugin = 'shezar_sync_source_user_csv'
            AND name = 'import_delete' ";
        $DB->execute($sql);

        // Set "delete" as the default source name if no field mapping already exists
        // This will allow the existing sources to remain unchanged.
        $sql = "UPDATE {config_plugins}
            SET value = 'delete'
            WHERE plugin = 'shezar_sync_source_user_csv'
            AND name = 'fieldmapping_deleted'
            AND " . $DB->sql_compare_text('value') . " = ''";
        $DB->execute($sql);

        upgrade_plugin_savepoint(true, 2012101100, 'tool', 'shezar_sync');
    }

    //manual modifying permissions in $DB to retain any existing permissions
    if ($oldversion < 2012121200) {
        $oldname = 'tool/shezar_sync:setfilesdirectory';
        $newname = 'tool/shezar_sync:setfileaccess';

        $sql_capability = "UPDATE {capabilities} SET name = ? WHERE name = ?";
        $DB->execute($sql_capability, array($newname, $oldname));

        $sql_role_capability = "UPDATE {role_capabilities} SET capability = ? WHERE capability = ?";
        $DB->execute($sql_role_capability, array($newname, $oldname));

        upgrade_plugin_savepoint(true, 2012121200, 'tool', 'shezar_sync');
    }

    if ($oldversion < 2013031400) {
        $table = new xmldb_table('shezar_sync_log');
        $field = new xmldb_field('runid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0, 'info');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('runid', XMLDB_INDEX_NOTUNIQUE, array('runid'));

        // Conditionally launch add index runid
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        //automatically add the column to the embedded sync_log
        $params = array('shortname' => 'shezarsynclog', 'source' => 'shezar_sync_log', 'embedded' => 1);
        if ($report = $DB->get_record('report_builder', $params)) {
            $sortorder = $DB->get_field('report_builder_columns', 'MAX(sortorder) + 1', array('reportid' => $report->id));
            $sortorder = !empty($sortorder) ? $sortorder : 1;

            $todb = new stdClass();
            $todb->reportid = $report->id;
            $todb->type = $report->source;
            $todb->value = 'runid';
            $todb->heading = get_string('runid', 'tool_shezar_sync');
            $todb->sortorder = $sortorder;
            $todb->hidden = 0;
            $todb->customheading = 0;

            $params = array('reportid' => $report->id, 'type' => $report->source, 'value' => 'runid');
            if (!$DB->record_exists('report_builder_columns', $params)) {
                $DB->insert_record('report_builder_columns', $todb);
            }
        }

        upgrade_plugin_savepoint(true, 2013031400, 'tool', 'shezar_sync');
    }

    if ($oldversion < 2013092000) {
        // Set the lastnotify flag so emails with entire log file don't get sent out on first run.
        set_config('lastnotify', time(), 'shezar_sync');

        upgrade_plugin_savepoint(true, 2013092000, 'tool', 'shezar_sync');
    }

    if ($oldversion < 2013101500) {
        // Add sync actions for all elements.
        $actions = array('allow_create', 'allow_update', 'allow_delete');

        $params = array('plugin' => 'shezar_sync_element_user', 'name' => 'removeuser');
        if ($oldsetting = $DB->get_record('config_plugins', $params)) {
            $DB->delete_records('config_plugins', $params);
        }

        foreach ($actions as $action) {
            $params = array('plugin' => 'shezar_sync_element_user', 'name' => $action);
            if (!$DB->record_exists('config_plugins', $params)) {
                $newsetting = new stdClass();
                $newsetting->plugin = 'shezar_sync_element_user';
                $newsetting->name   = $action;
                $newsetting->value  = $action == 'allow_delete' ? !empty($oldsetting->value) : 1; // Keep the previously set value.
                $DB->insert_record('config_plugins', $newsetting);
            }
        }

        foreach (array('org', 'pos') as $element) {
            $params = array('plugin' => "shezar_sync_element_{$element}", 'name' => 'removeitems');
            if ($oldsetting = $DB->get_record('config_plugins', $params)) {
                $DB->delete_records('config_plugins', $params);
            }

            foreach ($actions as $action) {
                $params = array('plugin' => "shezar_sync_element_{$element}", 'name' => $action);
                if (!$DB->record_exists('config_plugins', $params)) {
                    $newsetting = new stdClass();
                    $newsetting->plugin = "shezar_sync_element_{$element}";
                    $newsetting->name   = $action;
                    $newsetting->value  = $action == 'allow_delete' ? !empty($oldsetting->value) : 1; // Keep the previously set value.
                    $DB->insert_record('config_plugins', $newsetting);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2013101500, 'tool', 'shezar_sync');
    }

    if ($oldversion < 2015080500) {
        if (get_config('shezar_sync_source_user_database', 'database_dateformat') == false) {
            // Set external database source settings, new date format setting from
            // value of Location settings, CSV import date format.
            $database_dateformat = get_config('', 'csvdateformat');
            set_config('database_dateformat', $database_dateformat, 'shezar_sync_source_user_database');
        }

        upgrade_plugin_savepoint(true, 2015080500, 'tool', 'shezar_sync');
    }

    if ($oldversion < 2015121800) {
        // Update any empty user lang fields to $CFG->lang.
        $sql = "UPDATE {user} set lang = ? WHERE lang IS NULL OR lang = ''";
        $params = array($CFG->lang);

        $DB->execute($sql, $params);

        upgrade_plugin_savepoint(true, 2015121800, 'tool', 'shezar_sync');
    }

    if ($oldversion < 2016012000) {
        require_once($CFG->dirroot . '/admin/tool/shezar_sync/locallib.php');

        // Convert old setting to scheduled task if the scheduled task hasn't been changed from default.
        $task = new \shezar_core\task\tool_shezar_sync_task();
        if (!$task->is_customised()) {
            // Do conversion
            $schedule = get_config('shezar_sync', 'schedule');
            $frequency = get_config('shezar_sync', 'frequency');
            $cronenable = get_config('shezar_sync', 'cronenable');

            $data = new stdClass();
            $data->schedule = $schedule;
            $data->frequency = $frequency;
            $data->cronenable = $cronenable;

            // Save old schedule to scheduled task.
            save_scheduled_task_from_form($data);
        }

        // Remove old scheduling settings (we are now using scheduled tasks).
        unset_config('cronenable', 'shezar_sync');
        unset_config('frequency', 'shezar_sync');
        unset_config('schedule', 'shezar_sync');
        unset_config('nextcron', 'shezar_sync');

        upgrade_plugin_savepoint(true, 2016012000, 'tool', 'shezar_sync');
    }

    if ($oldversion < 2016082500) {

        // TL-8647 saw the introduction of a new setting to determine how empty values should be handled in CSV sources.
        // For upgrade we want to maintain previous behaviour where an empty value removes data.

        set_config('csvsaveemptyfields', true, 'shezar_sync_element_user');
        set_config('csvsaveemptyfields', true, 'shezar_sync_element_pos');
        set_config('csvsaveemptyfields', true, 'shezar_sync_element_org');

        upgrade_plugin_savepoint(true, 2016082500, 'tool', 'shezar_sync');
    }

    return true;
}
