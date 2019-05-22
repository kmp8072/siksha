<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2014 onwards shezar Learning Solutions LTD
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
 * @author David Curry <david.curry@toraralearning.com>
 * @package shezar_reportbuilder
 */

/**
 * Rename reportbuilder columns. Using the $type param to constrain the renaming to a single
 * type is recommended to avoid renaming columns unintentionally.
 *
 * @param array $values     An array with data formatted like array($oldname => $newname)
 * @param string $type      The type constraint, e.g. 'user'
 */
function shezar_reportbuilder_migrate_column_names($values, $type = '') {
    global $DB;

    $typesql = '';
    $params = array();
    if (!empty($type)) {
        $typesql = ' AND type = :type';
        $params['type'] = $type;
    }

    foreach ($values as $oldname => $newname) {
        $sql = "UPDATE {report_builder_columns}
                   SET value = :newname
                 WHERE value = :oldname
                       {$typesql}";
        $params['newname'] = $newname;
        $params['oldname'] = $oldname;

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Map old position columns to the new job_assignment columns.
 *
 * @param array $values     An array of the values we are updating the type of
 * @param string $oldtype   The oldtype
 * @param string $newtype
 */
function shezar_reportbuilder_migrate_column_types($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    list($insql, $params) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
    $sql = "UPDATE {report_builder_columns}
               SET type = :newtype
             WHERE type = :oldtype
               AND value {$insql}";
    $params['newtype'] = $newtype;
    $params['oldtype'] = $oldtype;

    return $DB->execute($sql, $params);
}

/**
 * Rename reportbuilder filters. Using the $type param to constrain the renaming to a single
 * type is recommended to avoid renaming filters unintentionally.
 *
 * @param array $values     An array with data formatted like array($oldname => $newname)
 * @param string $type      The type constraint, e.g. 'user'
 */
function shezar_reportbuilder_migrate_filter_names($values, $type = '') {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    $typesql = '';
    $params = array();
    if (!empty($type)) {
        $typesql = 'AND type = :type';
        $params['type'] = $type;
    }

    foreach ($values as $oldname => $newname) {
        $sql = "UPDATE {report_builder_filters}
                   SET value = :newname
                 WHERE value = :oldname
                       {$typesql}";
        $params['newname'] = $newname;
        $params['oldname'] = $oldname;

        $DB->execute($sql, $params);
    }

    return true;
}

/**
 * Map old position filters to the new job_assignment columns.
 */
function shezar_reportbuilder_migrate_filter_types($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    list($insql, $params) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED);
    $sql = "UPDATE {report_builder_filters}
               SET type = :newtype
             WHERE type = :oldtype
               AND value {$insql}";
    $params['newtype'] = $newtype;
    $params['oldtype'] = $oldtype;

    return $DB->execute($sql, $params);
}

/**
 * Update the filters in any saved searches, generally used after migrating filter types.
 */
function shezar_reportbuilder_migrate_saved_search_filters($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    // Get all saved searches.
    $savedsearches = $DB->get_records('report_builder_saved');

    // Loop through them all and json_decode
    foreach ($savedsearches as $saved) {
        if (empty($saved)) {
            continue;
        }

        $search = unserialize($saved->search);

        if (!is_array($search)) {
            continue;
        }

        // Check for any filters that will need to be updated.
        foreach ($search as $key => $info) {
            list($type, $value) = explode('-', $key);

            // NOTE: This isn't quite as generic as the other functions.
            $value = $value == 'posstartdate' ? 'startdate' : $value;
            $value = $value == 'posenddate' ? 'enddate' : $value;

            if ($type == $oldtype && in_array($value, $values)) {
                $search[$newtype.'-'.$value] = $info;
                unset($search[$key]);
            }
        }

        // Re encode and update the database.
        $saved->search = serialize($search);
        $DB->update_record('report_builder_saved', $saved);
    }

    return true;
}

/**
 * Map reports default sort columns the to new job_assignment columns.
 */
function shezar_reportbuilder_migrate_default_sort_columns($values, $oldtype, $newtype) {
    global $DB;

    // If there is nothing to migrate just return.
    if (empty($values)) {
        return true;
    }

    foreach ($values as $sort) {
        $sql = "UPDATE {report_builder}
                   SET defaultsortcolumn = :newsort
                 WHERE defaultsortcolumn = :oldsort";
        $params = array(
            'oldsort' => $oldtype . '_' . $sort,
            'newsort' => $newtype . '_' . $sort
        );

        $DB->execute($sql, $params);
    }

    return true;
}
