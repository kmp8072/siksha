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
 * @author Oleg Demeshev <oleg.demeshev@shezarlms.com>
 * @package shezar
 * @subpackage reportbuilder
 */

/**
 * Menu of choices select filter based on a signle value.
 */

global $CFG;

require_once($CFG->dirroot . '/shezar/reportbuilder/filters/select.php');

class rb_filter_menuofchoices extends rb_filter_select {

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    function get_sql_filter($data) {

        $value = $data['value'];
        $query = $this->get_field();
        $simplemode = $this->options['simplemode'];

        if ($simplemode) {
            // Use "equal to" operator for simple select.
            $operator = 1;
        } else {
            $operator = $data['operator'];
        }

        if ($operator == 0) {
            // Return 1=1 instead of TRUE for MSSQL support.
            return array(' 1=1 ', array());
        } else if ($operator == 1) {
            // Equal to.
            $param = rb_unique_param("fsequal_");
            return array("{$query} = :{$param}", array($param => $value));
        } else {
            // Not equal to.
            // Check for null case for is not operator.
            $param = rb_unique_param("fsequal_");
            return array("({$query} != :{$param} OR ({$query}) IS NULL)", array($param => $value));
        }
    }
}
