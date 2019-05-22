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
 * @author Murali Nair <murali.nair@shezarlearning.com>
 * @package shezar_reportbuilder
 */

namespace shezar_reportbuilder\rb\display;

/**
 * Formats user profile custom fields.
 */
final class user_customfield extends base {
    /**
     * Actual value formatters to call after visibility check is done.
     */
    private static $formatters = [
        'parent'   => '\shezar_reportbuilder\rb\display\base',
        'checkbox' => '\shezar_reportbuilder\rb\display\yes_or_no',
        'date'     => '\shezar_reportbuilder\rb\display\nice_date_no_timezone',
        'datetime' => '\shezar_reportbuilder\rb\display\nice_datetime',
        'textarea' => '\shezar_reportbuilder\rb\display\userfield_textarea'
    ];

    /**
     * {@inheritdoc}
     */
    public static function display(
        $value,
        $format,
        \stdClass $row,
        \rb_column $column,
        \reportbuilder $report
     ) {
        GLOBAL $USER;

        $visibility = empty($column->extracontext['visible'])
                      ? PROFILE_VISIBLE_ALL
                      : $column->extracontext['visible'];

        $hiddenmsg = get_string('hiddencellvalue', 'shezar_reportbuilder');
        $isadmin = is_siteadmin($USER);

        if ($visibility === PROFILE_VISIBLE_NONE && !$isadmin) {
            // It should be not be possible to get here because this should be
            // checked elsewhere but you cannot count on that...
            return $hiddenmsg;
        }
        else if ($visibility === PROFILE_VISIBLE_PRIVATE
                 && $USER->id != $row->id
                 && !$isadmin) {
            return $hiddenmsg;
        }

        // If it gets to here, it means the current user has the rights to see
        // the real custom field value.
        $formatter = self::formatter($column);
        return call_user_func_array(
            "$formatter::display",
            [$value, $format, $row, $column, $report]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function is_graphable(
        \rb_column $column, \rb_column_option $option, \reportbuilder $report
    ) {
        $formatter = self::formatter($column);
        return call_user_func_array(
            "$formatter::is_graphable",
            [$column, $option, $report]
        );
    }

    /**
     * Returns the formatter for the specified custom field type in a column.
     *
     * @param \rb_column $column column metadata.
     *
     * @return string formatter class name.
     */
    private static function formatter(\rb_column $column) {
        $type = empty($column->extracontext['datatype'])
                ? ''
                : $column->extracontext['datatype'];

        return array_key_exists($type, self::$formatters)
               ? self::$formatters[$type]
               : self::$formatters['parent'];
    }
}
