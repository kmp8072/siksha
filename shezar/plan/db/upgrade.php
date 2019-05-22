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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage plan
 */

/**
 * Local db upgrades for shezar Core
 */

require_once($CFG->dirroot.'/shezar/core/db/utils.php');


/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_shezar_plan_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes


    if ($oldversion < 2013021400) {
        $table = new xmldb_table('dp_template');
        $field = new xmldb_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Make the first record on the list default to keep current
        // default
        $record = $DB->get_record_select('dp_template', 'sortorder = (SELECT MIN(sortorder) FROM {dp_template})');

        if ($record) {
            $todb = new stdClass();
            $todb->id = $record->id;
            $todb->isdefault = 1;
            $DB->update_record('dp_template', $todb);
        }

        // Add column to plan table to record how a plan was created
        $table = new xmldb_table('dp_plan');
        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add template foreign key
        $key = new xmldb_key('templateid', XMLDB_KEY_FOREIGN, array('templateid'), 'dp_template', array('id'));
        $dbman->add_key($table, $key);

        // Add user foreign key
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $dbman->add_key($table, $key);

        shezar_upgrade_mod_savepoint(true, 2013021400, 'shezar_plan');
    }

    if ($oldversion < 2013040200) {
        // Evidence types
        $table = new xmldb_table('dp_evidence_type');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, 'medium');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evidence
        $table = new xmldb_table('dp_plan_evidence');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('evidencetypeid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('evidencelink', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('institution', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('datecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('dpplanev_userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $key = new xmldb_key('evidencetypeid', XMLDB_KEY_FOREIGN, array('evidencetypeid'), 'dp_evidence_type', array('id'));
        $dbman->add_key($table, $key);

        // Evidence + Item component relation
        $table = new xmldb_table('dp_plan_evidence_relation');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $field = new xmldb_field('evidenceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('planid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'evidenceid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'planid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'component');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('evidenceid', XMLDB_KEY_FOREIGN, array('evidenceid'), 'dp_plan_evidence', array('id'));
        $dbman->add_key($table, $key);

        $key = new xmldb_key('planid', XMLDB_KEY_FOREIGN, array('planid'), 'dp_plan', array('id'));
        $dbman->add_key($table, $key);

        $index = new xmldb_index('component', XMLDB_INDEX_NOTUNIQUE, array('planid', 'component', 'itemid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2013040200, 'shezar', 'plan');
    }

    if ($oldversion < 2013040201) {

        // Define field readonly to be added to dp_plan_evidence
        $table = new xmldb_table('dp_plan_evidence');
        $field = new xmldb_field('readonly', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Conditionally launch add field readonly
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // plan savepoint reached
        upgrade_plugin_savepoint(true, 2013040201, 'shezar', 'plan');
    }

    if ($oldversion < 2013103000) {
        // Adding foreign keys.
        $tables = array(
        'dp_permissions' => array(
            new xmldb_key('dpperm_tem_fk', XMLDB_KEY_FOREIGN, array('templateid'), 'dp_template', 'id')),
        'dp_component_settings' => array(
            new xmldb_key('dpcompsett_tem_fk', XMLDB_KEY_FOREIGN, array('templateid'), 'dp_template', 'id')),
        'dp_course_settings' => array(
            new xmldb_key('dpcoursett_tem_fk', XMLDB_KEY_FOREIGN_UNIQUE, array('templateid'), 'dp_template', 'id'),
            new xmldb_key('dpcoursett_pri_fk', XMLDB_KEY_FOREIGN, array('priorityscale'), 'dp_priority_scale', 'id')),
        'dp_plan_course_assign' => array(
            new xmldb_key('dpplancourassi_pla_fk', XMLDB_KEY_FOREIGN, array('planid'), 'dp_plan', 'id'),
            new xmldb_key('dpplancourassi_cou_fk', XMLDB_KEY_FOREIGN, array('courseid'), 'course', 'id'),
            new xmldb_key('dpplancourassi_pri_fk', XMLDB_KEY_FOREIGN, array('priority'), 'dp_priority_scale_value', 'id'),
            new xmldb_key('dpplancourassi_com_fk', XMLDB_KEY_FOREIGN, array('completionstatus'), 'course_completions', 'id')),
        'dp_plan_competency_assign' => array(
            new xmldb_key('dpplancompassi_pla_fk', XMLDB_KEY_FOREIGN, array('planid'), 'dp_plan', 'id'),
            new xmldb_key('dpplancompassi_com_fk', XMLDB_KEY_FOREIGN, array('competencyid'), 'comp', 'id'),
            new xmldb_key('dpplancompassi_pri_fk', XMLDB_KEY_FOREIGN, array('priority'), 'dp_priority_scale_value', 'id'),
            new xmldb_key('dpplancompassi_sca_fk', XMLDB_KEY_FOREIGN, array('scalevalueid'), 'comp_scale_values', 'id')),
        'dp_competency_settings' => array(
            new xmldb_key('dpcompsett_tem_fk', XMLDB_KEY_FOREIGN_UNIQUE, array('templateid'), 'dp_template', 'id'),
            new xmldb_key('dpcompsett_pri_fk', XMLDB_KEY_FOREIGN, array('priorityscale'), 'dp_priority_scale', 'id')),
        'dp_priority_scale' => array(
            new xmldb_key('dpprioscal_def_fk', XMLDB_KEY_FOREIGN, array('defaultid'), 'dp_priority_scale_value', 'id'),
            new xmldb_key('dpprioscal_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id')),
        'dp_priority_scale_value' => array(
            new xmldb_key('dpprioscalvalu_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id'),
            new xmldb_key('dpprioscalvalu_pri_fk', XMLDB_KEY_FOREIGN, array('priorityscaleid'), 'dp_priority_scale', 'id')),
        'dp_objective_scale' => array(
            new xmldb_key('dpobjescal_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id'),
            new xmldb_key('dpobjescal_def_fk', XMLDB_KEY_FOREIGN, array('defaultid'), 'dp_objective_scale_value', 'id')),
        'dp_objective_scale_value' => array(
            new xmldb_key('dpobjescalvalu_obj_fk', XMLDB_KEY_FOREIGN, array('objscaleid'), 'dp_objective_scale', 'id'),
            new xmldb_key('dpobjescalvalu_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id')),
        'dp_plan_history' => array(
            new xmldb_key('dpplanhist_pla_fk', XMLDB_KEY_FOREIGN, array('planid'), 'dp_plan', 'id'),
            new xmldb_key('dpplanhist_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id')),
        'dp_plan_evidence' => array(
            new xmldb_key('dpplanevid_user_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id'),
            new xmldb_key('dpplanevid_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', 'id')),
        'dp_objective_settings' => array(
            new xmldb_key('dpobjesett_tem_fk', XMLDB_KEY_FOREIGN, array('templateid'), 'dp_template', 'id'),
            new xmldb_key('dpobjesett_pri_fk', XMLDB_KEY_FOREIGN, array('priorityscale'), 'dp_priority_scale', 'id'),
            new xmldb_key('dpobjesett_obj_fk', XMLDB_KEY_FOREIGN, array('objectivescale'), 'dp_objective_scale', 'id')),
        'dp_plan_objective' => array(
            new xmldb_key('dpplanobje_pla_fk', XMLDB_KEY_FOREIGN, array('planid'), 'dp_plan', 'id'),
            new xmldb_key('dpplanobje_pri_fk', XMLDB_KEY_FOREIGN, array('priority'), 'dp_priority_scale_value', 'id'),
            new xmldb_key('dpplanobje_sca_fk', XMLDB_KEY_FOREIGN, array('scalevalueid'), 'dp_objective_scale_value', 'id')),
        'dp_plan_settings' => array(
            new xmldb_key('dpplansett_tem_fk', XMLDB_KEY_FOREIGN_UNIQUE, array('templateid'), 'dp_template', 'id')),
        'dp_plan_program_assign' => array(
            new xmldb_key('dpplanprogassi_pla_fk', XMLDB_KEY_FOREIGN, array('planid'), 'dp_plan', 'id'),
            new xmldb_key('dpplanprogassi_pro_fk', XMLDB_KEY_FOREIGN, array('programid'), 'prog', 'id'),
            new xmldb_key('dpplanprogassi_pri_fk', XMLDB_KEY_FOREIGN, array('priority'), 'dp_priority_scale_value', 'id')),
        'dp_program_settings' => array(
            new xmldb_key('dpprogsett_tem_fk', XMLDB_KEY_FOREIGN_UNIQUE, array('templateid'), 'dp_template', 'id')),
        'dp_evidence_type' => array(
            new xmldb_key('dpevidtype_use_fk', XMLDB_KEY_FOREIGN, array('usermodified'), 'user', 'id')));

        foreach ($tables as $tablename => $keys) {
            $table = new xmldb_table($tablename);
            foreach ($keys as $key) {
                $dbman->add_key($table, $key);
            }
        }

        // Plan savepoint reached.
        upgrade_plugin_savepoint(true, 2013103000, 'shezar', 'plan');
    }

    if ($oldversion < 2013111500) {
        // Conditionally remove some fields that are no longer used and no longer exist in the install.xml.

        $table = new xmldb_table('dp_plan_evidence');
        $fields = array('planid', 'type', 'filepath');

        // Can't drop the planid field while this index still exists, so bombs away.
        $index = new xmldb_index('dpplanevid_pla', XMLDB_INDEX_NOTUNIQUE, array('planid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        foreach ($fields as $fieldname) {
            $field = new xmldb_field($fieldname);

            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2013111500, 'shezar', 'plan');
    }

    if ($oldversion < 2014030600) {
        // Add reason for denying or approving a program extension.
        $table = new xmldb_table('dp_plan_history');
        $field = new xmldb_field('reasonfordecision', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'reason');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('dp_plan_competency_assign');
        $field = new xmldb_field('reasonfordecision', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'approved');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('dp_plan_course_assign');
        $field = new xmldb_field('reasonfordecision', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'approved');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('dp_plan_program_assign');
        $field = new xmldb_field('reasonfordecision', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'approved');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('dp_plan_objective');
        $field = new xmldb_field('reasonfordecision', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'approved');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2014030600, 'shezar', 'plan');
    }

    if ($oldversion < 2014082200) {
        // Make sure there are no nulls before preventing nulls.
        $DB->set_field_select('dp_plan_evidence', 'name', '', "name IS NULL");

        // Fix nulls before setting to nul null.
        $DB->execute("UPDATE {dp_plan_evidence} SET name = '' WHERE name IS NULL");

        // Changing nullability of field name on table dp_plan_evidence to not null.
        $table = new xmldb_table('dp_plan_evidence');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch change of nullability for field name.
        $dbman->change_field_notnull($table, $field);

        // Plan savepoint reached.
        upgrade_plugin_savepoint(true, 2014082200, 'shezar', 'plan');
    }

    if ($oldversion < 2014120400) {
        // Fix shezar 1.x upgrade.

        // Changing nullability of field component on table dp_component_settings to null.
        $table = new xmldb_table('dp_component_settings');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'templateid');

        // Conditionally launch drop index templateid-component.
        $index = new xmldb_index('templateid-component', XMLDB_INDEX_UNIQUE, array('templateid', 'component'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Launch change of nullability for field component.
        $dbman->change_field_notnull($table, $field);

        // Readd index.
        $dbman->add_index($table, $index);

        // Changing the default of field reason on table dp_plan_history to drop it.
        $table = new xmldb_table('dp_plan_history');
        $field = new xmldb_field('reason', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null, 'status');

        // Launch change of default for field reason.
        $dbman->change_field_default($table, $field);

        // Define field completionstatus to be dropped from dp_plan_program_assign.
        $table = new xmldb_table('dp_plan_program_assign');
        $field = new xmldb_field('completionstatus');

        // Conditionally launch drop field completionstatus.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Plan savepoint reached.
        upgrade_plugin_savepoint(true, 2014120400, 'shezar', 'plan');
    }

    if ($oldversion < 2015052100) {
        global $TEXTAREA_OPTIONS;

        // Delete all evidence items linked to deleted / non-existant users.
        $sql = "SELECT ev.id
                  FROM {dp_plan_evidence} ev
             LEFT JOIN {user} u
                    ON ev.userid = u.id
                 WHERE u.id IS NULL
                 OR u.deleted = 1";
        $evidenceitems = $DB->get_records_sql($sql);

        $transaction = $DB->start_delegated_transaction();

        foreach ($evidenceitems as $evidence) {
            // Delete the evidence item.
            $DB->delete_records('dp_plan_evidence', array('id' => $evidence->id));

            // Delete any evidence relations.
            $DB->delete_records('dp_plan_evidence_relation', array('evidenceid' => $evidence->id));

            // Delete any linked files.
            $fs = get_file_storage();
            $fs->delete_area_files($TEXTAREA_OPTIONS['context']->id, 'shezar_plan', 'attachment', $evidence->id);
        }

        $transaction->allow_commit();

        // Plan savepoint reached.
        upgrade_plugin_savepoint(true, 2015052100, 'shezar', 'plan');
    }

    if ($oldversion < 2015061600) {
        // Update the default sort column for RoL: Certifications embedded reports.

        $params = array('embedded' => 1, 'shortname' => 'plan_certifications', 'defaultsortcolumn' => 'certification_fullnamelink');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'base_fullnamelink', $params);

        upgrade_plugin_savepoint(true, 2015061600, 'shezar', 'plan');
    }

    if ($oldversion < 2016021501) {
        // Define table dp_plan_evidence_info_field to be created.
        $table = new xmldb_table('dp_plan_evidence_info_field');

        // Adding fields to table dp_plan_evidence_info_field.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('locked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('forceunique', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('defaultdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param1', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param2', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param3', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param4', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('param5', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table dp_plan_evidence_info_field.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for dp_plan_evidence_info_field.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table dp_plan_evidence_info_data to be created.
        $table = new xmldb_table('dp_plan_evidence_info_data');

        // Adding fields to table dp_plan_evidence_info_data.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('evidenceid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table dp_plan_evidence_info_data.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dpplanevidenceinfodata_fie_fk', XMLDB_KEY_FOREIGN, array('fieldid'), 'dp_plan_evidence_info_field', array('id'));
        $table->add_key('dpplanevidenceinfodata_evi_fk', XMLDB_KEY_FOREIGN, array('evidenceid'), 'dp_plan_evidence', array('id'));

        // Conditionally launch create table for dp_plan_evidence_info_data.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table dp_plan_evidence_info_data_param to be created.
        $table = new xmldb_table('dp_plan_evidence_info_data_param');

        // Adding fields to table dp_plan_evidence_info_data_param.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table dp_plan_evidence_info_data_param.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dpplanevidenceinfodata_para_dat_fk', XMLDB_KEY_FOREIGN, array('dataid'), 'dp_plan_evidence_info_data', array('id'));

        // Adding indexes to table dp_plan_evidence_info_data_param.
        $table->add_index('dpplanevidenceinfodata_val_ix', XMLDB_INDEX_NOTUNIQUE, array('value'));

        // Conditionally launch create table for dp_plan_evidence_info_data_param.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2016021501, 'shezar', 'plan');
    }

    if ($oldversion < 2016021502) {
        // Migrate evidence description field to a custom field.
        // This upgrade step must be safe to run multiple times.
        $table = new xmldb_table('dp_plan_evidence');
        $field = new xmldb_field('description');

        // Check if the field exists, if it does this migration has been performed already.
        if ($dbman->field_exists($table, $field)) {
            // Order of procession:
            //   1. Create the new custom field.
            //   2. Migrate evidence descriptions to the custom field (incl. files).
            //   3. Drop the description field.
            //   4. Rename description column in report builder reports to use the custom field.
            //   5. Update all saved searches.

            // Create the custom field.
            $data = new stdClass();
            $data->fullname = get_string('evidencedescription', 'shezar_plan');
            $data->shortname = str_replace(' ', '', get_string('evidencedescriptionshort', 'shezar_plan'));
            $data->datatype = 'textarea';
            $data->sortorder = $DB->count_records('dp_plan_evidence_info_field') + 1;
            $data->hidden = 0;
            $data->locked = 0;
            $data->required = 0;
            $data->forceunique = 0;
            $data->param1 = 30;
            $data->param2 = 10;
            $fieldid = $DB->insert_record('dp_plan_evidence_info_field', $data);

            // Migrate data.
            $fs = get_file_storage();
            $rs = $DB->get_recordset('dp_plan_evidence', null, '', 'id, description');
            $syscontextid = context_system::instance()->id;
            foreach ($rs as $record) {
                if (!empty($record->description)) {
                    // Insert description into the custom field.
                    $data = new stdClass();
                    $data->fieldid = $fieldid;
                    $data->evidenceid = $record->id;
                    $data->data = $record->description;
                    $newitemid = $DB->insert_record('dp_plan_evidence_info_data', $data);

                    // Process any files that are contained on the description.
                    $files = $fs->get_area_files($syscontextid, 'shezar_plan', 'dp_plan_evidence', $record->id);
                    foreach ($files as $orgfile) {

                        if ($orgfile->get_filename() === ".") {
                            continue;
                        }

                        $newfile = array(
                            'component' => 'shezar_customfield',
                            'filearea' => 'evidence',
                            'itemid' => $newitemid
                        );
                        $fs->create_file_from_storedfile($newfile, $orgfile);
                        // Noting we delete individually as if you call delete_area_files it just fetches them and calls
                        // delete individually.
                        $orgfile->delete();
                    }
                }
            }
            $rs->close();
            unset($syscontextid);

            // Drop the description field.
            $dbman->drop_field($table, $field);

            // Update reports to use new custom field.
            reportbuilder_rename_data('columns', 'dp_evidence', 'evidence', 'description', 'dp_plan_evidence', 'custom_field_' . $fieldid);
            reportbuilder_rename_data('filters', 'dp_evidence', 'evidence', 'description', 'dp_plan_evidence', 'custom_field_' . $fieldid);

            // Update saved rb searches to use new custom field.
            $params = array('source' => 'dp_evidence');
            $sql = "SELECT rbs.id, rbs.search
                      FROM {report_builder_saved} rbs
                      JOIN {report_builder} rb ON rbs.reportid = rb.id
                     WHERE rb.source = :source";
            $rs = $DB->get_records_sql($sql, $params);
            foreach ($rs as $record) {
                $saveditems = unserialize($record->search);
                if (isset($saveditems['evidence-description'])) {
                    $saveditems['dp_plan_evidence-custom_field_' . $fieldid] = $saveditems['evidence-description'];
                    unset ($saveditems['evidence-description']);

                    // Update record.
                    $data = new stdClass();
                    $data->id = $record->id;
                    $data->search = serialize($saveditems);
                    $DB->update_record('report_builder_saved', $data);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2016021502, 'shezar', 'plan');
    }

    if ($oldversion < 2016021503) {
        // Migrate evidence link field to custom field.
        // This upgrade step must be safe to run multiple times.
        $table = new xmldb_table('dp_plan_evidence');
        $field = new xmldb_field('evidencelink');
        if ($dbman->field_exists($table, $field)) {
            // Order of procession:
            //   1. Create the new custom field.
            //   2. Migrate evidence link to the custom field.
            //   3. Drop the evidencelink field.
            //   4. Rename evidencelink column in report builder reports to use the custom field.
            // Note: this field does not have a filter in report sources. No need to look at saved searches.

            // Create the custom field.
            $data = new stdClass();
            $data->fullname = get_string('evidencelink', 'shezar_plan');
            $data->shortname = str_replace(' ', '', get_string('evidencelinkshort', 'shezar_plan'));
            $data->datatype = 'url';
            $data->sortorder = $DB->count_records('dp_plan_evidence_info_field') + 1;
            $data->hidden = 0;
            $data->locked = 0;
            $data->required = 0;
            $data->forceunique = 0;
            $data->param1 = '';
            $data->param2 = 1;
            $fieldid = $DB->insert_record('dp_plan_evidence_info_field', $data);

            // Migrate data, we have to go one by one due to json_encode.
            $rs = $DB->get_recordset('dp_plan_evidence', null, '', 'id, evidencelink');
            foreach ($rs as $record) {
                if (!empty($record->evidencelink)) {
                    $data = new stdClass();
                    $data->fieldid = $fieldid;
                    $data->evidenceid = $record->id;
                    $data->data = json_encode(array('url' => $record->evidencelink));
                    $DB->insert_record('dp_plan_evidence_info_data', $data, false);
                }
            }
            $rs->close();
            // Drop the evidencelink custom field.
            $dbman->drop_field($table, $field);

            // Update reports to use new custom field.
            reportbuilder_rename_data('columns', 'dp_evidence', 'evidence', 'evidencelink', 'dp_plan_evidence', 'custom_field_' . $fieldid);
            reportbuilder_rename_data('filters', 'dp_evidence', 'evidence', 'evidencelink', 'dp_plan_evidence', 'custom_field_' . $fieldid);
        }

        upgrade_plugin_savepoint(true, 2016021503, 'shezar', 'plan');
    }

    if ($oldversion < 2016021504) {
        // Migrate evidence institution field to custom field.
        // This upgrade step must be safe to run multiple times.
        $table = new xmldb_table('dp_plan_evidence');
        $field = new xmldb_field('institution');
        if ($dbman->field_exists($table, $field)) {
            // Order of procession:
            //   1. Create the new custom field.
            //   2. Migrate evidence institution to the custom field.
            //   3. Drop the institution field.
            //   4. Rename institution column in report builder reports to use the custom field.
            //   5. Update embedded reports.
            // Note: this field does not have a filter in report sources. No need to look at saved searches.

            // Create the custom field.
            $data = new stdClass();
            $data->fullname = get_string('evidenceinstitution', 'shezar_plan');
            $data->shortname = str_replace(' ', '', get_string('evidenceinstitutionshort', 'shezar_plan'));
            $data->datatype = 'text';
            $data->sortorder = $DB->count_records('dp_plan_evidence_info_field') + 1;
            $data->hidden = 0;
            $data->locked = 0;
            $data->required = 0;
            $data->forceunique = 0;
            $data->param1 = 30;
            $data->param2 = 2048;
            $fieldid = $DB->insert_record('dp_plan_evidence_info_field', $data);

            // Migrate data.
            $sql = "INSERT INTO {dp_plan_evidence_info_data}
                                (evidenceid, data, fieldid)
                    SELECT id, institution, :fieldid
                    FROM {dp_plan_evidence}";
            $params = array('fieldid' => $fieldid);
            $DB->execute($sql, $params);
            $dbman->drop_field($table, $field);

            // Update Institution to new custom field.
            reportbuilder_rename_data('columns', 'dp_evidence', 'evidence', 'institution', 'dp_plan_evidence', 'custom_field_' . $fieldid);
            reportbuilder_rename_data('filters', 'dp_evidence', 'evidence', 'institution', 'dp_plan_evidence', 'custom_field_' . $fieldid);
        }

        upgrade_plugin_savepoint(true, 2016021504, 'shezar', 'plan');
    }

    if ($oldversion < 2016021505) {
        // Migrate evidence date completed field to custom field.
        // This upgrade step must be safe to run multiple times.
        $table = new xmldb_table('dp_plan_evidence');
        $field = new xmldb_field('datecompleted');
        if ($dbman->field_exists($table, $field)) {
            // Order of procession:
            //   1. Create the new custom field.
            //   2. Migrate evidence datecompleted to the custom field.
            //   3. Drop the datecompleted field.
            //   4. Rename datecompleted column in report builder reports to use the custom field.
            // Note: this field does not have a filter in report sources. No need to look at saved searches.

            // Create the custom field.
            $data = new stdClass();
            $data->fullname = get_string('evidencedatecompleted', 'shezar_plan');
            $data->shortname = str_replace(' ', '', get_string('evidencedatecompletedshort', 'shezar_plan'));
            $data->datatype = 'datetime';
            $data->sortorder = $DB->count_records('dp_plan_evidence_info_field') + 1;
            $data->hidden = 0;
            $data->locked = 0;
            $data->required = 0;
            $data->forceunique = 0;
            $data->defaultdata = 0;
            $data->param1 = 1900;
            $data->param2 = 2050;
            $fieldid = $DB->insert_record('dp_plan_evidence_info_field', $data);

            // Migrate data.
            $sql = "INSERT INTO {dp_plan_evidence_info_data}
                                (evidenceid, data, fieldid)
                    SELECT id, datecompleted, :fieldid
                    FROM {dp_plan_evidence}";

            $params = array('fieldid' => $fieldid);
            $DB->execute($sql, $params);
            $dbman->drop_field($table, $field);

            // Update embedded report, datecompleted to new custom field.
            reportbuilder_rename_data('columns', 'dp_evidence', 'evidence', 'datecompleted', 'dp_plan_evidence', 'custom_field_' . $fieldid);
            reportbuilder_rename_data('filters', 'dp_evidence', 'evidence', 'datecompleted', 'dp_plan_evidence', 'custom_field_' . $fieldid);
        }

        upgrade_plugin_savepoint(true, 2016021505, 'shezar', 'plan');
    }

    if ($oldversion < 2016021506) {
        // Migrate evidence file attachments to custom field.
        // This upgrade step must be safe to run multiple times.
        if (!get_config('shezar_plan', 'evidence_files_migrated_to_cf')) {
            // Order of procession:
            //   1. Create the new custom field.
            //   2. Migrate evidence datecompleted to the custom field.
            //   3. Drop the datecompleted field.
            //   4. Rename datecompleted column in report builder reports to use the custom field.
            // Note: this field does not have a filter in report sources. No need to look at saved searches.

            // Create the custom field.
            $data = new stdClass();
            $data->fullname = get_string('evidencefileattachments', 'shezar_plan');
            $data->shortname = str_replace(' ', '', get_string('evidencefileattachmentsshort', 'shezar_plan'));
            $data->datatype = 'file';
            $data->sortorder = $DB->count_records('dp_plan_evidence_info_field') + 1;
            $data->hidden = 0;
            $data->locked = 0;
            $data->required = 0;
            $data->forceunique = 0;
            $data->defaultdata = 0;
            $fieldid = $DB->insert_record('dp_plan_evidence_info_field', $data);

            // Create info_data record.
            $sql = "INSERT INTO {dp_plan_evidence_info_data}
                                (evidenceid, fieldid)
                    SELECT id, :fieldid
                    FROM {dp_plan_evidence} ";
            $params = array('fieldid' => $fieldid);
            $DB->execute($sql, $params);

            // Set data value to match id.
            $sql = "UPDATE {dp_plan_evidence_info_data}
                SET data = id
                WHERE fieldid = :fieldid";
            $params = array('fieldid' => $fieldid);
            $DB->execute($sql, $params);

            // Update files.
            $fs = get_file_storage();
            $params = array('fieldid' => $fieldid);
            $rs = $DB->get_recordset('dp_plan_evidence_info_data', $params);
            foreach ($rs as $cfdata) {
                $files = $fs->get_area_files(context_system::instance()->id, 'shezar_plan', 'attachment', $cfdata->evidenceid);
                foreach ($files as $orgfile) {

                    if ($orgfile->get_filename() === ".") {
                        continue;
                    }

                    $newfile = array(
                        'component' => 'shezar_customfield',
                        'filearea' => 'evidence_filemgr',
                        'itemid' => $cfdata->id
                    );
                    $fs->create_file_from_storedfile($newfile, $orgfile);
                    $orgfile->delete();
                }
            }
            $rs->close();

            // Update reports to use new custom field.
            reportbuilder_rename_data('columns', 'dp_evidence', 'evidence', 'attachmentlink', 'dp_plan_evidence', 'custom_field_' . $fieldid);
            reportbuilder_rename_data('filters', 'dp_evidence', 'evidence', 'attachmentlink', 'dp_plan_evidence', 'custom_field_' . $fieldid);

            // Set the config so that we know we have completed this step and never need to run it again.
            set_config('evidence_files_migrated_to_cf', time(), 'shezar_plan');
        }
        upgrade_plugin_savepoint(true, 2016021506, 'shezar', 'plan');
    }

    if ($oldversion < 2016021507) {
        // Clean up deleted courses still assigned to plans.
        $transaction = $DB->start_delegated_transaction();

        $sql = "DELETE FROM {dp_plan_course_assign} WHERE courseid NOT IN (SELECT id FROM {course})";

        $DB->execute($sql);

        $sql = "DELETE FROM {dp_plan_component_relation} WHERE
                    (component1 = 'course' AND itemid1 NOT IN (SELECT id FROM {dp_plan_course_assign}))
                OR
                    (component2 = 'course' AND itemid2 NOT IN (SELECT id FROM {dp_plan_course_assign}))";

        $DB->execute($sql);

        $transaction->allow_commit();

        upgrade_plugin_savepoint(true, 2016021507, 'shezar', 'plan');
    }

    return true;
}
