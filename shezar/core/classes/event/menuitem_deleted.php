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
 * @author Oleg Demeshev <oleg.demeshev@shezarlms.com>
 * @package shezar_core
 */

namespace shezar_core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The menu item deleted event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string shortname: Short name of menu item.
 * }
 *
 * @author Oleg Demeshev <oleg.demeshev@shezarlms.com>
 * @package shezar_core
 */
class menuitem_deleted extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param $itemid
     * @return menuitem_deleted
     */
    public static function create_from_item($itemid) {
        $data = array(
            'objectid' => $itemid,
            'context' => \context_system::instance()
        );
        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        return $event;
    }

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['objecttable'] = 'shezar_navigation';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventmenuitemdeleted', 'shezar_core');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "Menu item with id '{$this->objectid}' deleted";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/shezar/core/menu/delete.php', array('id' => $this->objectid));
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call menuitem_deleted::create() directly, use menuitem_deleted::create_from_item() instead.');
        }
        parent::validate_data();
    }
}
