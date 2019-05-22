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
 * facetoface module PHPUnit data generator class
 *
 * @package    mod_facetoface
 * @subpackage phpunit
 * @author     Maria Torres <maria.torres@shezarlms.com>
 * @author     Nathan Lewis <nathan.lewis@shezarlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_generator extends testing_module_generator {

    /**
     * The number of rooms created so far.
     * @var int
     */
    protected $roominstancecount = 0;

    /**
     * The number of assets created so far.
     * @var int
     */
    protected $assetinstancecount = 0;

    /**
     * Create new facetoface module instance
     * @param array|stdClass $record
     * @param array $options
     * @throws coding_exception
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }

        $defaults = array();
        $defaults['intro'] = 'Test facetoface ' . $i;
        $defaults['introformat'] = FORMAT_MOODLE;
        $defaults['name'] = get_string('pluginname', 'facetoface').' '.$i;
        $defaults['shortname'] = 'facetoface' . $i;
        $defaults['description'] = 'description';
        $defaults['thirdparty'] = null; // Default to username
        $defaults['thirdpartywaitlist'] = 0;
        $defaults['display'] = 6;
        $defaults['showoncalendar'] = '1';
        $defaults['approvaloptions'] = 'approval_none';
        $defaults['usercalentry'] = 1;
        $defaults['multiplesessions'] = 0;
        $defaults['completionstatusrequired'] = '{"100":1}';
        $defaults['managerreserve'] = 0;
        $defaults['maxmanagerreserves'] = 1;
        $defaults['reservecanceldays'] = 1;
        $defaults['reservedays'] = 2;
        foreach ($defaults as $field => $value) {
            if (!isset($record->$field)) {
                $record->$field = $value;
            }
        }

        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        } else {
            $record->cmidnumber = '';
        }

        $record->coursemodule = $this->precreate_course_module($record->course, $options);
        $id = facetoface_add_instance($record, null);
        return $this->post_add_instance($id, $record->coursemodule);
    }

    /**
     * Add facetoface session
     * @param array|stdClass $record
     * @param array $options
     * @throws coding_exception
     * @return bool|int session created
     */
    public function add_session($record, $options = array()) {
        global $USER, $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");

        $record = (object) (array) $record;

        if (empty($record->facetoface)) {
            throw new coding_exception('Session generator requires $record->facetoface');
        }

        if (!isset($record->sessiondates) && empty($record->sessiondates)) {
            $time = time();
            $sessiondate = new stdClass();
            $sessiondate->timestart = $time;
            $sessiondate->timefinish = $time + (DAYSECS * 2);
            $sessiondate->sessiontimezone = 'Pacific/Auckland';
            $sessiondate->roomid = 0;
            $sessiondate->assetids = array();
            $sessiondates = array($sessiondate);
        } else {
            $sessiondates = $record->sessiondates;
        }

        if (!isset($record->capacity)) {
            $record->capacity = 10;
        }
        if (!isset($record->allowoverbook)) {
            $record->allowoverbook = 0;
        }
        if (!isset($record->normalcost)) {
            $record->normalcost = '$100';
        }
        if (!isset($record->discountcost)) {
            $record->discountcost = '$NZ20';
        }
        if (!isset($record->discountcost)) {
            $record->discountcost = FORMAT_MOODLE;
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->waitlisteveryone)) {
            $record->waitlisteveryone = 0;
        }
        if (!isset($record->registrationtimestart)) {
            $record->registrationtimestart = 0;
        }
        if (!isset($record->registrationtimefinish)) {
            $record->registrationtimefinish = 0;
        }

        $record->usermodified = $USER->id;

        return facetoface_add_session($record, $sessiondates);
    }

    /**
     * Create a room - please use the add_custom_room, or add_site_wide_room methods.
     *
     * @param stdClass|array $record
     * @return mixed
     */
    protected function add_room($record) {
        global $DB, $USER;

        $this->roominstancecount++;
        $record = (object) $record;

        if (!isset($record->name)) {
            $record->name = 'Room '.$this->roominstancecount;
        }
        if (!isset($record->capacity)) {
            // Don't ever bet on the capacity, if you need to be something specific set it to that.
            $record->capacity = floor(rand(5, 50));
        }

        if (!empty($record->allowconflicts)) {
            $record->allowconflicts = 1;
        } else {
            $record->allowconflicts = 0;
        }

        if (!isset($record->description)) {
            $record->description = 'Description for room '.$this->roominstancecount;
        }
        if (!isset($record->custom)) {
            $record->custom = 1;
        }
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        $record->usermodified = $record->usercreated;
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        $record->timemodified = $record->timecreated;
        $id = $DB->insert_record('facetoface_room', $record);
        return $DB->get_record('facetoface_room', array('id' => $id));
    }

    /**
     * Add a custom room.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_custom_room($record) {
        $record = (object)$record;
        $record->custom = 1;
        return $this->add_room($record);
    }

    /**
     * Add a site wide room.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_site_wide_room($record) {
        $record = (object)$record;
        $record->custom = 0;
        return $this->add_room($record);
    }

    /**
     * Create a asset - please use the add_custom_asset, or add_site_wide_asset methods.
     *
     * @param stdClass|array $record
     * @return mixed
     */
    protected function add_asset($record) {
        global $DB, $USER;

        $this->assetinstancecount++;
        $record = (object) $record;

        if (!isset($record->name)) {
            $record->name = 'asset '.$this->assetinstancecount;
        }

        if (!empty($record->allowconflicts)) {
            $record->allowconflicts = 1;
        } else {
            $record->allowconflicts = 0;
        }

        if (!isset($record->description)) {
            $record->description = 'Description for asset '.$this->assetinstancecount;
        }
        if (!isset($record->custom)) {
            $record->custom = 1;
        }
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        $record->usermodified = $record->usercreated;
        if (!isset($record->usercreated)) {
            $record->usercreated = $USER->id;
        }
        if (!isset($record->timecreated)) {
            $record->timecreated = time();
        }
        $record->timemodified = $record->timecreated;
        $id = $DB->insert_record('facetoface_asset', $record);
        return $DB->get_record('facetoface_asset', array('id' => $id));
    }

    /**
     * Add a custom asset.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_custom_asset($record) {
        $record = (object)$record;
        $record->custom = 1;
        return $this->add_asset($record);
    }

    /**
     * Add a site wide asset.
     *
     * @param stdClass|array $record
     * @return stdClass
     */
    public function add_site_wide_asset($record) {
        $record = (object)$record;
        $record->custom = 0;
        return $this->add_asset($record);
    }

    /**
     * Resets this generator instance.
     */
    public function reset() {
        $this->roominstancecount = 0;
        $this->assetinstancecount = 0;
        parent::reset();
    }

    /**
     * Create facetoface content (Session)
     * @param stdClass $instance
     * @param array|stdClass $record
     * @return bool|int content created
     */
    public function create_content($instance, $record = array()) {
        $record = (array)$record + array(
            'facetoface' => $instance->id
        );

        return $this->add_session($record);
    }
}
