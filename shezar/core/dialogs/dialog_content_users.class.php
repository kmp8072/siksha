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
 * @author David Curry <david.curry@shezarlearning.com>
 * @package shezar
 * @subpackage shezar_core/dialogs
 */


require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/shezar/core/dialogs/dialog_content.class.php');

class shezar_dialog_content_users extends shezar_dialog_content {

    /**
     * If you are making access checks seperately, you can disable
     * the internal checks by setting this to true
     *
     * @access  public
     * @var     boolean
     */
    public $skip_access_checks = true;


    /**
     * Type of search to perform (generally relates to dialog type)
     *
     * @access  public
     * @var     string
     */
    public $searchtype = 'users';


    /**
     * Construct
     */
    public function __construct() {

        // Make some capability checks
        if (!$this->skip_access_checks) {
            require_login();
        }

        $this->type = self::TYPE_CHOICE_MULTI;
    }

    /**
     * Load hierarchy items to display
     *
     * @access  public
     * @param   $parentid   int
     */
    public function load_items($parentid) {
        $this->items = $this->get_items_by_parent($parentid);

        // If we are loading non-root nodes, tell the dialog_content class not to
        // return markup for the whole dialog
        if ($parentid > 0) {
            $this->show_treeview_only = true;
        }

        // Also fill parents array
        $this->parent_items = $this->get_all_parents();
    }


    /**
     * Should we show the treeview root?
     *
     * @access  protected
     * @return  boolean
     */
    protected function _show_treeview_root() {
        return !$this->show_treeview_only;
    }


    /**
     * Return all possible managers
     *
     * @return array Array of managers
     */
    function get_items() {
        global $DB;

        $guestuser = guest_user();

        return $DB->get_records_sql("
            SELECT DISTINCT u.id AS sortorder, u.id AS id, u.lastname
              FROM {user} u
             WHERE u.id != :guestid
               AND u.deleted = 0
               AND u.suspended = 0
             ORDER BY u.lastname"
        , array('guestid' => $guestuser->id));
    }

    /**
     * Get all users who could potentially be managers
     *
     * @param int|bool $parentmanagerid
     * @return array
     */
    function get_items_by_parent($parentmanagerid = false) {
        global $DB;

        return $this->get_all_root_items();
    }


    /**
     * Returns all users who are managers but don't have managers, e.g.
     * the top level of the management hierarchy
     *
     * @return array The records for the top level managers
     */
    function get_all_root_items() {
        global $DB;

        $guestuser = guest_user();
        $allnamefields = get_all_user_name_fields(true, 'u');

        $records = $DB->get_records_sql("
            SELECT DISTINCT u.id AS sortorder, u.id AS id, {$allnamefields}, u.email
              FROM {user} u
             WHERE u.id != :guestid
               AND u.deleted = 0
               AND u.suspended = 0
             ORDER BY u.lastname, u.firstname, sortorder"
         , array('guestid' => $guestuser->id));

        foreach ($records as $index => $record) {
            $records[$index]->fullname = fullname($record);
        }

        return $records;
    }


    /**
     * Get all items that are parents
     * (Use in hierarchy treeviews to know if an item is a parent of others, and
     * therefore has children)
     *
     * @return  array
     */
    function get_all_parents() {
        global $DB;

        return array();
    }
}
