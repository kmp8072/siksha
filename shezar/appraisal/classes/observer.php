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
 * @package shezar
 * @subpackage appraisal
 */

defined('MOODLE_INTERNAL') || die();

class shezar_appraisal_observer {

    /**
     * Event that is triggered when a user is deleted.
     *
     * Checks for any appraisals roles the user may have had and archives them.
     *
     * @param \core\event\user_deleted $event
     *
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/shezar/appraisal/lib.php');

        $userid = $event->objectid;
        $transaction = $DB->start_delegated_transaction();

        // Delete all user_assignments and associated data for the user.
        appraisal::delete_learner_assignments($userid);

        // Unassign all role_assignments for the user, but retain associated data.
        appraisal::unassign_user_roles($userid);

        $transaction->allow_commit();
    }

    /**
     * Activation message handler
     * If message is not immediate - add scheduled event
     * Also process stage_due as technically it's not an event but scheduled action
     *
     * @param \shezar_appraisal\event\appraisal_activation $event
     */
    public static function appraisal_activation(\shezar_appraisal\event\appraisal_activation $event) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/shezar/appraisal/lib.php'); // We should move all the classes into self loading ones.

        $time = $event->other['time'];
        $appraisalid = $event->objectid;

        $sql = "SELECT id FROM {appraisal_event} WHERE triggered = 0 AND event IN (?, ?) AND appraisalid = ?";
        $params = array(appraisal_message::EVENT_APPRAISAL_ACTIVATION, appraisal_message::EVENT_STAGE_DUE, $appraisalid);
        $events = $DB->get_records_sql($sql, $params);
        foreach ($events as $id => $eventdata) {
            $eventmessage = new appraisal_message($id);
            if ($eventmessage->is_immediate() && $eventmessage->type == appraisal_message::EVENT_APPRAISAL_ACTIVATION) {
                $eventmessage->send_appraisal_wide_message();
            } else {
                $eventmessage->schedule($eventmessage->get_schedule_from($time));
                $eventmessage->save();
            }
        }
    }

    /**
     * Stage complete message handler
     *
     * @param \shezar_appraisal\event\appraisal_stage_completion $event
     */
    public static function appraisal_stage_completion(\shezar_appraisal\event\appraisal_stage_completion $event) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/shezar/appraisal/lib.php'); // We should move all the classes into self loading ones.

        $time = $event->other['time'];
        $stageid = $event->other['stageid'];
        $sql = "SELECT id FROM {appraisal_event} WHERE event = ? AND appraisalstageid = ?";
        $params = array(appraisal_message::EVENT_STAGE_COMPLETE, $stageid);
        $events = $DB->get_records_sql($sql, $params);
        foreach ($events as $id => $eventdata) {
            $eventmessage = new appraisal_message($id);
            if ($eventmessage->is_immediate()) {
                $eventmessage->send_user_specific_message($event->userid);
            } else {
                $newuserevent = new stdClass();
                $newuserevent->eventid = $id;
                $newuserevent->userid = $event->userid;
                $newuserevent->timescheduled = $eventmessage->get_schedule_from($time);
                $DB->insert_record('appraisal_user_event', $newuserevent);
            }
        }
    }

    /**
     * Get's all scheduled untriggered messages and send's them
     *
     * @param int $time current time
     */
    public static function send_scheduled($time) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/shezar/appraisal/lib.php'); // We should move all the classes into self loading ones.

        // First do scheduled messages that go to the whole appraisal.
        $sql = "SELECT ae.id
                FROM {appraisal_event} ae JOIN {appraisal} a ON (ae.appraisalid = a.id)
                WHERE a.status = ? AND timescheduled > 0 AND triggered = 0";
        $events = $DB->get_records_sql($sql, array(appraisal::STATUS_ACTIVE));
        foreach ($events as $id => $eventdata) {
            $event = new appraisal_message($id);
            if ($event->is_time($time)) {
                $event->send_appraisal_wide_message();
            }
        }

        // Then do scheduled messages that go to specific users.
        // Timescheduled in aue must always be set and triggered in ae is not relevant to these events.
        $sql = "SELECT ae.id, aue.userid, aue.timescheduled
                  FROM {appraisal_event} ae
                  JOIN {appraisal} a ON ae.appraisalid = a.id
                  JOIN {appraisal_user_event} aue ON aue.eventid = ae.id
                 WHERE a.status = ?";
        $userevents = $DB->get_records_sql($sql, array(appraisal::STATUS_ACTIVE));
        foreach ($userevents as $userevent) {
            $event = new appraisal_message($userevent->id);
            $event->schedule($userevent->timescheduled); // Use user-specific timescheduled for is_time calculation.
            if ($event->is_time($time)) {
                $event->send_user_specific_message($userevent->userid);
            }
        }
    }

}
