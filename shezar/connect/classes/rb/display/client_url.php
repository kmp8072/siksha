<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @package shezar_connect
 */

namespace shezar_connect\rb\display;
use \shezar_connect\util;

/**
 * Class describing column display formatting.
 *
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_connect
 */
class client_url extends \shezar_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        if ($format !== 'html') {
            return $value;
        }

        $extra = self::get_extrafields_row($row, $column);
        if ($extra->status == util::CLIENT_STATUS_OK) {
            if (strpos($value, 'https://') !== 0) {
                $value .= '<br />' . $OUTPUT->notification(get_string('errorhttpclient', 'shezar_connect'), 'notifyproblem');
            }
        }

        return $value;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}



