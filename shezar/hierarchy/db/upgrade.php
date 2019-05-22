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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Ciaran Irvine <ciaran.irvine@shezarlms.com>
 * @package shezar
 * @subpackage shezar_core
 */

/**
 * DB upgrades for shezar Hierarchies
 */

require_once($CFG->dirroot.'/shezar/core/db/utils.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/position/lib.php');

/**
 * Database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 */
function xmldb_shezar_hierarchy_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;
    $dbman = $DB->get_manager();

    if ($oldversion < 2012071000) {
        $table = new xmldb_table('pos_type_info_field');
        $field = new xmldb_field('defaultdata', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'forceunique');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_notnull($table, $field);
        }
        shezar_upgrade_mod_savepoint(true, 2012071000, 'shezar_hierarchy');
    }

    //Update to set default proficient value in competency scale
    if ($oldversion < 2012071200) {
        $scaleid = $DB->get_field('comp_scale', 'id', array('name' => get_string('competencyscale', 'shezar_hierarchy')));
        if (!$DB->record_exists('comp_scale_values', array('scaleid' => $scaleid, 'proficient' => 1))) {
            $scalevalueid = $DB->get_field_sql("
                    SELECT id
                    FROM {comp_scale_values}
                    WHERE scaleid = ?
                    ORDER BY sortorder ASC", array($scaleid), IGNORE_MULTIPLE);
            $todb = new stdClass();
            $todb->id = $scalevalueid;
            $todb->proficient = 1;
            $DB->update_record('comp_scale_values', $todb);
        }
        shezar_upgrade_mod_savepoint(true, 2012071200, 'shezar_hierarchy');
    }

    // Alter table names
    // comp_evidence => comp_record,
    // comp_evidence_items => comp_criteria
    // comp_evidence_items_evidence => comp_criteria_record
    if ($oldversion < 2013031400) {
        $compevidence = new xmldb_table('comp_evidence');
        $compevidenceitems = new xmldb_table('comp_evidence_items');
        $compevidenceitemsevidence = new xmldb_table('comp_evidence_items_evidence');


        if ($dbman->table_exists($compevidence)) {
            $dbman->rename_table($compevidence, 'comp_record');
        }
        if ($dbman->table_exists($compevidenceitems)) {
            $dbman->rename_table($compevidenceitems, 'comp_criteria');
        }
        if ($dbman->table_exists($compevidenceitemsevidence)) {
            $dbman->rename_table($compevidenceitemsevidence, 'comp_criteria_record');
        }

        // Indexes
        $indexes = array(
          'comp_record' => array(
              //'old name', 'new name, TYPE, array('fields')
              array('compevid_usecom_uix', 'compreco_usecom_uix', XMLDB_INDEX_UNIQUE, array('userid', 'competencyid')),
              array('compevid_com_ix', 'compreco_com_ix', XMLDB_INDEX_NOTUNIQUE, array('competencyid')),
              array('compevid_man_ix', 'compreco_man_ix', XMLDB_INDEX_NOTUNIQUE, array('manual')),
              array('compevid_rea_ix', 'compreco_rea_ix', XMLDB_INDEX_NOTUNIQUE, array('reaggregate')),
              array('compevid_use_ix', 'compreco_use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'))
          ),
          'comp_criteria' => array(
              //'old name', 'new name, TYPE, array('fields')
              array('compeviditem_com_ix', 'compcrit_com_ix', XMLDB_INDEX_NOTUNIQUE, array('competencyid')),
              array('compeviditem_ite2_ix', 'compcrit_ite2_ix', XMLDB_INDEX_NOTUNIQUE, array('iteminstance')),
              array('compeviditem_ite_ix', 'compcrit_ite_ix', XMLDB_INDEX_NOTUNIQUE, array('itemtype'))
          ),
          'comp_criteria_record' => array(
              //'old name', 'new name, TYPE, array('fields')
              array('compeviditemevid_useco_uix', 'compcritreco_useco_uix', XMLDB_INDEX_UNIQUE, array('userid', 'competencyid', 'itemid')),
              array('compeviditemevid_ite_ix', 'compcritreco_ite_ix', XMLDB_INDEX_NOTUNIQUE, array('itemid')),
              array('compeviditemevid_pro_ix', 'compcritreco_pro_ix', XMLDB_INDEX_NOTUNIQUE, array('proficiencymeasured')),
              array('compeviditemevid_tim_ix', 'compcritreco_tim_ix', XMLDB_INDEX_NOTUNIQUE, array('timemodified')),
              array('compeviditemevid_use_ix', 'compcritreco_use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid')),
              array('compeviditemevid_useite_ix', 'compcritreco_useite_ix', XMLDB_INDEX_NOTUNIQUE, array('userid', 'itemid'))
          )
        );

        foreach ($indexes as $tablename => $tableindexes) {
            $table = new xmldb_table($tablename);
            foreach ($tableindexes as $index) {
                $oldindex = new xmldb_index($index[0], $index[2], $index[3]);
                $newindex = new xmldb_index($index[1], $index[2], $index[3]);
                if ($dbman->index_exists($table, $oldindex)) {
                    $dbman->drop_index($table, $oldindex);
                }
                if (!$dbman->index_exists($table, $newindex)) {
                    $dbman->add_index($table, $newindex);
                }
            }
        }
        shezar_upgrade_mod_savepoint(true, 2013031400, 'shezar_hierarchy');
    }

    if ($oldversion < 2013041000) {
        //fix the sort order for any legacy (1.0.x) hierarchy custom fields
        //that are still ordered by now non-existent depth categories

        $hierarchylist = array('pos', 'org' ,'comp');
        foreach ($hierarchylist as $hierarchy) {
            $typesql = "SELECT id FROM {{$hierarchy}_type}";
            $types = $DB->get_records_sql($typesql);

            foreach ($types as $type) {
                $countsql = "SELECT COUNT(*) as count
                             FROM {{$hierarchy}_type_info_field}
                             WHERE typeid = ?
                             AND categoryid IS NOT NULL";
                $count = $DB->count_records_sql($countsql, array($type->id));

                if ($count != 0){
                    $sql = "SELECT id, sortorder, categoryid
                            FROM {{$hierarchy}_type_info_field}
                            WHERE typeid = ?
                            ORDER BY categoryid, sortorder";
                    $neworder = $DB->get_records_sql($sql, array($type->id));
                    $sortorder = 1;
                    $transaction = $DB->start_delegated_transaction();

                    foreach ($neworder as $item) {
                        $item->sortorder = $sortorder++;
                        $item->categoryid = null;
                        $DB->update_record("{$hierarchy}_type_info_field", $item);
                    }

                    $transaction->allow_commit();
                }
            }
        }
        shezar_upgrade_mod_savepoint(true, 2013041000, 'shezar_hierarchy');
    }

    // Create all of the tables for the goals hierarchy here.
    if ($oldversion < 2013080500) {
        global $USER;

        // Define table goal to be created.
        $table = new xmldb_table('goal');

        // Adding fields to table goal.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('frameworkid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('path', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('parentid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('proficiencyexpected', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('depthlevel', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('typeid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sortthread', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table goal.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('frameworkid', XMLDB_KEY_FOREIGN, array('frameworkid'), 'goal_framework', array('id'));

        // Adding indexes to table goal.
        $table->add_index('parentid', XMLDB_INDEX_NOTUNIQUE, array('parentid'));

        // Adding comment to table goal.
        $table->setComment('shezar Goals');

        // Conditionally launch create table for goal.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_framework to be created.
        $table = new xmldb_table('goal_framework');

        // Adding fields to table goal_framework.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hidecustomfields', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_framework.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table goal_framework.
        $table->add_index('goalfram_sor_uix', XMLDB_INDEX_UNIQUE, array('sortorder'));

        // Adding comment to table goal_framework.
        $table->setComment('A collection of goals');

        // Conditionally launch create table for goal_framework.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_scale to be created.
        $table = new xmldb_table('goal_scale');

        // Adding fields to table goal_scale.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('defaultid', XMLDB_TYPE_INTEGER, '2', null, null, null, null);

        // Adding keys to table goal_scale.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding comment to table goal_scale.
        $table->setComment('Scale represents the different levels of achievement of a goal');

        // Conditionally launch create table for goal_scale.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_scale_assignments to be created.
        $table = new xmldb_table('goal_scale_assignments');

        // Adding fields to table goal_scale_assignments.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scaleid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('frameworkid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_scale_assignments.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding comment to table goal_scale_assignments.
        $table->setComment('Tracks which scales are assigned to which goal frameworks');

        // Conditionally launch create table for goal_scale_assignments.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_scale_values to be created.
        $table = new xmldb_table('goal_scale_values');

        // Adding fields to table goal_scale_values.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('scaleid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('numericscore', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('proficient', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table goal_scale_values.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table goal_scale_values.
        $table->add_index('goaltype_idn_ix', XMLDB_INDEX_NOTUNIQUE, array('idnumber'));

        // Adding comment to table goal_scale_values.
        $table->setComment('The individual values that make up a goal scale');

        // Conditionally launch create table for goal_scale_values.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_record to be created.
        $table = new xmldb_table('goal_record');

        // Adding fields to table goal_record.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scalevalueid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table goal_record.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('goal_fk', XMLDB_KEY_FOREIGN, array('goalid'), 'goal', array('id'));
        $table->add_key('scvl_fk', XMLDB_KEY_FOREIGN, array('scalevalueid'), 'goal_scale_values', array('id'));

        // Adding comment to table goal_record.
        $table->setComment('Track current status of a user within goals');

        // Conditionally launch create table for goal_record.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_type to be created.
        $table = new xmldb_table('goal_type');

        // Adding fields to table goal_type.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);

        // Adding keys to table goal_type.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding comment to table goal_type.
        $table->setComment('Goal types are used to manage custom fields');

        // Conditionally launch create table for goal_type.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_type_info_data to be created.
        $table = new xmldb_table('goal_type_info_data');

        // Adding fields to table goal_type_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_type_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fieldid', XMLDB_KEY_FOREIGN, array('fieldid'), 'goal_type_info_field', array('id'));

        // Adding comment to table goal_type_info_data.
        $table->setComment('Stores custom field data related to goals');

        // Conditionally launch create table for goal_type_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_type_info_field to be created.
        $table = new xmldb_table('goal_type_info_field');

        // Adding fields to table goal_type_info_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('typeid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('locked', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('required', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('forceunique', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('defaultdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param1', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param2', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param3', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param4', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param5', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table goal_type_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding comment to table goal_type_info_field.
        $table->setComment('Stores the custom fields for each goal type');

        // Conditionally launch create table for goal_type_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_user_assignment to be created.
        $table = new xmldb_table('goal_user_assignment');

        // Adding fields to table goal_user_assignment.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('assigntype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('assignmentid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('extrainfo', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_user_assignment.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('goalid', XMLDB_KEY_FOREIGN, array('goalid'), 'goal', array('id'));

        // Adding comment to table goal_user_assignment.
        $table->setComment('Stores the user assignments of goals');

        // Conditionally launch create table for goal_user_assignment.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

         // Define table goal_grp_pos to be created.
        $table = new xmldb_table('goal_grp_pos');

        // Adding fields to table goal_grp_pos.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('posid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('includechildren', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_grp_pos.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('posid', XMLDB_KEY_FOREIGN, array('posid'), 'pos', array('id'));
        $table->add_key('goalid', XMLDB_KEY_FOREIGN, array('goalid'), 'goal', array('id'));

        // Adding comment to table goal_grp_pos.
        $table->setComment('Stores the position assignments of goals');

        // Conditionally launch create table for goal_grp_pos.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_grp_org to be created.
        $table = new xmldb_table('goal_grp_org');

        // Adding fields to table goal_grp_org.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('orgid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('includechildren', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_grp_org.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('orgid', XMLDB_KEY_FOREIGN, array('orgid'), 'org', array('id'));
        $table->add_key('goalid', XMLDB_KEY_FOREIGN, array('goalid'), 'goal', array('id'));

        // Adding comment to table goal_grp_org.
        $table->setComment('Stores the organisation assignments of goals');

        // Conditionally launch create table for goal_grp_org.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_grp_cohort to be created.
        $table = new xmldb_table('goal_grp_cohort');

        // Adding fields to table goal_grp_cohort.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_grp_cohort.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cohortid', XMLDB_KEY_FOREIGN, array('cohortid'), 'cohort', array('id'));
        $table->add_key('goalid', XMLDB_KEY_FOREIGN, array('goalid'), 'goal', array('id'));

        // Adding comment to table goal_grp_cohort.
        $table->setComment('Stores the assignments of goals');

        // Conditionally launch create table for goal_grp_cohort.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

         // Define table goal_personal to be created.
        $table = new xmldb_table('goal_personal');

        // Adding fields to table goal_personal.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('targetdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('scaleid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('scalevalueid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('assigntype', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_personal.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('scaleid', XMLDB_KEY_FOREIGN, array('scaleid'), 'goal_scale', array('id'));
        $table->add_key('scalevalueid', XMLDB_KEY_FOREIGN, array('scalevalueid'), 'goal_scale_values', array('id'));

        // Adding comment to table goal_personal.
        $table->setComment('shezar Goals');

        // Conditionally launch create table for goal_personal.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // If there are no scales, create the default.
        $existing_scales = $DB->count_records('goal_scale_values', array());
        if (empty($existing_scales)) {
            $now = time();

            $todb = new stdClass();
            $todb->name = get_string('goalscale', 'shezar_hierarchy');
            $todb->description = '';
            $todb->usermodified = $USER->id;
            $todb->timemodified = $now;
            $todb->defaultid = 1;
            $scaleid = $DB->insert_record('goal_scale', $todb);

            $goal_scale_vals = array(
                array('name'=>get_string('goalscaledefaultassigned', 'shezar_hierarchy'), 'scaleid' => $scaleid,
                      'sortorder' => 3, 'usermodified' => $USER->id, 'timemodified' => $now),
                array('name'=>get_string('goalscaledefaultstarted', 'shezar_hierarchy'), 'scaleid' => $scaleid,
                      'sortorder' => 2, 'usermodified' => $USER->id, 'timemodified' => $now),
                array('name'=>get_string('goalscaledefaultcompleted', 'shezar_hierarchy'), 'scaleid' => $scaleid,
                      'sortorder' => 1, 'usermodified' => $USER->id, 'timemodified' => $now, 'proficient' => 1)
            );

            // If there are no scale values, create the defaults.
            $existing_values = $DB->count_records('goal_scale_values', array());
            if (empty($existing_values)) {
                foreach ($goal_scale_vals as $svrow) {
                    $todb = new stdClass();
                    foreach ($svrow as $key => $val) {
                        // Insert default goal scale values, if non-existent.
                        $todb->$key = $val;
                    }

                    $svid = $DB->insert_record('goal_scale_values', $todb);
                }
                unset($goal_scale_vals, $scaleid, $svid, $todb);
            }
        }

        shezar_upgrade_mod_savepoint(true, 2013080500, 'shezar_hierarchy');
    }

    // Create goal item history table and add delete flags to goal_record and goal_personal.
    if ($oldversion < 2013080501) {

        // Define field deleted to be added to goal_record.
        $table = new xmldb_table('goal_record');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'scalevalueid');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field deleted to be added to goal_personal.
        $table = new xmldb_table('goal_personal');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'usermodified');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table goal_item_history to be created.
        $table = new xmldb_table('goal_item_history');

        // Adding fields to table goal_item_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scope', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scalevalueid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_item_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table goal_item_history.
        $table->add_index('itemscope', XMLDB_INDEX_NOTUNIQUE, array('scope', 'itemid'));

        // Adding comment to table goal_item_history.
        $table->setComment('Store changes to scalevalueid in goal_record and goal_personal.');

        // Conditionally launch create table for goal_item_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2013080501, 'shezar', 'hierarchy');
    }

    // Add comp record history table.
    if ($oldversion < 2013080502) {

        // Define table comp_record_history to be created.
        $table = new xmldb_table('comp_record_history');

        // Adding fields to table comp_record_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('proficiency', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table comp_record_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table comp_record_history.
        $table->add_index('comprechist_usecom_ix', XMLDB_INDEX_NOTUNIQUE, array('userid', 'competencyid'));

        // Adding comment to table comp_record_history.
        $table->setComment('Store changes to proficiency in comp_record.');

        // Conditionally launch create table for comp_record_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2013080502, 'shezar', 'hierarchy');
    }

    // Drop unused hierarchy fields.
    if ($oldversion < 2013101500) {
        // Define 'icon' field to drop.
        $field = new xmldb_field('icon');

        $table = new xmldb_table('comp_type');
        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('org_type');
        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('pos_type');
        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define 'categoryid' field to drop.
        $field = new xmldb_field('categoryid');

        $table = new xmldb_table('comp_type_info_field');
        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('org_type_info_field');
        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('pos_type_info_field');
        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('goal_type_info_field');
        // Conditionally drop field.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2013101500, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2013103000) {
        // Adding foreign keys.
        $tables = array(
            'comp' => array(
                new xmldb_key('comp_com_fk', XMLDB_KEY_FOREIGN, array('parentid'), 'comp', 'id'),
                new xmldb_key('comp_comtyp_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'comp_type', 'id'),
                new xmldb_key('comp_comfra_fk', XMLDB_KEY_FOREIGN, array('frameworkid'), 'comp_framework', 'id')),
            'comp_record' => array(
                new xmldb_key('compreco_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id'),
                new xmldb_key('compreco_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id'),
                new xmldb_key('compreco_pos_fk', XMLDB_KEY_FOREIGN, array('positionid'), 'pos', 'id'),
                new xmldb_key('compreco_org_fk', XMLDB_KEY_FOREIGN, array('organisationid'), 'org', 'id'),
                new xmldb_key('compreco_ass_fk', XMLDB_KEY_FOREIGN, array('assessorid'), 'user', 'id'),
                new xmldb_key('compreco_pro_fk', XMLDB_KEY_FOREIGN, array('proficiency'), 'comp_scale_values', 'id')),
            'comp_record_history' => array(
                new xmldb_key('comprecohist_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id'),
                new xmldb_key('comprecohist_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id'),
                new xmldb_key('comprecohist_pro_fk', XMLDB_KEY_FOREIGN, array('proficiency'), 'comp_scale_values', 'id')),
            'comp_criteria' => array(
                new xmldb_key('compcrit_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id')),
            'comp_criteria_record' => array(
                new xmldb_key('compcritreco_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id'),
                new xmldb_key('compcritreco_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id'),
                new xmldb_key('compcritreco_ite_fk', XMLDB_KEY_FOREIGN, array('itemid'), 'comp_criteria', 'id')),
            'comp_scale_assignments' => array(
                new xmldb_key('compscalassi_sca_fk', XMLDB_KEY_FOREIGN, array('scaleid'), 'comp_scale', 'id'),
                new xmldb_key('compscalassi_fra_fk', XMLDB_KEY_FOREIGN, array('frameworkid'), 'comp_framework', 'id')),
            'comp_scale_values' => array(
                new xmldb_key('compscalvalu_sca_fk', XMLDB_KEY_FOREIGN, array('scaleid'), 'comp_scale', 'id')),
            'comp_template' => array(
                new xmldb_key('comptemp_fra_fk', XMLDB_KEY_FOREIGN, array('frameworkid'), 'comp_framework', 'id')),
            'comp_template_assignment' => array(
                new xmldb_key('comptempassi_tem_fk', XMLDB_KEY_FOREIGN, array('templateid'), 'comp_template', 'id'),
                new xmldb_key('comptempassi_ins_fk', XMLDB_KEY_FOREIGN, array('instanceid'), 'comp', 'id'),
                new xmldb_key('comptempassi_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id')),
            'comp_type_info_data' => array(
                new xmldb_key('comptypeinfodata_fie_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'comp_type_info_field', 'id'),
                new xmldb_key('comptypeinfodata_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id')),
            'comp_type_info_field' => array(
                new xmldb_key('comptypeinfofiel_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'comp_type', 'id')),
            'pos' => array(
                new xmldb_key('pos_fra_fk', XMLDB_KEY_FOREIGN, array('frameworkid'), 'pos_framework', 'id'),
                new xmldb_key('pos_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'pos_type', 'id'),
                new xmldb_key('pos_par_fk', XMLDB_KEY_FOREIGN, array('parentid'), 'pos', 'id')),
            'pos_competencies' => array(
                new xmldb_key('poscomp_pos_fk', XMLDB_KEY_FOREIGN, array('positionid'), 'pos', 'id'),
                new xmldb_key('poscomp_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id')),
            'pos_type_info_data' => array(
                new xmldb_key('postypeinfodata_fie_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'pos_type_info_field', 'id'),
                new xmldb_key('postypeinfodata_pos_fk', XMLDB_KEY_FOREIGN, array('positionid'), 'pos', 'id')),
            'pos_type_info_field' => array(
                new xmldb_key('postypeinfofiel_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'pos_type', 'id')),
            'org' => array(
                new xmldb_key('org_fra_fk', XMLDB_KEY_FOREIGN, array('frameworkid'), 'org_framework', 'id'),
                new xmldb_key('org_par_fk', XMLDB_KEY_FOREIGN, array('parentid'), 'org', 'id'),
                new xmldb_key('org_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'org_type', 'id')),
            'org_competencies' => array(
                new xmldb_key('orgcomp_org_fk', XMLDB_KEY_FOREIGN, array('organisationid'), 'org', 'id'),
                new xmldb_key('orgcomp_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id')),
            'org_relations' => array(
                new xmldb_key('orgrela_id1_fk', XMLDB_KEY_FOREIGN, array('id1'), 'org', 'id'),
                new xmldb_key('orgrela_id2_fk', XMLDB_KEY_FOREIGN, array('id2'), 'org', 'id')),
            'org_type_info_data' => array(
                new xmldb_key('orgtypeinfodata_fie_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'org_type_info_field', 'id'),
                new xmldb_key('orgtypeinfodata_org_fk', XMLDB_KEY_FOREIGN, array('organisationid'), 'org', 'id')),
            'org_type_info_field' => array(
                new xmldb_key('orgtypeinfofield_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'org_type', 'id')),
            'goal' => array(
                new xmldb_key('goal_par_fk', XMLDB_KEY_FOREIGN, array('parentid'), 'goal', 'id'),
                new xmldb_key('goal_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'goal_type', 'id')),
            'goal_scale' => array(
                new xmldb_key('goalscal_def_fk', XMLDB_KEY_FOREIGN, array('defaultid'), 'goal_scale_values', 'id')),
            'goal_scale_assignments' => array(
                new xmldb_key('goalscalassi_sca_fk', XMLDB_KEY_FOREIGN, array('scaleid'), 'goal_scale', 'id'),
                new xmldb_key('goalscalassi_fra_fk', XMLDB_KEY_FOREIGN, array('frameworkid'), 'goal_framework', 'id')),
            'goal_scale_values' => array(
                new xmldb_key('goalscalvalu_sca_fk', XMLDB_KEY_FOREIGN, array('scaleid'), 'goal_scale', 'id')),
            'goal_type_info_data' => array(
                new xmldb_key('goaltypeinfodata_goa_fk', XMLDB_KEY_FOREIGN, array('goalid'), 'goal', 'id')),
            'goal_type_info_field' => array(
                new xmldb_key('goaltypeinfodata_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'goal_type', 'id')),
            'goal_item_history' => array(
                new xmldb_key('goalitemhist_sca_fk', XMLDB_KEY_FOREIGN, array('scalevalueid'), 'goal_scale_values', 'id')));

        foreach ($tables as $tablename => $keys) {
            $table = new xmldb_table($tablename);
            foreach ($keys as $key) {
                $dbman->add_key($table, $key);
            }
        }
        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2013103000, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2014030400) {
        // Competencies customfield parameters.
        $table = new xmldb_table('comp_type_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'comp_type_info_data', array('id'));
        $table->add_index('value', null, array('value'));

        // Set the comment for the table 'comp_type_info_data_param'.
        $table->setComment('Custom competency fields data parameters');

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Positions customfield parameters.
        $table = new xmldb_table('pos_type_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'pos_type_info_data', array('id'));
        $table->add_index('value', null, array('value'));

        // Set the comment for the table 'pos_type_info_data_param'.
        $table->setComment('Custom position fields data parameters');

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Organisations customfield parameters.
        $table = new xmldb_table('org_type_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'org_type_info_data', array('id'));
        $table->add_index('value', null, array('value'));

        // Set the comment for the table 'org_type_info_data_param'.
        $table->setComment('Custom organisation fields data parameters');

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Goals customfield parameters.
        $table = new xmldb_table('goal_type_info_data_param');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'goal_type_info_data', array('id'));
        $table->add_index('value', null, array('value'));

        // Set the comment for the table 'goal_type_info_data_param'.
        $table->setComment('Custom goal fields data parameters');

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2014030400, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2014041500) {
        // Fix a bad reference in a goal_record foreign key.
        $table = new xmldb_table('goal_record');
        $oldkey = new xmldb_key('scvl_fk', XMLDB_KEY_FOREIGN, array('scalevalueid'), 'goal_scale_value', 'id');
        $newkey = new xmldb_key('scvl_fk', XMLDB_KEY_FOREIGN, array('scalevalueid'), 'goal_scale_values', 'id');
        $dbman->drop_key($table, $oldkey);
        $dbman->add_key($table, $newkey);

        // Fix a bad reference in a comp_record foreign key.
        $table = new xmldb_table('comp_record');
        $oldkey = new xmldb_key('compreco_ass_fk', XMLDB_KEY_FOREIGN, array('assessorid'), 'userid', 'id');
        $newkey = new xmldb_key('compreco_ass_fk', XMLDB_KEY_FOREIGN, array('assessorid'), 'user', 'id');
        $dbman->drop_key($table, $oldkey);
        $dbman->add_key($table, $newkey);

        upgrade_plugin_savepoint(true, 2014041500, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2014120400) {
        // Fix shezar 1.x upgrades.

        // Changing nullability of field path on table comp to null.
        $table = new xmldb_table('comp');
        $field = new xmldb_field('path', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'frameworkid');

        // Launch change of nullability for field path.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field itemtype on table comp_criteria to null.
        $table = new xmldb_table('comp_criteria');
        $field = new xmldb_field('itemtype', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'competencyid');

        // Conditionally launch drop index compcrit_ite_ix.
        $index = new xmldb_index('compcrit_ite_ix', XMLDB_INDEX_NOTUNIQUE, array('itemtype'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Launch change of nullability for field itemtype.
        $dbman->change_field_notnull($table, $field);
        // Readd the index.
        $dbman->add_index($table, $index);

        // Changing nullability of field itemmodule on table comp_criteria to null.
        $table = new xmldb_table('comp_criteria');
        $field = new xmldb_field('itemmodule', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'itemtype');

        // Launch change of nullability for field itemmodule.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field fullname on table comp_framework to not null.
        $table = new xmldb_table('comp_framework');
        $field = new xmldb_field('fullname', XMLDB_TYPE_CHAR, '1024', null, XMLDB_NOTNULL, null, null, 'usermodified');

        // Update existing data to ''.
        $DB->execute("UPDATE {comp_framework} SET fullname = '' WHERE fullname IS NULL");

        // Launch change of nullability for field fullname.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field name on table comp_scale to null.
        $table = new xmldb_table('comp_scale');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'id');

        // Launch change of nullability for field name.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field shortname on table comp_template to null.
        $table = new xmldb_table('comp_template');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fullname');

        // Launch change of nullability for field shortname.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field shortname on table comp_type_info_field to null.
        $table = new xmldb_table('comp_type_info_field');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'id');

        // Launch change of nullability for field shortname.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field datatype on table comp_type_info_field to null.
        $table = new xmldb_table('comp_type_info_field');
        $field = new xmldb_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'typeid');

        // Launch change of nullability for field datatype.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field path on table pos to null.
        $table = new xmldb_table('pos');
        $field = new xmldb_field('path', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'frameworkid');

        // Launch change of nullability for field path.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field typeid on table pos_type_info_field to null.
        $table = new xmldb_table('pos_type_info_field');
        $field = new xmldb_field('typeid', XMLDB_TYPE_INTEGER, '18', null, null, null, null, 'shortname');

        // Launch drop key postypeinfofiel_typ_fk.
        $key = new xmldb_key('postypeinfofiel_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'pos_type', array('id'));
        $dbman->drop_key($table, $key);

        // Launch change of nullability for field typeid.
        $dbman->change_field_notnull($table, $field);

        // Readd the key.
        $dbman->add_key($table, $key);

        // Changing nullability of field shortname on table org_type_info_field to null.
        $table = new xmldb_table('org_type_info_field');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'id');

        // Launch change of nullability for field shortname.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field datatype on table org_type_info_field to null.
        $table = new xmldb_table('org_type_info_field');
        $field = new xmldb_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'typeid');

        // Launch change of nullability for field datatype.
        $dbman->change_field_notnull($table, $field);

        upgrade_plugin_savepoint(true, 2014120400, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2015061800) {
        // Add missing block_shezar_stats records.
        $sql = "SELECT cr.id, cr.userid, cr.competencyid, cr.reaggregate
                FROM {comp_record} cr
                WHERE cr.proficiency IS NOT NULL AND (
                      NOT EXISTS (
                          SELECT 1
                          FROM {block_shezar_stats} bts
                          WHERE bts.userid = cr.userid AND bts.data2 = cr.competencyid)
                )
                ORDER BY cr.userid";
        $comprecords = $DB->get_recordset_sql($sql);
        $newevents = array();
        foreach ($comprecords as $record) {
            $newevent = array();
            $newevent['timestamp'] = $record->reaggregate;
            $newevent['userid'] = $record->userid;
            $newevent['eventtype'] = 4;
            $newevent['data'] = '';
            $newevent['data2'] = $record->competencyid;
            $newevents[] = $newevent;
        }
        $comprecords->close();
        $DB->insert_records('block_shezar_stats', $newevents);
        // Clean up newevents arrays as we don't need them any more.
        unset($newevents);
        unset($newevent);

        upgrade_plugin_savepoint(true, 2015061800, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2015092100) {
        // Define table goal_user_info_data to be created.
        $table = new xmldb_table('goal_user_info_data');

        // Adding fields to table goal_user_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('goal_userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_user_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fieldid', XMLDB_KEY_FOREIGN, array('fieldid'), 'goal_user_info_field', array('id'));
        $table->add_key('goaluserinfodata_goa_fk', XMLDB_KEY_FOREIGN, array('goal_userid'), 'goal', array('id'));

        // Conditionally launch create table for goal_user_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_user_info_data_param to be created.
        $table = new xmldb_table('goal_user_info_data_param');

        // Adding fields to table goal_user_info_data_param.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_user_info_data_param.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('goaluserinfodatapara_dat_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'goal_user_info_data', array('id'));

        // Adding indexes to table goal_user_info_data_param.
        $table->add_index('goaluserinfodatapara_val_ix', XMLDB_INDEX_NOTUNIQUE, array('value'));

        // Conditionally launch create table for goal_user_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_user_info_field to be created.
        $table = new xmldb_table('goal_user_info_field');

        // Adding fields to table goal_user_info_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('typeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('locked', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('required', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('forceunique', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('defaultdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param1', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param2', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param3', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param4', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param5', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024', null, null, null, null);

        // Adding keys to table goal_user_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('goalusertypeinfofield_typ_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'goal_user_type', array('id'));

        // Conditionally launch create table for goal_user_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table goal_user_type_cohort to be created.
        $table = new xmldb_table('goal_user_type_cohort');

        // Adding fields to table goal_user_type_cohort.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('goalid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_user_type_cohort.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cohortid', XMLDB_KEY_FOREIGN, array('cohortid'), 'cohort', array('id'));
        $table->add_key('goalid', XMLDB_KEY_FOREIGN, array('goalid'), 'goal', array('id'));

        // Conditionally launch create table for goal_user_type_cohort.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('goal_user_type');

        // Adding fields to table goal_user_type.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('audience', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table goal_user_type.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for goal_user_type.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field typeid to be added to goal_personal.
        $table = new xmldb_table('goal_personal');
        $field = new xmldb_field('typeid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'deleted');

        // Conditionally launch add field typeid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // We do this inside here to make this block of code safe to execute multiple times.
            // Define key goalpersonal_type_fk (foreign) to be added to goal_personal.
            $key = new xmldb_key('goalpersonal_type_fk', XMLDB_KEY_FOREIGN, array('typeid'), 'goal_user_type', array('id'));
            // Launch add key goalpersonal_type_fk.
            $dbman->add_key($table, $key);
        }

        // Define field visible to be added to goal_personal.
        $table = new xmldb_table('goal_personal');
        $field = new xmldb_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'typeid');

        // Conditionally launch add field visible.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2015092100, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2015112500) {
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'id');

        // Create the shortname field for company goal types.
        $table = new xmldb_table('goal_type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Create the shortname field for personal goal types.
        $table = new xmldb_table('goal_user_type');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2015112500, 'shezar', 'hierarchy');
    }

    if ($oldversion < 2016030300) {

        // TL-8381 - Hierarchy custom field data is not being fully deleted when the Hierarchy item is deleted.
        // This will remove all redundant data from the custom field info_data_param table created prior to this patch.

        // Positions.
        $DB->execute("DELETE FROM {pos_type_info_data_param}
              WHERE dataid NOT IN (SELECT id FROM {pos_type_info_data})");

        // Organisations.
        $DB->execute("DELETE FROM {org_type_info_data_param}
              WHERE dataid NOT IN (SELECT id FROM {org_type_info_data})");

        // Competencies.
        $DB->execute("DELETE FROM {comp_type_info_data_param}
              WHERE dataid NOT IN (SELECT id FROM {comp_type_info_data})");

        // Company Goals
        $DB->execute("DELETE FROM {goal_type_info_data_param}
              WHERE dataid NOT IN (SELECT id FROM {goal_type_info_data})");

        // Hierarchy savepoint reached.
        upgrade_plugin_savepoint(true, 2016030300, 'shezar', 'hierarchy');
    }

    return true;
}
