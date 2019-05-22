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
 * @package shezar_core
 */

namespace shezar_core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when bulk enrolments in course started.
 *
 * Note: this includes enrolments, unenrolments and enrolment modifications.
 *
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_core
 */
class bulk_enrolments_ended extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param \stdClass $instance enrol instance
     * @return bulk_enrolments_ended
     */
    public static function create_from_instance(\stdClass $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_course::instance($instance->courseid),
        );
        $event = self::create($data);
        $event->add_record_snapshot('enrol', $instance);
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'enrol';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventbulkenrolmentsfinished', 'shezar_core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Bulk enrolment changes were finished in course $this->courseid";
    }
}
