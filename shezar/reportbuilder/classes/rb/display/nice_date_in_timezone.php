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
 * @author Oleg Demeshev <oleg.demeshev@shezarlearning.com>
 * @package shezar_reportbuilder
 */

namespace shezar_reportbuilder\rb\display;

/**
 * Display date in user or session timezone.
 *
 * @author Oleg Demeshev <oleg.demeshev@shezarlearning.com>
 * @package shezar_reportbuilder
 */
class nice_date_in_timezone extends base {

    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {

        if (!is_numeric($value) || $value == 0 || $value == -1) {
            return '';
        }

        $session = self::get_extrafields_row($row, $column);

        if (empty($session->timezone) || (int)$session->timezone == 99) {
            $targetTZ = \core_date::get_user_timezone();
        } else {
            $targetTZ = \core_date::normalise_timezone($session->timezone);
        }

        $date = userdate($value, get_string('strftimedate', 'langconfig'), $targetTZ);
        return $date;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}