<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@shezarlms.com>
 * @package   theme_bootstrapbase
 */

defined('MOODLE_INTERNAL') || die();

class theme_bootstrapbase_core_course_management_renderer extends core_course_management_renderer {
    public function grid_start($id = null, $class = null) {
        $gridclass = 'grid-row-r row-fluid';
        if (is_null($class)) {
            $class = $gridclass;
        } else {
            $class .= ' ' . $gridclass;
        }
        $attributes = array();
        if (!is_null($id)) {
            $attributes['id'] = $id;
        }
        return html_writer::start_div($class, $attributes);
    }

    public function grid_column_start($size, $id = null, $class = null) {

        // Calculate Bootstrap grid sizing.
        $bootstrapclass = 'span'.$size;

        // Calculate YUI grid sizing.
        if ($size === 12) {
            $maxsize = 1;
            $size = 1;
        } else {
            $maxsize = 12;
            $divisors = array(8, 6, 5, 4, 3, 2);
            foreach ($divisors as $divisor) {
                if (($maxsize % $divisor === 0) && ($size % $divisor === 0)) {
                    $maxsize = $maxsize / $divisor;
                    $size = $size / $divisor;
                    break;
                }
            }
        }
        if ($maxsize > 1) {
            $yuigridclass =  "grid-col-{$size}-{$maxsize} grid-col";
        } else {
            $yuigridclass =  "grid-col-1 grid-col";
        }

        if (is_null($class)) {
            $class = $yuigridclass . ' ' . $bootstrapclass;
        } else {
            $class .= ' ' . $yuigridclass . ' ' . $bootstrapclass;
        }
        $attributes = array();
        if (!is_null($id)) {
            $attributes['id'] = $id;
        }
        return html_writer::start_div($class, $attributes);
    }

    protected function detail_pair($key, $value, $class ='') {
        $html = html_writer::start_div('detail-pair row yui3-g '.preg_replace('#[^a-zA-Z0-9_\-]#', '-', $class));
        $html .= html_writer::div(html_writer::span($key), 'pair-key span3 yui3-u-1-4');
        $html .= html_writer::div(html_writer::span($value), 'pair-value span9 yui3-u-3-4');
        $html .= html_writer::end_div();
        return $html;
    }
}
