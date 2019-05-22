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
 * @subpackage shezar_customfield
 */

/**
 * Serves customfield file type files. Required for M2 File API
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if file not found, does not return if found - just send the file
 */
function shezar_customfield_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    $fs = get_file_storage();
    $fullpath = "/{$context->id}/shezar_customfield/$filearea/$args[0]/$args[1]";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    // finally send the file
    send_stored_file($file, 86400, 0, true, $options); // download MUST be forced - security!
}

/**
 * Retrieve a list of all the available data types
 * @return   array   a list of the datatypes suitable to use in a select statement
 */
function customfield_list_datatypes() {
    global $CFG;

    $datatypes = array();

    if ($dirlist = get_directory_list($CFG->dirroot.'/shezar/customfield/field', '', false, true, false)) {
        foreach ($dirlist as $type) {
            $datatypes[$type] = get_string('customfieldtype'.$type, 'shezar_customfield');
            if (strpos($datatypes[$type], '[[') !== false) {
                $datatypes[$type] = get_string('customfieldtype'.$type, 'admin');
            }
        }
    }
    asort($datatypes);

    return $datatypes;
}

/**
 * Get custom field record based on it's id.
 *
 * @param string $tableprefix The table prefix where the custom field should be
 * @param int $id The ID of the customfield we want to find
 * @param string $datatype Custom field type
 * @return stdClass $field an instance of the custom field. If it's not found, a new instance is create with default values
 */
function customfield_get_record_by_id($tableprefix, $id, $datatype) {
    global $DB;

    if ($id) {
        if ($datatype !== '') {
            $field = $DB->get_record($tableprefix.'_info_field', array('id' => $id, 'datatype' => $datatype), '*', MUST_EXIST);
        } else {
            $field = $DB->get_record($tableprefix.'_info_field', array('id' => $id), '*', MUST_EXIST);
        }
    } else {
        $datatypes = customfield_list_datatypes();
        if (!isset($datatypes[$datatype])) {
            throw new invalid_parameter_exception('Unkwnown datatype');
        }

        $field = new stdClass();
        $field->id = 0;
        $field->datatype = $datatype;
        $field->description = '';
        $field->defaultdata = '';
        $field->forceunique = 0;
    }

    return $field;
}

/**
 * Get an instance of a custom field type. Used when creating a new custom field.
 *
 * @param string $prefix The custom field prefix
 * @param \context $sitecontext The context
 * @param array $extrainfo Array with extra info to create the custom field instance
 * @return a custom field instance based on the information provided
 */
function get_customfield_type_instace($prefix, $sitecontext, $extrainfo) {
    if (isset($extrainfo["class"]) && $extrainfo["class"] == 'personal') {
        $classname = 'shezar_customfield\\prefix\\'. $prefix . '_user';
    } else {
        $classname = 'shezar_customfield\\prefix\\'. $prefix . '_type';
    }

    if (!class_exists($classname)) {
        print_error('prefixtypeclassnotfound', 'shezar_customfield');
    }

    return new $classname($prefix, $sitecontext, $extrainfo);
}
