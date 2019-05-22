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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage shezar_sync
 */

require_once($CFG->dirroot.'/admin/tool/shezar_sync/sources/classes/source.pos.class.php');
require_once($CFG->dirroot.'/admin/tool/shezar_sync/lib.php');

class shezar_sync_source_pos_csv extends shezar_sync_source_pos {

    function get_filepath() {
        $path = '/csv/ready/pos.csv';
        $pathos = $this->get_canonical_filesdir($path);
        return $pathos;
    }

    function config_form(&$mform) {
        global $CFG;

        $filepath = $this->get_filepath();

        $this->config->import_idnumber = "1";
        $this->config->import_fullname = "1";
        $this->config->import_frameworkidnumber = "1";
        $this->config->import_timemodified = "1";

        if (empty($filepath) && get_config('shezar_sync', 'fileaccess') == FILE_ACCESS_DIRECTORY) {
            $mform->addElement('html', html_writer::tag('p', get_string('nofilesdir', 'tool_shezar_sync')));
            return false;
        }

        // Display file example
        $fieldmappings = array();
        foreach ($this->fields as $field) {
            if (!empty($this->config->{'fieldmapping_'.$field})) {
                $fieldmappings[$field] = $this->config->{'fieldmapping_'.$field};
            }
        }
        foreach ($this->customfields as $key => $f) {
            if (!empty($this->config->{'fieldmapping_'.$key})) {
                $fieldmappings[$key] = $this->config->{'fieldmapping_'.$key};
            }
        }

        $filestruct = array();
        foreach ($this->fields as $field) {
            if (!empty($this->config->{'import_'.$field})) {
                $filestruct[] = !empty($fieldmappings[$field]) ? '"'.$fieldmappings[$field].'"' : '"'.$field.'"';
            }
        }
        foreach (array_keys($this->customfields) as $f) {
            if (!empty($this->config->{'import_'.$f})) {
                $filestruct[] = !empty($fieldmappings[$f]) ? '"'.$fieldmappings[$f].'"' : '"'.$f.'"';
            }
        }

        $delimiter = $this->config->delimiter;
        $info = get_string('csvimportfilestructinfo', 'tool_shezar_sync', implode($delimiter, $filestruct));
        $mform->addElement('html', html_writer::tag('div', html_writer::tag('p', $info, array('class' => "informationbox"))));

        // Empty field info.
        $langstring = !empty($this->element->config->csvsaveemptyfields) ? 'csvemptysettingdeleteinfo' : 'csvemptysettingkeepinfo';
        $info = get_string($langstring, 'tool_shezar_sync');
        $mform->addElement('html', html_writer::tag('div', html_writer::tag('p', $info), array('class' => "alert alert-warning")));

        // Add some source file details
        $mform->addElement('header', 'fileheader', get_string('filedetails', 'tool_shezar_sync'));
        $mform->setExpanded('fileheader');
        if (get_config('shezar_sync', 'fileaccess') == FILE_ACCESS_DIRECTORY) {
            $mform->addElement('static', 'nameandloc', get_string('nameandloc', 'tool_shezar_sync'), html_writer::tag('strong', $filepath));
        } else {
            $link = "{$CFG->wwwroot}/admin/tool/shezar_sync/admin/uploadsourcefiles.php";
            $mform->addElement('static', 'uploadfilelink', get_string('uploadfilelink', 'tool_shezar_sync', $link));
        }

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'csvposencoding', get_string('csvencoding', 'tool_shezar_sync'), $encodings);
        $mform->setType('csvposencoding', PARAM_ALPHANUMEXT);
        $default = $this->get_config('csvposencoding');
        $default = (!empty($default) ? $default : 'UTF-8');
        $mform->setDefault('csvposencoding', $default);

        $delimiteroptions = array(
            ',' => get_string('comma', 'tool_shezar_sync'),
            ';' => get_string('semicolon', 'tool_shezar_sync'),
            ':' => get_string('colon', 'tool_shezar_sync'),
            '\t' => get_string('tab', 'tool_shezar_sync'),
            '|' => get_string('pipe', 'tool_shezar_sync')
        );

        $mform->addElement('select', 'delimiter', get_string('delimiter', 'tool_shezar_sync'), $delimiteroptions);
        $default = $this->config->delimiter;
        if (empty($default)) {
            $default = ',';
        }
        $mform->setDefault('delimiter', $default);

        parent::config_form($mform);
    }

    function config_save($data) {
        // Make sure we use a tab character for the delimiter, if a tab is selected.
        $this->set_config('delimiter', $data->{'delimiter'} == '\t' ? "\t" : $data->{'delimiter'});
        $this->set_config('csvposencoding', $data->{'csvposencoding'});

        parent::config_save($data);
    }

    function import_data($temptable) {
        global $CFG, $DB;

        $fileaccess = get_config('shezar_sync', 'fileaccess');

        if ($fileaccess == FILE_ACCESS_DIRECTORY) {
            if (!$this->filesdir) {
                throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofilesdir');
            }
            $filepath = $this->get_filepath();
            if (!file_exists($filepath)) {
                throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofiletosync', $filepath, null, 'warn');
            }
            $filemd5 = md5_file($filepath);
            while (true) {
                // Ensure file is not currently being written to
                sleep(2);
                $newmd5 = md5_file($filepath);
                if ($filemd5 != $newmd5) {
                    $filemd5 = $newmd5;
                } else {
                    break;
                }
            }
            // Check that file is readable
            if (!$file = is_readable($filepath)) {
                throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotreadx', $filepath);
            }

            // Move file to store folders
            $storedir = $this->filesdir . '/csv/store';
            if (!shezar_sync_make_dirs($storedir)) {
                throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotcreatedirx', $storedir);
            }
            $storefilepath = $storedir . '/' . time() . '.' . basename($filepath);
            rename($filepath, $storefilepath);

        } else if ($fileaccess == FILE_ACCESS_UPLOAD) {
            $fs = get_file_storage();
            $systemcontext = context_system::instance();
            $fieldid = get_config('shezar_sync', 'sync_pos_itemid');

            // Check the file exists
            if (!$fs->file_exists($systemcontext->id, 'shezar_sync', 'pos', $fieldid, '/', '')) {
                throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofileuploaded', $this->get_element_name(), null, 'warn');
            }

            // Get the file
            $fsfiles = $fs->get_area_files($systemcontext->id, 'shezar_sync', 'pos', $fieldid, 'id DESC', false);
            $fsfile = reset($fsfiles);

            // Set up the temp dir
            $tempdir = $CFG->tempdir . '/shezarsync/csv';
            check_dir_exists($tempdir, true, true);

            // Create temporary file (so we know the filepath)
            $fsfile->copy_content_to($tempdir.'/pos.php');
            $itemid = $fsfile->get_itemid();
            $fs->delete_area_files($systemcontext->id, 'shezar_sync', 'pos', $itemid);
            $storefilepath = $tempdir.'/pos.php';
        }

        $encoding = $this->get_config('csvposencoding');
        $storefilepath = shezar_sync_clean_csvfile($storefilepath, $encoding, $fileaccess, $this->get_element_name());

        // Open file from store for processing
        if (!$file = fopen($storefilepath, 'r')) {
            throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotopenx', $storefilepath);
        }

        // Map CSV fields with db fields.
        $fields = fgetcsv($file, 0, $this->config->delimiter);
        $fieldmappings = array();
        foreach ($this->fields as $field) {
            if (empty($this->config->{'import_'.$field})) {
                continue;
            }
            if (empty($this->config->{'fieldmapping_'.$field})) {
                $fieldmappings[$field] = $field;
            } else {
                $fieldmappings[$this->config->{'fieldmapping_'.$field}] = $field;
            }
        }

        foreach (array_keys($this->customfields) as $f) {
            if (empty($this->config->{'import_'.$f})) {
                continue;
            }
            if (empty($this->config->{'fieldmapping_'.$f})) {
                $fieldmappings[$f] = $f;
            } else {
                $fieldmappings[$this->config->{'fieldmapping_'.$f}] = $f;
            }
        }

        // Check field integrity for custom fields.
        foreach ($this->customfields as $cf => $name) {
            if (empty($this->config->{'import_'. $cf}) || in_array($cf, $fieldmappings)) {
                // Disabled or mapped fields can be ignored.
                continue;
            }
            if (!in_array($cf, $fields)) {
                throw new shezar_sync_exception($this->get_element_name(), 'importdata', 'csvnotvalidmissingfieldx', $cf);
            }
        }

        // Throw an exception if fields contain invalid characters
        foreach ($fields as $field) {
            $invalidchars = preg_replace('/[ ?!A-Za-z0-9_-]/i', '', $field);
            if (strlen($invalidchars)) {
                $errorvar = new stdClass();
                $errorvar->invalidchars = $invalidchars[0];
                $errorvar->delimiter = $this->config->delimiter;
                throw new shezar_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidinvalidchars', $errorvar);
            }
        }

        // Ensure necessary fields are present
        foreach ($fieldmappings as $field => $map) {
            if (!in_array($field, $fields)) {
                if ($map == 'typeidnumber') {
                    // typeidnumber field can be optional if no custom fields specified
                    $customfieldspresent = false;
                    foreach ($fields as $ff) {
                        if (preg_match('/^customfield_/', $ff)) {
                            $customfieldspresent = true;
                            break;
                        }
                    }
                    if (!$customfieldspresent) {
                        // No typeidnumber and no customfields; this is not a problem then ;)
                        continue;
                    }
                }
                if ($field == $map) {
                    throw new shezar_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldx', $field);
                } else {
                    throw new shezar_sync_exception($this->get_element_name(), 'mapfields', 'csvnotvalidmissingfieldxmappingx', (object)array('field' => $field, 'mapping' => $map));
                }
            }
        }
        // Finally, perform CSV to db field mapping
        foreach ($fields as $index => $field) {
            if (!preg_match('/^customfield_/', $field)) {
                if (in_array($field, array_keys($fieldmappings))) {
                    $fields[$index] = $fieldmappings[$field];
                }
            }
        }

        // Populate temp sync table from CSV
        $now = time();
        $datarows = array();    // holds csv row data
        $dbpersist = shezar_SYNC_DBROWS;  // # of rows to insert into db at a time
        $rowcount = 0;
        $fieldcount = new stdClass();
        $fieldcount->headercount = count($fields);
        $fieldcount->rownum = 0;
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'shezar_core');

        while ($row = fgetcsv($file, 0, $this->config->delimiter)) {
            $fieldcount->rownum++;
            // Skip empty rows
            if (is_array($row) && current($row) === null) {
                $fieldcount->fieldcount = 0;
                $fieldcount->delimiter = $this->config->delimiter;
                $this->addlog(get_string('fieldcountmismatch', 'tool_shezar_sync', $fieldcount), 'error', 'populatesynctablecsv');
                unset($fieldcount->delimiter);
                continue;
            }
            $fieldcount->fieldcount = count($row);
            if ($fieldcount->fieldcount !== $fieldcount->headercount) {
                $fieldcount->delimiter = $this->config->delimiter;
                $this->addlog(get_string('fieldcountmismatch', 'tool_shezar_sync', $fieldcount), 'error', 'populatesynctablecsv');
                unset($fieldcount->delimiter);
                continue;
            }
            $row = array_combine($fields, $row);  // nice associative array

            // Encode and clean the data.
            $row = shezar_sync_clean_fields($row);

            $row['parentidnumber'] = !empty($row['parentidnumber']) ? $row['parentidnumber'] : '';
            $row['parentidnumber'] = $row['parentidnumber'] == $row['idnumber'] ? '' : $row['parentidnumber'];

            if ($this->config->{'import_typeidnumber'} == '0') {
                unset($row['typeidnumber']);
            } else {
                $row['typeidnumber'] = !empty($row['typeidnumber']) ? $row['typeidnumber'] : '';
            }

            if (empty($row['timemodified'])) {
                $row['timemodified'] = $now;
            } else {
                // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                $parsed_date = shezar_date_parse_from_format($csvdateformat, trim($row['timemodified']), true);
                if ($parsed_date) {
                    $row['timemodified'] = $parsed_date;
                }
            }

            // Custom fields - need to handle custom field formats like dates here
            $customfieldkeys = preg_grep('/^customfield_.*/', $fields);
            if (!empty($customfieldkeys)) {
                $customfields = array();
                foreach ($customfieldkeys as $key) {
                    // Get shortname and check if we need to do field type processing
                    $value = trim($row[$key]);
                    if (!empty($value)) {
                        $shortname = str_replace('customfield_', '', $key);
                        $datatype = $DB->get_field('pos_type_info_field', 'datatype', array('shortname' => $shortname));
                        switch ($datatype) {
                            case 'datetime':
                                // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged
                                $parsed_date = shezar_date_parse_from_format($csvdateformat, $value, true);
                                if ($parsed_date) {
                                    $value = $parsed_date;
                                }
                                break;
                            default:
                                break;
                        }
                    }
                    $customfields[$key] = $value;
                    unset($row[$key]);
                }

                $row['customfields'] = json_encode($customfields);
            }

            $datarows[] = $row;
            $rowcount++;

            if ($rowcount >= $dbpersist) {
                $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'pos');
                // Bulk insert
                try {
                    shezar_sync_bulk_insert($temptable, $datarows);
                } catch (dml_exception $e) {
                    throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage());
                }

                $rowcount = 0;
                unset($datarows);
                $datarows = array();

                gc_collect_cycles();
            }
        }

        $this->check_length_limit($datarows, $DB->get_columns($temptable), $fieldmappings, 'pos');
        // Insert remaining rows
        try {
            shezar_sync_bulk_insert($temptable, $datarows);
        } catch (dml_exception $e) {
            throw new shezar_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'couldnotimportallrecords', $e->getMessage());
        }
        unset($fieldmappings);

        fclose($file);
        // Done, clean up the file(s)
        if ($fileaccess == FILE_ACCESS_UPLOAD) {
            unlink($storefilepath); // don't store this file in temp
        }

        return true;
    }
}
