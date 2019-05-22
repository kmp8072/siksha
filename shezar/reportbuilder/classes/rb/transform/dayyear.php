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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_reportbuilder
 */

namespace shezar_reportbuilder\rb\transform;

/**
 * Class describing column transformation options.
 */
class dayyear extends base {
    protected static function get_field_transform($field) {
        global $DB;
        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily === 'mysql') {
            $expr = "FROM_UNIXTIME($field, '%j')";
        } else if ($dbfamily === 'mssql') {
            $expr = "RIGHT('000' + CAST(DATEPART(dayofyear, DATEADD(second, $field, {d '1970-01-01'})) AS NVARCHAR(3)), 3)";
        } else {
            $expr = "TO_CHAR(TO_TIMESTAMP($field), 'DDD')";
        }
        return "CASE WHEN ($field IS NULL OR $field = 0) THEN NULL ELSE $expr END";
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return true;
    }
}
