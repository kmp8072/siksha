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
 * @package shezar_plan
 */

namespace shezar_plan\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when priority scale updated.
 */
class priority_scale_updated extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create an event.
     * @param \stdClass $scale
     * @return priority_scale_updated
     */
    public static function create_from_scale($scale) {
        $data = array(
            'objectid' => $scale->id,
            'context' => \context_system::instance(),
        );

        self::$preventcreatecall = false;
        /** @var priority_scale_updated $event */
        $event = self::create($data);
        $event->add_record_snapshot('dp_priority_scale', $scale);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'dp_priority_scale';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventpriorityscaleupdated', 'shezar_plan');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' updated priority scale with id '{$this->objectid}'";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/shezar/plan/priorityscales/view.php', array('id' => $this->objectid));
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    public function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly');
        }
        parent::validate_data();
    }
}
