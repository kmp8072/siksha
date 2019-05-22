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
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar_hierarchy
 */

namespace hierarchy_goal\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Triggered when a hierarchy is updated.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - fullname  The name of the hierarchy item
 * }
 *
 * @author David Curry <david.curry@shezarlms.com>
 * @package shezar_hierarchy
 */
class personal_updated extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param   \stdClass $instance A personal goal record.
     * @return  personal_updated
     */
    public static function create_from_instance(\stdClass $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'relateduserid' => $instance->userid,
            'other' => array(
                'fullname' => $instance->name,
            ),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->add_record_snapshot($event->objecttable, $instance);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'goal_personal';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string("eventupdatedpersonalgoal", "hierarchy_goal");
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The personal goal: {$this->objectid} was updated";
    }


    public function get_url() {
        $urlparams = array('user' => $this->relateduserid);
        return new \moodle_url('/shezar/hierarchy/prefix/goal/item/edit_personal.php', $urlparams);
    }

    public function get_legacy_logdata() {
        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = 'goal';
        $logdata[] = 'update personal goal';
        $logdata[] = $this->get_url()->out_as_local_url(false);
        $logdata[] = $this->data['other']['fullname'];
        $logdata[] = 0;
        $logdata[] = $this->relateduserid;

        return $logdata;
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    public function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        parent::validate_data();

        if (!isset($this->other['fullname'])) {
            throw new \coding_exception('fullname must be set in $other');
        }
    }
}