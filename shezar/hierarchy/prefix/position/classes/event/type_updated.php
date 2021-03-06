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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar_hierarchy
 */

namespace hierarchy_position\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Triggered when a hierarchy type is updated.
 *
 * @property-read array $other {
 *      Extra information about the event.
 * }
 *
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar_hierarchy
 */
class type_updated extends \shezar_hierarchy\event\type_updated {
    /**
     * Returns hierarchy prefix.
     * @return string
     */
    public function get_prefix() {
        return 'position';
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'pos_type';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('eventupdatedtype', 'hierarchy_position');
    }
}
