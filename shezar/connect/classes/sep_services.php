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
 * @package shezar_connect
 */

namespace shezar_connect;

/**
 * Class sep_services provides the implementation for all web service calls
 * from shezar Connect clients.
 *
 * NOTE: developers must sanitise all $parameters before use!
 *
 * @package shezar_connect
 */
class sep_services {
    /**
     * Returns the supported API version.
     *
     * NOTE: This function must not change, we need to keep it in all future versions.
     *
     * @param \stdClass $client
     * @param array $parameters
     * @return array JSend compatible result
     */
    public static function get_api_version($client, array $parameters) {
        if (empty($parameters['clienttype']) or ($parameters['clienttype'] !== 'shezarlms' and $parameters['clienttype'] !== 'shezarsocial')) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'clienttype' => 'incorrect or missing clienttype name',
                ),
            );
        }

        return array(
            'status' => 'success',
            'data' => array(
                'minapiversion' => util::MIN_API_VERSION,
                'maxapiversion' => util::MAX_API_VERSION,
            ),
        );
    }

    /**
     * Try to update communication API.
     *
     * NOTE: This function must not change, we need to keep it in all future versions.
     *
     * @param \stdClass $client
     * @param array $parameters
     * @return array JSend compatible result
     */
    public static function update_api_version($client, array $parameters) {
        global $DB;

        if (!isset($parameters['apiversion']) or !is_number($parameters['apiversion'])) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'apiversion' => 'missing api version number',
                ),
            );
        }
        $parameters['apiversion'] = clean_param($parameters['apiversion'], PARAM_INT);

        if ($parameters['apiversion'] > util::MAX_API_VERSION or $parameters['apiversion'] < util::MIN_API_VERSION) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'apiversion' => 'unsupported api version number',
                ),
            );
        }

        if (empty($parameters['clienttype']) or ($parameters['clienttype'] !== 'shezarlms' and $parameters['clienttype'] !== 'shezarsocial')) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'clienttype' => 'incorrect or missing clienttype name',
                ),
            );
        }

        $DB->set_field('shezar_connect_clients', 'apiversion', $parameters['apiversion'], array('id' => $client->id));
        $DB->set_field('shezar_connect_clients', 'clienttype', $parameters['clienttype'], array('id' => $client->id));
        $DB->set_field('shezar_connect_clients', 'timemodified', time(), array('id' => $client->id));

        return array(
            'status' => 'success',
            'data' => array(),
        );
    }

    /**
     * Get all client users.
     *
     * This includes only basic info from the user table, no custom profiles or preferences.
     *
     * The deleted may have deleted flag != 0 or they may be just missing.
     * Unconfirmed self-registered users are included too.
     *
     * @param \stdClass $client
     * @param array $parameters
     * @return array JSend compatible result
     */
    public static function get_users($client, array $parameters) {
        global $DB;

        $guest = guest_user();
        $cohortjoin = "";
        $params = array('guestid' => $guest->id);

        if ($client->cohortid) {
            $cohortjoin = "JOIN {cohort_members} xcm ON xcm.userid = u.id
                           JOIN {shezar_connect_clients} xcl ON (xcl.cohortid = xcm.cohortid AND xcl.id = :clientid)";
            $params['clientid'] = $client->id;
        }

        $sql = "SELECT u.*
                  FROM {user} u
           $cohortjoin
                 WHERE u.id <> :guestid
              ORDER BY u.id ASC";

        $users = array();
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $user) {
            // Add profile fields, prefs and format description.
            util::prepare_user_for_client($user);
            $users[] = $user;
        }
        $rs->close();

        return array(
            'status' => 'success',
            'data' => array('users' => $users),
        );
    }

    /**
     * Get all client cohorts/courses and member users.
     *
     * @param \stdClass $client
     * @param array $parameters
     * @return array JSend compatible result
     */
    public static function get_user_collections($client, array $parameters) {
        global $DB;

        $guest = guest_user();

        // Get the list of cohorts.
        $sql = "SELECT c.*
                  FROM {cohort} c
                  JOIN {shezar_connect_client_cohorts} cc ON cc.cohortid = c.id
                 WHERE cc.clientid = :clientid
              ORDER BY c.id ASC";
        $cohorts = $DB->get_records_sql($sql, array('clientid' => $client->id));
        foreach ($cohorts as $k => $cohort) {
            $cohort->members = array();
            $cohorts[$k] = $cohort;
        }

        // Now add list of user ids to each cohort.
        $cohortjoin = "";
        $params = array('guestid' => $guest->id, 'clientid' => $client->id);
        if ($client->cohortid) {
            $cohortjoin = "JOIN {cohort_members} xcm ON xcm.userid = u.id
                           JOIN {shezar_connect_clients} xcl ON (xcl.cohortid = xcm.cohortid AND xcl.id = cc.clientid)";
        }
        $sql = "SELECT DISTINCT cm.userid, cm.cohortid
                  FROM {user} u
                  JOIN {cohort_members} cm ON cm.userid = u.id
                  JOIN {cohort} c ON c.id = cm.cohortid
                  JOIN {shezar_connect_client_cohorts} cc ON cc.cohortid = c.id
           $cohortjoin
                 WHERE u.id <> :guestid AND u.deleted = 0 AND cc.clientid = :clientid
              ORDER BY cm.userid ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $cm) {
            if (!isset($cohorts[$cm->cohortid])) {
                // Weird, concurrent modification?
                continue;
            }
            $cohorts[$cm->cohortid]->members[] = array('id' => $cm->userid);
        }
        $rs->close();

        // Get list of courses.
        $sql = "SELECT c.*
                  FROM {course} c
                  JOIN {shezar_connect_client_courses} cc ON cc.courseid = c.id
                 WHERE cc.clientid = :clientid AND c.category > 0
              ORDER BY c.id ASC";
        $courses = $DB->get_records_sql($sql, array('clientid' => $client->id));
        foreach ($courses as $k => $course) {
            $course->members = array();
            $courses[$k] = $course;
        }

        // Now add list of user ids to each course.
        $cohortjoin = "";
        $params = array('guestid' => $guest->id, 'clientid' => $client->id, 'ueactive' => ENROL_USER_ACTIVE, 'eenabled' => ENROL_INSTANCE_ENABLED);
        if ($client->cohortid) {
            $cohortjoin = "JOIN {cohort_members} xcm ON xcm.userid = u.id
                           JOIN {shezar_connect_clients} xcl ON (xcl.cohortid = xcm.cohortid AND xcl.id = cc.clientid)";
        }
        $sql = "SELECT DISTINCT ue.userid, e.courseid
                  FROM {user} u
                  JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.status = :ueactive)
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.status = :eenabled)
                  JOIN {course} c ON (c.id = e.courseid AND c.category > 0)
                  JOIN {shezar_connect_client_courses} cc ON cc.courseid = c.id
           $cohortjoin
                 WHERE u.id <> :guestid AND u.deleted = 0 AND cc.clientid = :clientid
              ORDER BY ue.userid ASC";
        // Note: this query ignores the start/end of enrolments.
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $ue) {
            if (!isset($courses[$ue->courseid])) {
                // Weird, concurrent modification?
                continue;
            }
            $courses[$ue->courseid]->members[] = array('id' => $ue->userid);
        }
        $rs->close();

        // Return the data.
        return array(
            'status' => 'success',
            'data' => array(
                'cohort'  => array_values($cohorts),
                'course'  => array_values($courses),
            ),
        );
    }

    /**
     * Get the SSO user info.
     *
     * NOTE: each ssotoken may be used only once with this method.
     *
     * @param \stdClass $client
     * @param array $parameters
     * @return array JSend compatible result
     */
    public static function get_sso_user($client, array $parameters) {
        global $DB;

        if (!isset($parameters['ssotoken'])) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'missing sso token',
                ),
            );
        }
        $parameters['ssotoken'] = clean_param($parameters['ssotoken'], PARAM_ALPHANUM);
        if (strlen($parameters['ssotoken']) !== 40) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'invalid sso token format',
                ),
            );
        }

        $ssosession = $DB->get_record('shezar_connect_sso_sessions', array('clientid' => $client->id, 'ssotoken' => $parameters['ssotoken']));

        if (!$ssosession) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'invalid sso token',
                )
            );
        }

        $session = $DB->get_record('sessions', array('sid' => $ssosession->sid, 'state' => 0));

        if (!$session or !\core\session\manager::session_exists($session->sid)) {
            util::terminate_sso_session($client, $ssosession);
            return array(
                'status' => 'error',
                'message' => 'session expired',
            );
        }

        if ($session->userid != $ssosession->userid) {
            util::terminate_sso_session($client, $ssosession);
            return array(
                'status' => 'error',
                'message' => 'invalid user session',
            );
        }

        $user = $DB->get_record('user', array('id' => $ssosession->userid, 'deleted' => 0, 'suspended' => 0));

        if (!$user) {
            util::terminate_sso_session($client, $ssosession);
            return array(
                'status' => 'error',
                'message' => 'invalid user session',
            );
        }

        // Prevent reuse of ssotoken in this method.
        if ($ssosession->active) {
            return array(
                'status' => 'error',
                'message' => 'reused ssotoken',
            );
        }
        $DB->set_field('shezar_connect_sso_sessions', 'active', 1, array('id' => $ssosession->id));

        // Add profile fields, prefs and format description.
        util::prepare_user_for_client($user, true);

        return array(
            'status' => 'success',
            'data' => $user,
        );
    }

    /**
     * Force user logout everywhere.
     *
     * @param \stdClass $client
     * @param array $parameters
     * @return array JSend compatible result
     */
    public static function force_sso_logout($client, array $parameters) {
        global $DB;

        if (!isset($parameters['ssotoken'])) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'missing sso token',
                ),
            );
        }
        $parameters['ssotoken'] = clean_param($parameters['ssotoken'], PARAM_ALPHANUM);
        if (strlen($parameters['ssotoken']) !== 40) {
            return array(
                'status' => 'fail',
                'data' => array(
                    'ssotoken' => 'invalid sso token format',
                ),
            );
        }

        $ssosession = $DB->get_record('shezar_connect_sso_sessions', array('clientid' => $client->id, 'ssotoken' => $parameters['ssotoken']));

        if (!$ssosession) {
            // Most probably already deleted.
            return array(
                'status' => 'success',
                'data' => array(),
            );
        }

        // Now kill sessions on all other SSO clients.
        $allsessions = $DB->get_records('shezar_connect_sso_sessions', array('sid' => $ssosession->sid));

        // Delete sessions first in case we are interrupted somehow.
        \core\session\manager::kill_session($ssosession->sid);
        $DB->delete_records('shezar_connect_sso_sessions', array('sid' => $ssosession->sid));

        foreach ($allsessions as $s) {
            $c = $DB->get_record('shezar_connect_clients', array('id' => $s->clientid));
            if (!$c) {
                continue;
            }
            util::terminate_sso_session($c, $s);
        }

        return array(
            'status' => 'success',
            'data' => array(),
        );
    }

    /**
     * The client does not want to be connected to this server any more.
     *
     * The client is marked as deleted and the record itself is kept.
     * No web service requests will be allowed in the future.
     *
     * @param \stdClass $client
     * @param array $parameters
     * @return array JSend compatible result
     */
    public static function delete_client($client, array $parameters) {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        $record = new \stdClass();
        $record->id = $client->id;
        $record->status       = $client->status       = util::CLIENT_STATUS_DELETED;
        $record->timemodified = $client->timemodified = time();

        $DB->update_record('shezar_connect_clients', $record);

        // Purge all other tables.
        util::purge_deleted_client($client);

        $trans->allow_commit();

        return array(
            'status' => 'success',
            'data' => array(),
        );
    }
}
