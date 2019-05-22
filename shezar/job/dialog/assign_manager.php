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
 * @author Brendan Cox <brendan.cox@shezarlearning.com>
 * @package shezar_job
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) .'/config.php');
require_once($CFG->dirroot . '/shezar/core/dialogs/dialog_content.class.php');
require_once($CFG->dirroot . '/shezar/job/lib.php');

/**
 */
class shezar_job_dialog_assign_manager extends shezar_dialog_content {

    private $userid, $managers = array(), $jobassignments = array();

    /** @var  bool true if this dialog is for temp managers. */
    private $tempmanagers = false;

    /** @var bool|int - only required if using dialog for temp managers. */
    private $usualmanagerid = false;

    /** @var string used to prefix manager ids  on expandable nodes so that
     * they are differentiated from selected manager/job assignments. */
    private $manageridprefix = 'mgr';

    /** @var int|null Userid of manager whose job assignments we're viewing.
     * Or left unset if we want to return all managers to the dialog. */
    private $managerid;

    /** @var array the keys are passed used to update the form where this dialog was opened. */
    protected $datakeys = array('userid', 'jaid', 'displaystring');

    public $searchtype = 'assign_manager';

    protected $showfoldericons = false;

    /** @var bool - true if $USER is able to see emails when viewing a list of users. */
    private $canviewemail;

    private $restrictmanagers = false;

    /** @var bool if true, this allows empty job assignments to be created as long current user also has capabilities */
    private $allowcreate = true;

    public function __construct($userid, $managerid = false, $usualmanagerid = null) {

        // By default this is a single item dialog. Use set_as_multi_item method to change.
        $this->type = self::TYPE_CHOICE_SINGLE;

        $this->userid = $userid;
        $this->urlparams = array('userid' => $userid);
        // We define this so that someone can't select themself as manager via the search.
        $this->customdata = array('current_user' => $userid);

        // Check if being used to select temp manager.
        $this->tempmanagers = isset($usualmanagerid);
        if ($this->tempmanagers) {
            $this->usualmanagerid = $usualmanagerid;
            $this->urlparams['usualmgrid'] = $this->usualmanagerid;
            if (get_config(null, 'tempmanagerrestrictselection')) {
                $this->restrict_to_current_managers(true);
            }
        }

        $this->canviewemail = in_array('email', get_extra_user_fields(context_system::instance()));

        $this->managerid = $this->unprefix_managerid($managerid);
        if (!empty($this->managerid)) {
            $this->show_treeview_only = true;
        }
    }

    public function load_data() {
        if (empty($this->managerid)) {
            $this->load_managers();
        } else {
            $this->load_job_assignments();
        }
    }

    private function load_managers() {

        $managers = $this->get_managers_from_db();

        $multiplejobsenabled = get_config(null, 'shezar_job_allowmultiplejobs');

        foreach ($managers as $manager) {
            $manager->fullname = fullname($manager);
            $jobassignments = \shezar_job\job_assignment::get_all($manager->id);
            $manager_assignments_count = count($jobassignments);

            if ($this->allowcreate) {
                // Can the current user create the empty placeholder job assignments for this manager.
                $canedit = shezar_job_can_edit_job_assignments($manager->id);
            } else {
                $canedit = false;
            }

            $item = new stdClass();
            $item->id = $this->prefix_managerid($manager->id);
            if ($manager_assignments_count === 0) {
                // There's no job assignments for this manager. Whether they are selectable or not
                // depends on whether the current user has permissions to create a job assignment for them.
                if ($canedit) {
                    // Using a name property as the dialog may try to add the email to fullname
                    // properties if an email property is present.
                    $item->name = shezar_job_display_user_job($manager, null, $this->canviewemail, true);
                    $item->userid = $manager->id;
                    $item->jaid = null;
                    $item->displaystring = $item->name;
                } else {
                    $item->name = shezar_job_display_user_job($manager, null, $this->canviewemail, false);
                    $this->disabled_items[$item->id] = $item;
                }
                $this->managers[$item->id] = $item;
            } else if (!$canedit and (($manager_assignments_count === 1) or !$multiplejobsenabled)) {
                // There's one job to display. There's no other options available to the user. So they can
                // select this manager and this job. No need to make the node expandable.
                $firstjob = \shezar_job\job_assignment::get_first($manager->id);
                $item->id = $manager->id . '-' . $firstjob->id;
                $item->userid = $manager->id;
                $item->jaid = $firstjob->id;
                $item->name = shezar_job_display_user_job($manager, $firstjob, $this->canviewemail, false);
                $item->displaystring = $item->name;
                $this->managers[$item->id] = $item;
            } else {
                // There are 2 or more job assignments for this manager. Or 1 but the current user will
                // also have the option to add an empty job assignment. Therefore this manager simply has
                // an expandable node.
                if ($this->canviewemail) {
                    $item->name = get_string('dialogmanageremail', 'shezar_job', $manager);
                } else {
                    $item->name = $manager->fullname;
                }
                $this->managers[$item->id] = $item;
                $this->expandonly_items[$item->id] = $item;
                $this->parent_items[$item->id] = $item;
            }
        }
    }

    private function load_job_assignments() {

        if (empty($this->managerid)) {
            // This is used when expanding a single manager's node only.
            $this->jobassignments = array();
            return;
        }

        $manager = $this->get_managers_from_db($this->managerid);
        $manager->fullname = fullname($manager);

        $jobassignments = \shezar_job\job_assignment::get_all($this->managerid);

        foreach ($jobassignments as $jobassignment) {
            $item = new stdClass();
            $item->id = $this->managerid . '-' . $jobassignment->id;
            $item->userid = $this->managerid;
            $item->jaid = $jobassignment->id;
            $item->name = $jobassignment->fullname;
            $item->displaystring = shezar_job_display_user_job($manager, $jobassignment, $this->canviewemail);

            $this->jobassignments[$item->id] = $item;
        }

        if ($this->allowcreate && shezar_job_can_edit_job_assignments($manager->id)) {
            // The current user can create the empty placeholder job assignments for this manager.
            $item = new stdClass();
            $item->id = $this->managerid . '-NEW';
            $item->userid = $this->managerid;
            $item->jaid = null;
            $item->name = get_string('dialogmanagercreateemptyjob', 'shezar_job');
            $item->displaystring = shezar_job_display_user_job($manager, null, $this->canviewemail, true);

            $this->jobassignments[$item->id] = $item;
        }
    }

    public function generate_markup() {
        $this->items = array_merge($this->managers, $this->jobassignments);

        return parent::generate_markup();
    }

    private function prefix_managerid($id) {
        return $this->manageridprefix . $id;
    }

    private function unprefix_managerid($mgrid) {
        if (empty($mgrid)) {
            return null;
        }
        // Validate that it has the correct prefix first.
        if (strpos($mgrid, $this->manageridprefix) !== 0) {
            throw new coding_exception('Wrong manager id prefix being used');
        }
        return (int)substr($mgrid, 3);
    }

    private function get_managers_from_db($managerid = null) {
        global $DB;

        if ($this->canviewemail) {
            $email = 'u.email, ';
        } else {
            $email = '';
        }

        // Determine the fields to select.
        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT DISTINCT u.id, {$email} {$usernamefields}";

        list($joinsql, $params) = $this->get_managers_joinsql_and_params();
        $sql .= $joinsql;

        if (empty($managerid)) {
            // We'll get all potential managers. Put them in some kind of order.
            $usernamefields = get_all_user_name_fields(true, 'u');
            $sql .= "
                ORDER BY {$usernamefields}
            ";
        } else {
            // Get the specified manager.
            $sql .= "
                AND u.id = :managerid
            ";
            $params['managerid'] = $managerid;
        }

        if (empty($managerid)) {
            // Limit results to 1 more than the maximum number that might be displayed
            // there is no point returning any more as we will never show them.
            return $DB->get_records_sql($sql, $params, 0, shezar_DIALOG_MAXITEMS + 1);
        } else {
            // We just want one record.
            return $DB->get_record_sql($sql, $params, MUST_EXIST);
        }
    }

    protected function get_managers_joinsql_and_params($joinjobassignments = false) {

        $sql = '';

        if ($this->restrictmanagers) {
            // If this dialog is for temp managers and the setting to restrict selection
            // to only current managers is on, we need to do a couple of joins.
            $sql .= "
              FROM {job_assignment} staffja
              JOIN {job_assignment} managerja ON staffja.managerjaid = managerja.id
              JOIN {user} u ON managerja.userid = u.id
            ";
        } else if ($joinjobassignments) {
            $sql .= "
              FROM {user} u
              LEFT JOIN {job_assignment} managerja ON managerja.userid = u.id
            ";
        } else {
            // Otherwise, we just query from the user table.
            $sql .= "
                 FROM {user} u
            ";
        }

        // We don't need to include the usualmanagerid unless this is for tempmanagers AND the
        // usualmanagerid has been specified. But rather than customise the query too much. We'll include
        // it and make it zero if it's not supposed to be relevant.
        if (empty($this->usualmanagerid)) {
            $usualmgrid = 0;
        } else {
            $usualmgrid = $this->usualmanagerid;
        }


        $guest = guest_user();

        $sql .= "WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND u.id != :guestid
                   AND u.id != :userid
                   AND u.id != :usualmgrid
               ";
        $params = array(
            'guestid' => $guest->id,
            'userid' => $this->userid,
            'usualmgrid' => $usualmgrid
        );

        return array($sql, $params);
    }

    public function get_search_items_array($results) {
        $items = array();

        $addcreateitem = null;
        foreach ($results as $result) {
            $item = new stdClass();
            $item->id = $result->id;

            if ($this->allowcreate && isset($addcreateitem)) {
                if (($result->userid != $addcreateitem->userid) && shezar_job_can_edit_job_assignments($addcreateitem->userid)) {
                    // We've moved on to a new user. We need to add a 'create job assignment' option for the previous one.
                    $previtem = new stdClass();
                    $previtem->id = $addcreateitem->userid . '-NEW';
                    $previtem->fullname = shezar_job_display_user_job($addcreateitem, null, $this->canviewemail, true);
                    $previtem->userid = $addcreateitem->userid;
                    $previtem->jaid = 0;
                    $previtem->displaystring = $previtem->fullname;
                    $items[$previtem->id] = $previtem;

                    $addcreateitem = null;
                }
            }

            if ($result->idnumber) {
                $item->fullname = shezar_job_display_user_job($result, $result, $this->canviewemail);
                $item->jaid = $result->jaid;
                $addcreateitem = clone($result);
            } else if ($this->allowcreate && shezar_job_can_edit_job_assignments($result->userid)) {
                $item->fullname = shezar_job_display_user_job($result, null, $this->canviewemail, true);
                $addcreateitem = null;
            } else {
                $item->fullname = shezar_job_display_user_job($result, null, $this->canviewemail);
                $addcreateitem = null;
                $this->disabled_items[$item->id] = $item;
            }
            $item->userid = $result->userid;
            $item->displaystring = $item->fullname;

            $items[$item->id] = $item;
        }

        if ($this->allowcreate && isset($addcreateitem) && shezar_job_can_edit_job_assignments($addcreateitem->userid)) {
            // We still need to add the 'create job assignment' option for the last user.
            $previtem = new stdClass();
            $previtem->id = $addcreateitem->userid . '-NEW';
            $previtem->fullname = shezar_job_display_user_job($addcreateitem, null, $this->canviewemail, true);
            $previtem->userid = $addcreateitem->userid;
            $previtem->jaid = 0;
            $previtem->displaystring = $previtem->fullname;
            $items[$previtem->id] = $previtem;

            $addcreateitem = null;
        }

        return $items;
    }

    public function set_as_multi_item($bool = true) {
        if ($bool) {
            $this->type = self::TYPE_CHOICE_MULTI;
        } else {
            $this->type = self::TYPE_CHOICE_SINGLE;
        }
    }

    public function restrict_to_current_managers($bool = true) {
        if ($bool) {
            $this->restrictmanagers = true;
        } else {
            $this->restrictmanagers = false;
        }
    }

    public function do_not_create_empty($bool = true) {
        if ($bool) {
            $this->allowcreate = false;
        } else {
            $this->allowcreate = true;
        }
    }
}