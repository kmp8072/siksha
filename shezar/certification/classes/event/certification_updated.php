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
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package shezar_certification
 */


namespace shezar_certification\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when certification settings are updated.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * }
 *
 * @author Maria Torres <maria.torres@shezarlms.com>
 * @package shezar_certification
 */
class certification_updated extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call */
    protected static $preventcreatecall = true;

    /** @var \stdClass The database record used to create the event */
    protected $program = null;

    /**
     * Create instance of event.
     *
     * @param   \program $instance A program class.
     * @return \core\event\base $event New event.
     */
    public static function create_from_instance(\program $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_program::instance($instance->id),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->program = $instance;
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get instance.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \stdClass
     */
    public function get_instance() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_instance() is intended for event observers only');
        }
        return $this->program;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'certif';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventupdated', 'shezar_certification');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The Certification settings for the program {$this->objectid} was updated by user {$this->userid}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/shezar/certification/edit_certification.php', array('id' => $this->objectid));
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array(SITEID, 'certification', 'edit', 'edit_certification.php?id=' . $this->objectid, 'ID: ' . $this->objectid);
    }

    /**
     * Validate data
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            $error  = 'cannot call certification_updated::create() directly,';
            $error .= 'use certification_updated::create_from_instance() instead.';
            throw new \coding_exception($error);
        }
        parent::validate_data();
    }
}
