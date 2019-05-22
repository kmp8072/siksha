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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage shezar_sync
 */

require_once($CFG->dirroot.'/admin/tool/shezar_sync/sources/classes/source.pos.class.php');
require_once($CFG->dirroot.'/admin/tool/shezar_sync/lib.php');
require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot.'/admin/tool/shezar_sync/sources/databaselib.php');

class shezar_sync_source_pos_database extends shezar_sync_source_pos {

    function config_form(&$mform) {
        global $PAGE;

        $this->config->import_idnumber = "1";
        $this->config->import_fullname = "1";
        $this->config->import_frameworkidnumber = "1";
        $this->config->import_timemodified = "1";

        // Display required db table columns
        $fieldmappings = array();

        foreach ($this->fields as $field) {
            if (!empty($this->config->{'fieldmapping_'.$field})) {
                $fieldmappings[$field] = $this->config->{'fieldmapping_'.$field};
            }
        }
        foreach ($this->customfields as $key => $field) {
            if (!empty($this->config->{'fieldmapping_'.$key})) {
                $fieldmappings[$key] = $this->config->{'fieldmapping_'.$key};
            }
        }

        $dbstruct = array();
        foreach ($this->fields as $field) {
            if (!empty($this->config->{'import_'.$field})) {
                $dbstruct[] = !empty($fieldmappings[$field]) ? $fieldmappings[$field] : $field;
            }
        }
        foreach (array_keys($this->customfields) as $field) {
            if (!empty($this->config->{'import_'.$field})) {
                $dbstruct[] = !empty($fieldmappings[$field]) ? $fieldmappings[$field] : $field;
            }
        }

        $db_table = isset($this->config->{'database_dbtable'}) ? $this->config->{'database_dbtable'} : false;

        if (!$db_table) {
            $description = get_string('dbconnectiondetails', 'tool_shezar_sync');
        } else {
            $dbstruct = implode(', ', $dbstruct);
            $description = get_string('tablemustincludexdb', 'tool_shezar_sync', $db_table);
            $description .= html_writer::empty_tag('br') . $dbstruct;
        }

        $mform->addElement('html', html_writer::tag('div', html_writer::tag('p', $description), array('class' => 'informationbox')));

        // Empty or null field info.
        if ($db_table) {
            $info = get_string('databaseemptynullinfo', 'tool_shezar_sync');
            $mform->addElement('html', html_writer::tag('div', html_writer::tag('p', $info), array('class' => "alert alert-warning")));
        }

        $db_options = get_installed_db_drivers();

        // Database details
        $mform->addElement('select', 'database_dbtype', get_string('dbtype', 'tool_shezar_sync'), $db_options);
        $mform->addElement('text', 'database_dbname', get_string('dbname', 'tool_shezar_sync'));
        $mform->addRule('database_dbname', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbname', PARAM_RAW); // There is no safe cleaning of connection strings.
        $mform->addElement('text', 'database_dbhost', get_string('dbhost', 'tool_shezar_sync'));
        $mform->setType('database_dbhost', PARAM_HOST);
        $mform->addElement('text', 'database_dbuser', get_string('dbuser', 'tool_shezar_sync'));
        $mform->addRule('database_dbuser', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbuser', PARAM_ALPHANUMEXT);
        $mform->addElement('password', 'database_dbpass', get_string('dbpass', 'tool_shezar_sync'));
        $mform->setType('database_dbpass', PARAM_RAW);
        $mform->addElement('text', 'database_dbport', get_string('dbport', 'tool_shezar_sync'));
        $mform->setType('database_dbport', PARAM_INT);

        // Table name
        $mform->addElement('text', 'database_dbtable', get_string('dbtable', 'tool_shezar_sync'));
        $mform->addRule('database_dbtable', get_string('err_required', 'form'), 'required');
        $mform->setType('database_dbtable', PARAM_ALPHANUMEXT);

        $mform->addElement('button', 'database_dbtest', get_string('dbtestconnection', 'tool_shezar_sync'));

        // Javascript include
        local_js(array(shezar_JS_DIALOG));

        $PAGE->requires->strings_for_js(array('dbtestconnectsuccess', 'dbtestconnectfail'), 'tool_shezar_sync');

        $jsmodule = array(
                'name' => 'shezar_syncdatabaseconnect',
                'fullpath' => '/admin/tool/shezar_sync/sources/sync_database.js',
                'requires' => array('json', 'shezar_core'));

        $PAGE->requires->js_init_call('M.shezar_syncdatabaseconnect.init', null, false, $jsmodule);

        parent::config_form($mform);
    }

    function config_save($data) {
        // Check database connection when saving
        try {
            setup_sync_DB($data->{'database_dbtype'}, $data->{'database_dbhost'}, $data->{'database_dbname'},
                $data->{'database_dbuser'}, $data->{'database_dbpass'}, array('dbport' => $data->{'database_dbport'}));
        } catch (Exception $e) {
            shezar_set_notification(get_string('cannotconnectdbsettings', 'tool_shezar_sync'), qualified_me());
        }

        $this->set_config('database_dbtype', $data->{'database_dbtype'});
        $this->set_config('database_dbname', $data->{'database_dbname'});
        $this->set_config('database_dbhost', $data->{'database_dbhost'});
        $this->set_config('database_dbuser', $data->{'database_dbuser'});
        $this->set_config('database_dbpass', $data->{'database_dbpass'});
        $this->set_config('database_dbport', $data->{'database_dbport'});
        $this->set_config('database_dbtable', $data->{'database_dbtable'});

        parent::config_save($data);
    }

    function import_data($temptable) {
        global $CFG, $DB; // Careful using this in here as we have 2 database connections

        // Get database config
        $dbtype = $this->config->{'database_dbtype'};
        $dbname = $this->config->{'database_dbname'};
        $dbhost = $this->config->{'database_dbhost'};
        $dbuser = $this->config->{'database_dbuser'};
        $dbpass = $this->config->{'database_dbpass'};
        $dbport = $this->config->{'database_dbport'};
        $db_table = $this->config->{'database_dbtable'};

        try {
            $database_connection = setup_sync_DB($dbtype, $dbhost, $dbname, $dbuser, $dbpass, array('dbport' => $dbport));
        } catch (Exception $e) {
            $this->addlog(get_string('databaseconnectfail', 'tool_shezar_sync'), 'error', 'importdata');
        }

        // Get list of fields to be imported
        $fields = array();
        foreach ($this->fields as $f) {
            if (!empty($this->config->{'import_'.$f})) {
                $fields[] = $f;
            }
        }

        // Same for customfields
        foreach ($this->customfields as $name => $value) {
            if (!empty($this->config->{'import_'.$name})) {
                $fields[] = $name;
            }
        }

        // Sort out field mappings
        $fieldmappings = array();
        foreach ($fields as $i => $f) {
            if (empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$f] = $f;
            } else {
                $fieldmappings[$f] = $this->config->{'fieldmapping_'.$f};
            }
        }

        // Finally, perform externaldb to shezar db field mapping
        foreach ($fields as $i => $f) {
            if (in_array($f, array_keys($fieldmappings))) {
                $fields[$i] = $fieldmappings[$f];
            }
        }

        // Check that all fields exists in database
        foreach ($fields as $field) {
            try {
                $database_connection->get_field_sql("SELECT $field from $db_table", array(), IGNORE_MULTIPLE);
            } catch (Exception $e) {
                $this->addlog(get_string('dbmissingcolumnx', 'tool_shezar_sync', $field), 'error', 'importdata');
                return false;
            }
        }

        unset($fieldmappings);

        // Populate temp sync table from remote database
        $now = time();
        $datarows = array();  // holds rows of data
        $rowcount = 0;
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'shezar_core');

        $columns = implode(', ', $fields);
        $fetch_sql = 'SELECT ' . $columns . ' FROM ' . $db_table;
        $data = $database_connection->get_recordset_sql($fetch_sql);

        foreach ($data as $row) {
            // Setup a db row
            $extdbrow = array_combine($fields, (array)$row);
            $dbrow = array();

            foreach ($this->fields as $field) {
                if (!empty($this->config->{'import_'.$field})) {
                    if (!empty($this->config->{'fieldmapping_'.$field})) {
                        $dbrow[$field] = $extdbrow[$this->config->{'fieldmapping_'.$field}];
                    } else {
                        $dbrow[$field] = $extdbrow[$field];
                    }
                }
            }

            $dbrow['parentidnumber'] = !empty($dbrow['parentidnumber']) ? $dbrow['parentidnumber'] : '0';
            $dbrow['parentidnumber'] = $dbrow['parentidnumber'] == $dbrow['idnumber'] ? '0' : $dbrow['parentidnumber'];

            if ($this->config->{'import_typeidnumber'} == '0') {
                unset($dbrow['typeidnumber']);
            } else {
                $dbrow['typeidnumber'] = !empty($dbrow['typeidnumber']) ? $dbrow['typeidnumber'] : '0';
            }

            if (empty($extdbrow['timemodified'])) {
                $dbrow['timemodified'] = $now;
            } else {
                //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = shezar_date_parse_from_format($csvdateformat, trim($extdbrow['timemodified']), true);
                if ($parsed_date) {
                    $dbrow['timemodified'] = $parsed_date;
                }
            }
            // Custom fields are special - needs to be json-encoded
            if (!empty($this->customfields)) {
                $cfield_data = array();
                foreach (array_keys($this->customfields) as $cf) {
                    if (!empty($this->config->{'import_'.$cf})) {
                        if (!empty($this->config->{'fieldmapping_'.$cf})) {
                            $value = trim($extdbrow[$this->config->{'fieldmapping_'.$cf}]);
                        } else {
                            $value = trim($extdbrow[$cf]);
                        }
                        if (!empty($value)) {
                            //get shortname and check if we need to do field type processing
                            $shortname = str_replace("customfield_", "", $cf);
                            $datatype = $DB->get_field('pos_type_info_field', 'datatype', array('shortname' => $shortname));
                            switch ($datatype) {
                                case 'datetime':
                                    //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                                    $parsed_date = shezar_date_parse_from_format($csvdateformat, $value, true);
                                    if ($parsed_date) {
                                        $value = $parsed_date;
                                    }
                                    break;
                                case 'date':
                                    //try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                                    $parsed_date = shezar_date_parse_from_format($csvdateformat, $value, true, 'UTC');
                                    if ($parsed_date) {
                                        $value = $parsed_date;
                                    }
                                    break;
                                default:
                                    break;
                            }
                        }
                        $cfield_data[$cf] = $value;
                        unset($dbrow[$cf]);
                    }
                }
                $dbrow['customfields'] = json_encode($cfield_data);
                unset($cfield_data);
            }

            $datarows[] = $dbrow;
            $rowcount++;

            if ($rowcount >= shezar_SYNC_DBROWS) {
                // Bulk insert
                if (!shezar_sync_bulk_insert($temptable, $datarows)) {
                    $this->addlog(get_string('couldnotimportallrecords', 'tool_shezar_sync'), 'error', 'populatesynctabledb');
                    return false;
                }

                $rowcount = 0;
                unset($datarows);
                $datarows = array();

                gc_collect_cycles();
            }
        }

        // Insert remaining rows
        if (!shezar_sync_bulk_insert($temptable, $datarows)) {
            $this->addlog(get_string('couldnotimportallrecords', 'tool_shezar_sync'), 'error', 'populatesynctabledb');
            return false;
        }

        return true;
    }
}
